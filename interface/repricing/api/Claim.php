<?php
/**
 * Created by PhpStorm.
 * User: aron
 * Date: 6/19/15
 * Time: 5:24 PM
 */

class Claim {

    var $summary;
    var $transactions;

    private $transactionMap;

    function __construct( $summary, $transactions) {
        $this->summary = $summary;
        $this->transactions = $transactions;

        $this->transactionMap = array();

        foreach( $this->transactions as $transaction ) {
            $this->transactionMap[$transaction['billingRowID']] = $transaction;
        }
    }

    public function getTransaction( $billingRowID ) {
        return $this->transactionMap[$billingRowID];
    }
}