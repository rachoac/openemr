<?php

require_once(dirname(__FILE__)."/../../globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/billing.inc");
require_once("$srcdir/forms.inc");
require_once("$srcdir/sql.inc");
require_once("ServiceCode.php");
require_once("ListOption.php");
require_once("Provider.php");
require_once("ClaimType.php");
require_once("EOBStatus.php");

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

    const SQL_SERVICE_CODE_BY_CODE =
        "WHERE code = ?";

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
        "WHERE npi IS NOT NULL AND authorized = 1
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
          WHERE list_id = 'pricelevel'
            AND option_id != 'standard'";

    const SQL_EOB_STATUS_SELECT =
        "SELECT option_id,
                title
           FROM list_options
          WHERE list_id = 'EOB_Status'";

    const SQL_FACILITYID_GET_BY_NAME =
        "SELECT id
           FROM facility
          WHERE name = ?";

    const SQL_FACILITY_NAME_GET_BY_ID =
        "SELECT name
           FROM facility
          WHERE id = ?";

    const SQL_APPLY_EOB_STATUS =
        "UPDATE claims
            SET eob_status = ?,
                eob_note = ?
          WHERE patient_id = ?
            AND encounter_id = ?
            AND version = 1";

    const SQL_ENCOUNTER_SELECT =
        "SELECT date,
                pc_catid,
                onset_date,
                provider_id,
                facility_id,
                pid,
                encounter
           FROM form_encounter";

    const SQL_ENCOUNTER_GET_BY_ENCOUNTER_ID =
        "WHERE encounter = ?";

    const SQL_SELECT_CLAIM_BY_PATIENT_ID_ENCOUNTER_ID =
        "SELECT * FROM claims WHERE patient_ID = ? AND encounter_id = ?";

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
    function getServiceCodeByID( $id ) {
        $row = sqlQuery( self::SQL_SERVICE_CODES_SELECT . " " . self::SQL_SERVICE_CODE_BY_ID, array($id) );
        $code = $this->createServiceCodeFromResult($row);
        return $code;
    }

    function getServiceCodeByCode( $code ) {
        $row = sqlQuery( self::SQL_SERVICE_CODES_SELECT . " " . self::SQL_SERVICE_CODE_BY_CODE, array($code) );
        $code = $this->createServiceCodeFromResult($row);
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

    public function getEOBStatuses() {
        $stmt = sqlStatement(self::SQL_EOB_STATUS_SELECT );

        $eobStatuses = array();

        for($iter=0; $row=sqlFetchArray($stmt); $iter++) {
            $eobStatus = new EOBStatus( $row['option_id'], $row['title'] );
            array_push( $eobStatuses, $eobStatus );
        }

        return $eobStatuses;
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

    public function getPayors($patientID, $dateOfService) {
        $primary = getInsuranceDataByDate($patientID, $dateOfService, 'primary' );
        $secondary = getInsuranceDataByDate($patientID, $dateOfService, 'secondary' );
        $tertiary = getInsuranceDataByDate($patientID, $dateOfService, 'tertiary' );

        $payorOptions = array();
        if ( $primary['provider'] ) {
            array_push($payorOptions, array(
                'payorID' => $primary['provider'],
                'payorName' => $primary['provider_name']
            ));
        }
        if ( $secondary['provider'] ) {
            array_push($payorOptions, array(
                'payorID' => $secondary['provider'],
                'payorName' => $secondary['provider_name']
            ));
        }
        if ( $tertiary['provider'] ) {
            array_push($payorOptions, array(
                'payorID' => $tertiary['provider'],
                'payorName' => $tertiary['provider_name']
            ));
        }

        return $payorOptions;
    }

    public function loadClaim($encounterID) {
        $claimData = array();

        $encounter = sqlQuery(self::SQL_ENCOUNTER_SELECT . " " . self::SQL_ENCOUNTER_GET_BY_ENCOUNTER_ID, array ( $encounterID ) );
        $facilityID = $encounter['facility_id'];
        $claimType = $this->getFacilityNameByID($facilityID);
        $patientID = $encounter['pid'];
        $providerID = $encounter['provider_id'];
        $dateOfService = $encounter['onset_date'];
        $claim = sqlQuery( self::SQL_SELECT_CLAIM_BY_PATIENT_ID_ENCOUNTER_ID, array($patientID, $encounterID) );
        $primaryPayorID = $claim['payer_id'];
        $eobStatus = $claim['eob_status'];
        $eobNote = $claim['eob_note'];

        $claimData['summary'] = array(
            'claimType' => $claimType,
            'patientID' => $patientID,
            'providerID' => $providerID,
            'claimDate' => $dateOfService,
            'encounterID' => $encounterID,
            'primaryPayorID' => $primaryPayorID,
            'eobStatus' => $eobStatus,
            'eobNote' => $eobNote,
        );

        $claimData['transactions'] = array();

        $billingRows = getBillingByEncounter($patientID, $encounterID);
        foreach( $billingRows as $billingRow ) {
            $serviceCode = $this->getServiceCodeByCode($billingRow['code']);
            $serviceCodeID = $serviceCode->id;
            $charge = $billingRow['fee'];

            $transaction = array(
                'serviceCodeID' => $serviceCodeID,
                'charge' => $charge
            );

            array_push($claimData['transactions'], $transaction);
        }

        return $claimData;
    }

    public function saveClaim($claimData) {
        $summary = $claimData['summary'];

        $claimType = $summary['claimType'];
        $patientID = $summary['patientID'];
        $providerID = $summary['providerID'];
        $dateOfService = $summary['claimDate'];
        $encounterID = $summary['encounterID'];
        $primaryPayorID = $summary['primaryPayorID'];
        $eobStatus = $summary['eobStatus'];
        $eobNote = $summary['eobNote'];
        $facilityID = $this->getFacilityIDByName($claimType);
        $userauthorized = empty($_SESSION['userauthorized']) ? 0 : $_SESSION['userauthorized'];

        if (!$encounterID) {
            // create an encounter
            $encounterID = $GLOBALS['adodb']['db']->GenID('sequences');
            $formID = $this->createEncounter($dateOfService, $patientID, $encounterID, $facilityID, $providerID);

            addForm($encounterID, "New Patient Encounter", $formID, "newpatient", $patientID,
                 "1", $dateOfService, $userauthorized );

            $summary['encounterID'] = $encounterID;
        } else {
            $this->updateEncounter($dateOfService, $patientID, $encounterID, $facilityID, $providerID);
        }

        foreach( $claimData['transactions'] as &$transaction ) {
            $serviceCodeID = $transaction['serviceCodeID'];
            $charge = $transaction['charge'];
            $billingRowID = $transaction['id'];

            $serviceCode = $this->getServiceCodeByID($serviceCodeID);
            $code_type = $serviceCode->codeType;
            $code = $serviceCode->code;
            $code_text = $serviceCode->text;
            $auth = 1;
            $modifier = "";
            $units = 1;
            $fee = $charge;
            $ndc_info = "";
            $justify = "";
            $notecodes = "";

            if ( !$billingRowID ) {
                $billingRowID = addBilling($encounterID, $code_type, $code, $code_text, $patientID, $auth,
                    $providerID, $modifier, $units, $fee, $ndc_info, $justify, 0, $notecodes);

                $transaction['id'] = $billingRowID;
            } else {
                // todo -- *update* billing instead of always adding one, if transaction row has an id present!
            }
        }

        updateClaim(true, $patientID, $encounterID, $primaryPayorID, 1, 2);
        $this->applyEOBStatus( $patientID, $encounterID, $eobStatus, $eobNote);

        return $claimData;
    }

    private function applyEOBStatus( $patientID, $encounterID, $eobStatus, $eobNote ) {
        sqlInsert(self::SQL_APPLY_EOB_STATUS, array ( $eobStatus, $eobNote, $patientID, $encounterID ) );
    }

    private function createEncounter($dos, $patient_pid, $encounter_id, $facilityID, $providerID) {
        return sqlInsert("INSERT INTO form_encounter SET " .
            "date = '$dos', " .
            "pc_catid = 9, " .
            "onset_date = '$dos', " .
            "sensitivity = 'normal', " .
            "provider_id = $providerID, " .
            "facility_id = $facilityID, " .
            "billing_facility = $facilityID, " .
            "pid = '$patient_pid', " .
            "encounter = '$encounter_id'");
    }

    private function updateEncounter($dos, $patient_pid, $encounter_id, $facilityID, $providerID) {
        sqlInsert("UPDATE form_encounter SET " .
            "date = '$dos', " .
            "pc_catid = 9, " .
            "onset_date = '$dos', " .
            "sensitivity = 'normal', " .
            "provider_id = $providerID, " .
            "facility_id = $facilityID, " .
            "billing_facility = $facilityID, " .
            "pid = '$patient_pid', WHERE " .
            "encounter = '$encounter_id'");
    }

    private function getFacilityIDByName($name) {
        $row = sqlQuery(self::SQL_FACILITYID_GET_BY_NAME, array ($name) );
        return $row['id'];
    }

    private function getFacilityNameByID($id) {
        $row = sqlQuery(self::SQL_FACILITY_NAME_GET_BY_ID, array ($id) );
        return $row['name'];
    }

    /**
     * @param $row
     * @return ServiceCode
     */
    public function createServiceCodeFromResult($row)
    {
        $code = new ServiceCode(
            $row['id'],
            $row['code'],
            $row['code_text'],
            $row['code_type'],
            $row['allowedCharge'] ? $row['allowedCharge'] : 0.00
        );
        return $code;
    }
}
?>
