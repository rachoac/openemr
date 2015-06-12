<?php

class ServiceCode {

    var $id;
    var $code;
    var $text;
    var $codeType;

    function __construct( $id, $code, $text, $codeType ) {
        $this->id = $id;
        $this->code = $code;
        $this->text = $text;
        $this->codeType = $codeType;
    }
}

?>
