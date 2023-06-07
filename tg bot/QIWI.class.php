<?php
class QIWI extends Qiwi\Api\BillPayments
{
    private $mysqli;
    private $table;
    private $amount_currency;
    private $amount_value;
    private $billId;
    private $siteId;
    private $status_value;
    private $id;
    private $sha256_hash_header;
    private $sha256_hash;
    private $invoice_parameters;
    private $error;
    function init($data, $mysqli, $table)
    {
        $this->mysqli = $mysqli;
        $this->table = $table;

        $this->sha256_hash_header = $_SERVER['HTTP_X_API_SIGNATURE_SHA256'];
        $this->amount_currency = $data['bill']['amount']['currency'];
        $this->amount_value = $data['bill']['amount']['value'];
        $this->billId = $data['bill']['billId'];
        $this->siteId = $data['bill']['siteId'];
        $this->status_value = $data['bill']['status']['value'];
        $this->id = $data['bill']['customer']['account'];

        $this->invoice_parameters = $this->amount_currency . '|' .
            $this->amount_value . '|' . $this->billId . '|' .
            $this->siteId . '|' . $this->status_value;

        $this->sha256_hash = hash_hmac('sha256', $this->invoice_parameters, $this->secretKey);

        if (($this->sha256_hash_header == $this->sha256_hash) && $this->status_value = "PAID") {
            $this->addPayment();
            $this->headerAnswer();
        } else {

            $this->error = array('error' => '1');
        }
    }

    function addPayment()
    {
        $this->error = array('error' => '0');

        $items = explode("-", $this->billId);
        $chat_id = $items[0];

        $search = $this->mysqli->query("SELECT buy, chat_id FROM $this->table WHERE chat_id=$chat_id");

        //Поиск в БД id чата для проставления статуса buy на 2
        //Также отправка на index.php data['out'] => 'addpayment' для checkAddpayment()
        while ($row = $search->fetch_assoc()) {

            $this->mysqli->query("UPDATE $this->table SET buy=2 WHERE chat_id =$chat_id");

            $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $data = array("out" => "addpayment", "chat_id" => $chat_id);

            $postdata = json_encode($data);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_exec($ch);
            curl_close($ch);
        }
    }

    function headerAnswer()
    {
        header("HTTP/1.1 200 OK");
        header('Content-Type: application/json');
        $jsonres = json_encode($this->error);
        echo $jsonres;
        error_log('error code' . $jsonres);
    }
}