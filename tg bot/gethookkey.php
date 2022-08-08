<?php

$headers = array(
        "Authorization: Bearer 566a125f96a6c686e4e4b47a053f3b1a",
        "Accept: application/json"
);

$ch = curl_init("https://edge.qiwi.com/payment-notifier/v1/hooks/4205e8b9-5384-4620-bf56-5fabb8b70bf3/key");
curl_setopt($ch, CURLOPT_GET, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
$html = curl_exec($ch);
curl_close($ch);    
 
echo $html;