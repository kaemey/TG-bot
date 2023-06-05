<?php

$headers = array(
        "Authorization: Bearer SecretKey",
        'Content-Type: application/json',
        "Accept: application/json"
);

$ch = curl_init("https://api.qiwi.com/partner/bill/v1/bills/1654121345/reject");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$html = curl_exec($ch);
curl_close($ch);

echo $html;