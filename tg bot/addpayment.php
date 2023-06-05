<?php
// Секретный ключ от APIN
$secret_key = "Секретный ключ от QIWI";
$sha256_hash_header = $_SERVER['HTTP_X_API_SIGNATURE_SHA256'];

$conn = new mysqli("localhost", "bd", "password", "botbd");

$entity_body = file_get_contents('php://input');
$array_body = json_decode($entity_body, 1);

$amount_currency = $array_body['bill']['amount']['currency'];
$amount_value = $array_body['bill']['amount']['value'];
$billId = $array_body['bill']['billId'];
$siteId = $array_body['bill']['siteId'];
$status_value = $array_body['bill']['status']['value'];
$id = $array_body['bill']['customer']['account'];

$invoice_parameters = $amount_currency . '|' . $amount_value . '|' . $billId . '|' . $siteId . '|' . $status_value;

$sha256_hash = hash_hmac('sha256', $invoice_parameters, $secret_key);

// Проверка подписи вебхука

if (($sha256_hash_header == $sha256_hash) && $status_value = "PAID") {
    $error = array('error' => '0');

    $items = explode("-", $billId);
    $chat_id = $items[0];

    $search = $conn->query("SELECT buy, chat_id FROM user");

    //Поиск в БД id чата
    while ($row = $search->fetch_assoc()) {
        if ($row["chat_id"] == $chat_id) {
            if ($row["buy"] == 1) {

                $conn->query("UPDATE user SET buy=2 WHERE chat_id = '" . $chat_id . "'");

                $url = 'https://coorsebuy.ru/bot.php';
                $data = array("out" => "addpayment", "chat_id" => $chat_id);

                $postdata = json_encode($data);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                $result = curl_exec($ch);
                curl_close($ch);

            }
        }
    }
} else
    $error = array('error' => '1');
$conn->close();

//Ответ
header("HTTP/1.1 200 OK");
header('Content-Type: application/json');
$jsonres = json_encode($error);
echo $jsonres;
error_log('error code' . $jsonres);