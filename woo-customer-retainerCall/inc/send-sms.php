<?php



function woocr_do_send_melipayamak($api_metatext, $api_receiver){

    if ($api_metatext && $api_receiver) {
        ini_set("soap.wsdl_cache_enabled", 0);
        $sms = new SoapClient("http://api.payamak-panel.com/post/Send.asmx?wsdl", array("encoding" => "UTF-8"));
        $sms_config = [
            "username" => "DIVMAKERS_SMS_USER",
            "password" => "DIVMAKERS_SMS_PASS",
            "from" => 90006390,
            "text" => $api_metatext,
            "to" => array($api_receiver),
            "isflash" => false
        ];
        return $sms->SendSimpleSMS($sms_config)->SendSimpleSMSResult;
    }else{
        return false;
    }
}