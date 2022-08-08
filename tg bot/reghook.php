<?php

$headers = array(
        "Authorization: Bearer 566a125f96a6c686e4e4b47a053f3b1a",
        "Accept: application/json"
);

$ch = curl_init("https://edge.qiwi.com/payment-notifier/v1/hooks?hookType=1&param=https%3A%2F%2Fcoorsebuy.ru%2Faddpayment.php&txnType=0");
curl_setopt($ch, CURLOPT_PUT, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
$html = curl_exec($ch);
curl_close($ch);    
 
echo $html;