<?php

require_once(dirname(__FILE__)."/../../globals.php");
require_once("$srcdir/billing.inc");
require_once("$srcdir/sql.inc");
require_once("ServiceCode.php");
require_once("Provider.php");
require_once("ClaimType.php");

class RepricingAPI {

    const SQL_SERVICE_CODES_SELECT =
        "SELECT id,
            code,
            code_text,
            code_types.ct_key as code_type,
            pr_price as allowedCharge
       FROM codes
       JOIN code_types on codes.code_type = code_types.ct_id
       LEFT OUTER JOIN prices on prices.pr_id = codes.id";

    const SQL_SERVICE_CODES_WHERE_SEARCH =
        "WHERE (code_types.ct_fee = 1)
           AND (id LIKE ?
                OR code_text LIKE ?
                OR code_text_short LIKE ?
                OR code LIKE ?
                OR code_types.ct_key LIKE ?)";

    const SQL_SERVICE_CODE_BY_ID =
        "WHERE id = ?";

    const SQL_USERS_SELECT =
        "SELECT id,
                fname,
                mname,
                lname,
                npi
           FROM users";

    const SQL_PATIENTS_SELECT =
        "SELECT id,
                fname,
                mname,
                lname
           FROM patient_data";

    const SQL_PROVIDERS_WHERE_SEARCH =
        "WHERE npi IS NOT NULL
           AND (   (fname LIKE ? OR mname LIKE ? OR lname LIKE ? OR npi LIKE ?)
                OR (CONCAT(fname, ' ', lname ) LIKE ?)
                OR (CONCAT(fname, ' ', mname, ' ', lname ) LIKE ?)
               )";

    const SQL_USERS_WHERE_GET_BY_ID =
        "WHERE id = ?";

    const SQL_PATIENTS_WHERE_GET_BY_ID =
        "WHERE pid = ?";

    const SQL_INSERT_PROVIDER =
        "INSERT INTO users (fname, mname, lname, npi) VALUES (?, ?, ?, ?)";

    const SQL_CLAIM_TYPES_SELECT =
        "SELECT option_id,
                title
           FROM list_options
          WHERE list_id = 'pricelevel'";

    function __construct() {
    }

    /**
     * Returns an array of ServiceCharge
     * @param string $searchTerm
     */
    function searchServiceCodes( $searchTerm ) {
        $stmt = sqlStatement( self::SQL_SERVICE_CODES_SELECT . " " . self::SQL_SERVICE_CODES_WHERE_SEARCH . " LIMIT 50",
            array( "%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%" ) );

        $codes = array();

        for($iter=0; $row=sqlFetchArray($stmt); $iter++) {
            $code = new ServiceCode(
                $row['id'],
                $row['code'],
                $row['code_text'],
                $row['code_type'],
                $row['allowedCharge'] ? $row['allowedCharge'] : 0.00
            );
            array_push( $codes, $code );
        }

        return $codes;
    }

    /**
     * Returns ServiceCode by ID
     * @param string service code ID
     */
    function getServiceCode( $id ) {
        $row = sqlQuery( self::SQL_SERVICE_CODES_SELECT . " " . self::SQL_SERVICE_CODE_BY_ID, array($id) );

        $code = new ServiceCode(
            $row['id'],
            $row['code'],
            $row['code_text'],
            $row['code_type'],
            $row['allowedCharge'] ? $row['allowedCharge'] : 0.00
        );

        return $code;
    }

    /**
     * Returns an array of Provider
     * @param string $searchTerm
     */
    function searchProviders( $searchTerm ) {
        $stmt = sqlStatement( self::SQL_USERS_SELECT . " " . self::SQL_PROVIDERS_WHERE_SEARCH . " LIMIT 50",
            array( "%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%" ) );

        $providers = array();

        for($iter=0; $row=sqlFetchArray($stmt); $iter++) {
            $provider = $this->userFromRow($row);
            array_push( $providers, $provider );
        }

        return $providers;
    }

    public function createProvider($fname, $mname, $lname, $npi) {
        $providerID = sqlInsert(self::SQL_INSERT_PROVIDER, array( $fname, $mname, $lname, $npi ) );

        $provider = sqlQuery(self::SQL_USERS_SELECT . " " . self::SQL_USERS_WHERE_GET_BY_ID, array ( $providerID ) );
        return $this->userFromRow($provider);
    }

    public function getPatient($patientID) {
        $user = sqlQuery(self::SQL_PATIENTS_SELECT . " " . self::SQL_PATIENTS_WHERE_GET_BY_ID, array ( $patientID ) );
        return $this->userFromRow($user);
    }

    public function getClaimTypes() {
        $stmt = sqlStatement(self::SQL_CLAIM_TYPES_SELECT );

        $claimTypes = array();

        for($iter=0; $row=sqlFetchArray($stmt); $iter++) {
            $claimType = new ClaimType( $row['option_id'], $row['title'] );
            array_push( $claimTypes, $claimType );
        }

        return $claimTypes;

    }

    /**
     * @param $row
     * @return Provider
     */
    public function userFromRow($row) {
        $name = $row['fname'];
        if ($row['mname']) {
            $name .= ' ' . $row['mname'];
        }
        $name .= ' ' . $row['lname'];
        $user = new Provider(
            $row['id'],
            $name
        );
        return $user;
    }

    public function saveClaim($claimData) {
        $summary = $claimData['summary'];
        $transactions = $claimData['transactions'];

        $patientID = $summary['patientID'];
        $providerID = $summary['$providerID'];
        $dateOfService = $summary['claimDate'];
        $encounterID = $summary['encounterID'];

        if (!$encounterID) {
            // create an encounter
            $encounterID = $GLOBALS['adodb']['db']->GenID('sequences');
            $this->createEncounter($dateOfService, $patientID, $encounterID );
        } else {
            $this->updateEncounter($dateOfService, $patientID, $encounterID);
        }

        foreach( $transactions as $transaction ) {
            $serviceCode = $this->getServiceCode($transaction['serviceCodeID']);

            $code_type = $serviceCode->codeType;
            $code = $serviceCode->id;
            $code_text = $serviceCode->text;
            $auth = 1;
            $modifier = "";
            $units = 1;
            $fee = $transaction['charge'];
            $ndc_info = "";
            $justify = "";
            $notecodes = "";

            addBilling($encounterID, $code_type, $code, $code_text, $patientID, $auth,
                $providerID, $modifier, $units, $fee, $ndc_info, $justify, 0, $notecodes);
        }

        updateClaim(true, $patientID, $encounterID, -1, -1, 2);
    }

    private function createEncounter($dos, $patient_pid, $encounter_id) {
        sqlInsert("INSERT INTO form_encounter SET " .
            "date = '$dos', " .
            "onset_date = '$dos', " .
            "facility_id = '3', " .
            "pid = '$patient_pid', " .
            "encounter = '$encounter_id'");
    }

    private function updateEncounter($dos, $patient_pid, $encounter_id) {
        sqlInsert("UPDATE form_encounter SET " .
            "date = '$dos', " .
            "onset_date = '$dos', " .
            "facility_id = '3', " .
            "pid = '$patient_pid', WHERE " .
            "encounter = '$encounter_id'");
    }
}
?>
