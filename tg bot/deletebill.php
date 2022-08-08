<?php

$headers = array(
        "Authorization: Bearer eyJ2ZXJzaW9uIjoiUDJQIiwiZGF0YSI6eyJwYXlpbl9tZXJjaGFudF9zaXRlX3VpZCI6InRsam91by0wMCIsInVzZXJfaWQiOiI3OTUyMjAxNzYzMCIsInNlY3JldCI6IjA3M2NkNThlZmJjMzhlMzFjYjhiNjJhZWRkNzAwOTkwODRjYTVmNWRmZGFiOGU4YTY0ZWE5ZmE0NDM0NDViNWQifX0=",
        'Content-Type: application/json',
        "Accept: application/json"
);

$ch = curl_init("https://api.qiwi.com/partner/bill/v1/bills/1654121345/reject");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
$html = curl_exec($ch);
curl_close($ch);    
 
echo $html;