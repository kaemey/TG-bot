<?php
require __DIR__ . "/vendor/autoload.php";
require "QIWI.class.php";
class Tgbot
{
    private $tg_token;
    public $mysqli;
    private $table;
    private $tg_api;
    private $data;
    private $chat_id;
    private $admin_chats_id;
    private $calls_admins;
    private $reply;
    private $responses;
    private $first_message;
    private $qiwi;
    private $qiwi_public_key;
    private $qiwi_response;
    private $qiwi_answer;
    function __construct($param = '')
    {
        include "config.php";

        if (!empty($param)) {
            if ($param = "setWebHook")
                $this->setWebHook($TG_TOKEN);
        }

        if ((!empty($QIWI_SECRET_KEY)) and (!empty($QIWI_PUBLIC_KEY))) {
            $this->qiwi = new QIWI($QIWI_SECRET_KEY);
            $this->qiwi_response = $qiwi_response;
            $this->qiwi_answer = $qiwi_answer;
            $this->qiwi_public_key = $QIWI_PUBLIC_KEY;
        }

        $this->mysqli = new mysqli($host, $username, $password, $database);
        $this->table = $table;

        $this->tg_api = 'https://api.telegram.org/bot' . $TG_TOKEN . '/';

        $this->responses = $responses;

        $this->first_message = $first_message;

        $this->calls_admins = $calls_admins;

        $this->reply = array("keyboard" => $keyboard, "resize_keyboard" => true, "one_time_keyboard" => true);
        $this->reply = json_encode($this->reply);

        $this->listen();
    }

    function listen()
    {
        $this->data = file_get_contents('php://input');
        $this->data = json_decode($this->data, true);

        $this->chat_id = $this->data['message']['chat']['id'];

        if (!empty($this->qiwi)) {

            if (!empty($this->data['bill'])) {
                $this->qiwi->init($this->data, $this->mysqli);
                $this->mysqli->close();
                exit();
            }

        }

        if (empty($this->chat_id)) {
            if (empty($this->data['out'])) {
                $this->mysqli->close();
                exit();
            } else {
                $this->checkAddPayment();
            }
        }

        $this->initUser();

        if (!empty($this->data['message']['text'])) {
            $this->checkMessage($this->data['message']['text']);
        }
    }

    function initUser()
    {
        $result = $this->mysqli->query("SELECT username, chat_id, buy, admin FROM $this->table");
        $found = false;

        if ($result->num_rows > 0) {
            //Поиск в БД админских id чатов
            while ($row = $result->fetch_assoc()) {
                if ($row["admin"] == 1) {
                    $this->admin_chats_id[] = $row["chat_id"];
                }
                if ($row["chat_id"] == $this->chat_id) {
                    $found = true;
                }
            }
        }

        if (!$found) {
            $this->mysqli->query("INSERT INTO $this->table (chat_id, username)
            VALUES ('" . $this->escape($this->chat_id) . "','" . $this->escape($this->data['message']['from']['username']) . "')");

            $this->sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $this->chat_id,
                    'text' => $this->first_message
                )
            );

            $this->mysqli->close();
            exit();
        }

    }

    function escape($value)
    {
        return $this->mysqli->real_escape_string($value);
    }

    function checkAddPayment()
    {
        if ($this->data['out'] == 'addpayment') {
            $this->sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $this->data["chat_id"],
                    'text' => "Оплата прошла!"
                )
            );
            $this->mysqli->close();
            exit();
        }
    }

    function getQiwiLink($key)
    {
        $bill = $this->chat_id . '-' . rand(0, 50) . rand(0, 50);

        $params = [
            'publicKey' => $key,
            'amount' => 990,
            'comment' => $bill,
            'billId' => $bill
        ];

        return $this->qiwi->createPaymentForm($params);
    }

    function sendTelegram($method, $response)
    {

        $response['reply_markup'] = $this->reply;
        $response['parse_mode'] = "HTML";

        $ch = curl_init($this->tg_api . $method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);

    }

    function sendQiwiText()
    {
        $this->sendTelegram(
            'sendMessage',
            array(
                'chat_id' => $this->chat_id,
                'text' => $this->qiwi_answer . $this->getQiwiLink($this->qiwi_public_key)
            )
        );
    }

    function checkMessage($message)
    {
        if (!empty($this->qiwi_response)) {
            if (mb_stripos($message, $this->qiwi_response) !== false) {
                $this->sendQiwiText();
                $this->mysqli->close();
                exit();
            }
        }

        foreach ($this->responses as $response => $answer) {
            if (mb_stripos($message, $response) !== false) {
                $this->answerMessage($answer);
                $this->mysqli->close();
                exit();
            }
        }

        foreach ($this->calls_admins as $call) {
            if (mb_stripos($message, $call) !== false) {
                $this->callAdmins();
                $this->mysqli->close();
                exit();
            }
        }
    }

    function answerMessage($answer)
    {
        foreach ($answer as $a => $data) {

            switch ($data) {
                case "photo":
                    $this->sendTelegram(
                        'sendPhoto',
                        array(
                            'chat_id' => $this->chat_id,
                            'photo' => curl_file_create(__DIR__ . $a)
                        )
                    );
                    break;
                case "text":
                    $this->sendTelegram(
                        'sendMessage',
                        array(
                            'chat_id' => $this->chat_id,
                            'text' => $a
                        )
                    );
                    break;
                case "doc":
                    $this->sendTelegram(
                        'sendDocument',
                        array(
                            'chat_id' => $this->chat_id,
                            'document' => curl_file_create(__DIR__ . $a)
                        )
                    );
                    break;
            }

        }
    }

    function callAdmins()
    {
        $this->sendTelegram(
            'sendMessage',
            array(
                'chat_id' => $this->chat_id,
                'text' => '<b>Мы отправили администраторам сообщение с вашим ником и призывом к помощи!</b>
-----------------------------------------------------------------------------------------------------
<i>Если у вас нет никнейма в телеграм, просьба внести его в настройках аккаунта и вновь нажать <b>[Техподдержка]</b>, иначе менеджеры не смогут с вами связаться.</i>
-----------------------------------------------------------------------------------------------------',
            )
        );

        foreach ($this->admin_chats_id as $admin) {
            $this->sendTelegram(
                'sendMessage',
                array(
                    'chat_id' => $admin,
                    'text' => $this->data['message']['from']['username'] . ' просит о помощи!'
                )
            );
        }
    }

    function setWebHook($token)
    {
        $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        header('Location: https://api.telegram.org/bot' . $token . '/setWebhook?url=' . $url);
    }
}