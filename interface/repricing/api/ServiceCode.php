<?php

class ServiceCode {

    var $id;
    var $code;
    var $text;
    var $codeType;
    var $allowedCharge;

    function __construct( $id, $code, $text, $codeType, $allowedCharge ) {
        $this->id = $id;
        $this->code = $code;
        $this->text = $text;
        $this->codeType = $codeType;
        $this->allowedCharge = $allowedCharge;
    }
}

?>
