<?php

require_once(dirname(__FILE__)."/../../globals.php");
require_once("$srcdir/sql.inc");
require_once("ServiceCode.php");

class RepricingAPI {

    const SQL_SELECT =
        "SELECT id,
            code,
            code_text,
            code_types.ct_key as code_type
       FROM codes JOIN code_types on codes.code_type = code_types.ct_id";

    const SQL_WHERE_SEARCH =
        "WHERE (code_types.ct_fee = 1)
           AND (id LIKE ?
                OR code_text LIKE ?
                OR code_text_short LIKE ?
                OR code LIKE ?
                OR code_types.ct_key LIKE ?)";

    function __construct() {
    }

    /**
     * Returns an array of ServiceCharge
     * @param string $searchTerm
     */
    function search( $searchTerm ) {
        $stmt = sqlStatement( self::SQL_SELECT . " " . self::SQL_WHERE_SEARCH,
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
}
?>
