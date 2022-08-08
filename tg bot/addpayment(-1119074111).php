<?php
// Секретный ключ от APIN
$secret_key = "eyJ2ZXJzaW9uIjoiUDJQIiwiZGF0YSI6eyJwYXlpbl9tZXJjaGFudF9zaXRlX3VpZCI6InRsam91by0wMCIsInVzZXJfaWQiOiI3OTUyMjAxNzYzMCIsInNlY3JldCI6IjA3M2NkNThlZmJjMzhlMzFjYjhiNjJhZWRkNzAwOTkwODRjYTVmNWRmZGFiOGU4YTY0ZWE5ZmE0NDM0NDViNWQifX0=";

$conn = new mysqli("localhost", "kaemey", "KripSan4ik", "bd1");

$entity_body = file_get_contents('php://input');  // Декодирую тело входящего запроса
$array_body = json_decode($entity_body, 1);           // в обычный массив

if(isset($entity_body))
$conn->query("INSERT INTO uved (count) VALUES ('1')");

$amount_currency = $array_body['bill']['amount']['currency'];
$amount_value = $array_body['bill']['amount']['value'];
$billId = $array_body['bill']['billId'];
$siteId = $array_body['bill']['siteId'];
$status_value = $array_body['bill']['status']['value'];

$invoice_parameters = $amount_currency . '|' . $amount_value . '|' . $billId . '|' . $siteId . '|' . $status_value;

// Вычисляем хэш SHA-256 строки параметров и шифруем с ключом для веб-хуков
$my_hash = hash_hmac("sha256", $invoice_parameters, $secret_key);

// Проверка подписи вебхука

/*$check = $billPayments->checkNotificationSignature(
  $_SERVER['HTTP_X_API_SIGNATURE_SHA256'], $array_body, $secret_key
); // true or false
*/

if ($sha256_hash_header == $sha256_hash && !empty($sha256_hash_header) && $status_value == 'PAID'){
    
    $error = array('error' => '0');

    $search = $conn->query("SELECT buy, chat_id FROM user WHERE chat_id = '".$billId."'");
            
        //Поиск в БД id чата
        while($row = $search->fetch_assoc()) {
            if ($row["chat_id"] == $billId){
                if($row["buy"] == 1){

                    $conn->query("UPDATE user SET buy=2 WHERE chat_id = '".$billId."'");

                    $get = array(
                    	'addpay'  => md5('coorsebuy'),
                    	'chat_id' => $billId,
                    );

                    $ch = curl_init('https://coorsebuy.ru/bot.php?' . http_build_query($get));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HEADER, false);
                    curl_close($ch);

                }
            }
        }
}
else $error = array('error' => '1');
$conn->close();	
//Ответ
header("HTTP/1.1 200 OK");
header('Content-Type: application/json');
$jsonres = json_encode($error);
echo $jsonres;
error_log('error code' . $jsonres);