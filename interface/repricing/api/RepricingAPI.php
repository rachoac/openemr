<?php

require_once(dirname(__FILE__)."/../../globals.php");
require_once("$srcdir/sql.inc");
require_once("ServiceCode.php");
require_once("Provider.php");

class RepricingAPI {

    const SQL_SERVICE_CODES_SELECT =
        "SELECT id,
            code,
            code_text,
            code_types.ct_key as code_type
       FROM codes JOIN code_types on codes.code_type = code_types.ct_id";

    const SQL_SERVICE_CODES_WHERE_SEARCH =
        "WHERE (code_types.ct_fee = 1)
           AND (id LIKE ?
                OR code_text LIKE ?
                OR code_text_short LIKE ?
                OR code LIKE ?
                OR code_types.ct_key LIKE ?)";

    const SQL_PROVIDERS_SELECT =
        "SELECT id,
                fname,
                mname,
                lname,
                npi
           FROM users";

    const SQL_PROVIDERS_WHERE_SEARCH =
        "WHERE npi IS NOT NULL
           AND (fname LIKE ?
               OR mname LIKE ?
               OR lname LIKE ?
               OR npi LIKE ?)";

    const SQL_PROVIDERS_WHERE_GET_BY_ID =
        "WHERE id = ?";

    const SQL_INSERT_PROVIDER =
        "INSERT INTO users (fname, mname, lname, npi) VALUES (?, ?, ?, ?)";

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
                $row['code_type']
            );
            array_push( $codes, $code );
        }

        return $codes;
    }

    /**
     * Returns an array of Provider
     * @param string $searchTerm
     */
    function searchProviders( $searchTerm ) {
        $stmt = sqlStatement( self::SQL_PROVIDERS_SELECT . " " . self::SQL_PROVIDERS_WHERE_SEARCH . " LIMIT 50",
            array( "%$searchTerm%", "%$searchTerm%", "%$searchTerm%", "%$searchTerm%" ) );

        $providers = array();

        for($iter=0; $row=sqlFetchArray($stmt); $iter++) {
            $provider = $this->providerFromRow($row);
            array_push( $providers, $provider );
        }

        return $providers;
    }

    public function createProvider($fname, $mname, $lname, $npi) {
        $providerID = sqlInsert(self::SQL_INSERT_PROVIDER, array( $fname, $mname, $lname, $npi ) );

        $provider = sqlQuery(self::SQL_PROVIDERS_SELECT . " " . self::SQL_PROVIDERS_WHERE_GET_BY_ID, array ( $providerID ) );
        return $this->providerFromRow($provider);
    }

    /**
     * @param $row
     * @return Provider
     */
    public function providerFromRow($row)
    {
        $name = $row['fname'];
        if ($row['mname']) {
            $name .= ' ' . $row['mname'];
        }
        $name .= ' ' . $row['lname'];
        $provider = new Provider(
            $row['id'],
            $name
        );
        return $provider;
    }
}
?>
