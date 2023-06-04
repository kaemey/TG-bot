<?php
// composer
require __DIR__ . "/vendor/autoload.php";

//киви
const SECRET_KEY = 'SECRET_KEY от QIWI';
const PUBLIC_KEY = 'PUBLIC_KEY от QIWI';

$billPayments = new Qiwi\Api\BillPayments(SECRET_KEY);

//Подключение БД
$conn = new mysqli("localhost", "BD", "password", "botbd");
// Подлкючение запроса от ТГ
$data = file_get_contents('php://input');
$data = json_decode($data, true);
$reply = array();
define('TOKEN', 'Ваш токен от Bot Father');
define('tgapi', 'https://api.telegram.org/bot' . TOKEN . '/');

// Функция вызова методов API.
function sendTelegram($method, $response, $delete = false)
{
    global $reply;
    global $conn;
    
    if ($reply) $response['reply_markup'] = $reply;
    
    $response['parse_mode'] = "HTML";
	$ch = curl_init(tgapi . $method);  
	curl_setopt($ch, CURLOPT_POST, 1);  
	curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$res = curl_exec($ch);
	curl_close($ch);
    
    $msg = json_decode($res, true);
    
    $search = $conn->query("SELECT last, chat_id FROM user");
        
    if ($search->num_rows > 0) {
            //Поиск в БД id чата
            while($row = $search->fetch_assoc()) {
                if ($row["chat_id"] == $msg['result']['chat']['id']) 
                {
                    $deleting = $row['last'];
                    $conn->query("UPDATE user SET last='' WHERE chat_id='".$msg['result']['chat']['id']."'");
                }
            }
    }
        
    if ($deleting)
    {
        sendTelegram(
        		'deleteMessage', 
        		array(
        			'chat_id' => $msg['result']['chat']['id'],
        			'message_id' => $deleting
        		)
    	);
    }
    
    if ($msg['ok'] and $delete)
    {
        $conn->query("UPDATE user SET last='".$msg['result']['message_id']."' WHERE chat_id='".$msg['result']['chat']['id']."'");
    }
    
	return $res;
}

if($data['out'] == 'addpayment')
{
    sendTelegram(
    			'sendMessage', 
    			array(
    				'chat_id' => $data["chat_id"],
    				'text' => "Оплата прошла! 
Доступ к Базе курсов доступен по ссылке: https://telegra.ph/Baza-kursov-po-Wildberries-06-14"
    			)
    );
    $conn->close();
    exit();
}

$buy = 0;
$new = false;
//База id чатов админов
$admins = array();

// Ищем в базе ники и id чатов
$result = $conn->query("SELECT username, chat_id, buy, admin FROM user");
$found = false;

if ($result->num_rows > 0) {
    //Поиск в БД id чата
    while($row = $result->fetch_assoc()) {
        if ($row["admin"] == 1) { array_push($admins, $row["chat_id"]); }
        if ($row["chat_id"] == $data['message']['chat']['id']) {
            $found = true;
            $buy = $row["buy"];
        }
    }
}

//Записываем, если не нашли ника и id
if (!$found)
{
    if (!empty($data['message']['chat']['id']))
    {
        $new = true;
        $conn->query("INSERT INTO user (chat_id, username, admin) 
        VALUES ('".$data['message']['chat']['id']."','".$data['message']['from']['username']."','0')");
    }
    else {$conn->close(); exit();}
}

// Создание клавиатуры
Switch ($buy)
{
    Case 0:
        $b1 = "[Купить доступ]";
        break;
    Case 1:
        $b1 = "[Проверить доступ]";
        break;         
    Case 2:
        $b1 = "[Получить ссылку]";
        break;         
}

$keyboard = array(array($b1,"[Подробнее]","[Доказательства]","[Техподдержка]") );
$resp = array("keyboard" => $keyboard,"resize_keyboard" => true,"one_time_keyboard" => true);
$reply = json_encode($resp);

if ($new)
{
    sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => "Добро пожаловать в Бота, что поможет вам приобрести доступ к Базе курсов по Wildberries!
				
Управлять Ботом можно с помощью клавиатуры внизу экрана."
			)
		);
	$conn->close();	
	exit();
}
 
// Прислали фото.
/*
if (!empty($data['message']['photo'])) {
	$photo = array_pop($data['message']['photo']);
	$res = sendTelegram(
		'getFile', 
		array(
			'file_id' => $photo['file_id']
		)
	);
	
	$res = json_decode($res, true);
	if ($res['ok']) {
		$src = 'https://api.telegram.org/file/bot' . TOKEN . '/' . $res['result']['file_path'];
		$dest = __DIR__ . '/' . time() . '-' . basename($src);
 
		if (copy($src, $dest)) {
			sendTelegram(
				'sendMessage', 
				array(
					'chat_id' => $data['message']['chat']['id'],
					'text' => 'Фото сохранено'
				)
			);
			
		}
	}
	$conn->close();
	exit();	
}

// Прислали файл.

if (!empty($data['message']['document'])) {
    
	$res = sendTelegram(
		'getFile', 
		array(
			'file_id' => $data['message']['document']['file_id']
		)
	);
	
	$res = json_decode($res, true);
	if ($res['ok']) {
		$src = 'https://api.telegram.org/file/bot' . TOKEN . '/' . $res['result']['file_path'];
		$dest = __DIR__ . '/' . time() . '-' . $data['message']['document']['file_name'];
 
		if (copy($src, $dest)) {
			sendTelegram(
				'sendMessage', 
				array(
					'chat_id' => $data['message']['chat']['id'],
					'text' => 'Файл сохранён'
				)
			);	
		}
	}
	
	$conn->close();
	exit();	
}
*/
 
// Ответы на текстовые сообщения.
if (!empty($data['message']['text'])) {
	$text = $data['message']['text'];

    // Ответ на Купить
	if ( (mb_stripos($text, 'купить') !== false) or (mb_stripos($text, '/buy') !== false) or (mb_stripos($text, 'доступ') !== false) or (mb_stripos($text, 'получить') !== false) ) {
        Switch ($buy)
        {
            case 0:
                
                $bill = $data['message']['chat']['id'].'-'.rand(0,50).rand(0,50);
                
                $params = [
                  'publicKey' => PUBLIC_KEY,
                  'amount' => 990,
                  'comment' => $bill,
                  'billId' => $bill
                ];
                
                $conn->query("UPDATE user SET buy=1 WHERE chat_id = '".$data['message']['chat']['id']."'");
                
                $link = $billPayments->createPaymentForm($params);
                
                $conn->query("INSERT INTO bills (bill, chat_id) 
                VALUES ('".$link."','".$data['message']['chat']['id']."')");                
                
                $text = '<b>Оплатить доступ к Базе курсов можно по следующей ссылке: <a href="'.$link.'">Ссылка для оплаты QIWI</a>
После оплаты вам сразу же сюда придёт ссылка с доступом к Базе Курсов.</b>';
                break;
            case 1:
                $result = $conn->query("SELECT bill FROM bills WHERE chat_id='".$data['message']['chat']['id']."'");
                
                if ($result->num_rows > 0) {
                    //Поиск в БД id чата
                    while($row = $result->fetch_assoc()) {
                        
                       $pay_link = $row['bill'];
                        
                    }
                }
                
                $text = '<b>Для оплаты перейдите по ссылке: </b><a href="'.$pay_link.'">Ссылка для оплаты QIWI</a>';
                break;
            case 2:
                $text = '<b>Доступ к Базе курсов доступен по ссылке:</b> https://telegra.ph/Baza-kursov-po-Wildberries-06-14';
                break;
        }
        
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => $text
			)
		);
        $conn->close();
		exit();	
		
    }
 
    // Ответ на Подробнее
	if ( (mb_stripos($text, 'подробнее') !== false) or (mb_stripos($text, 'расскажи') !== false) or (mb_stripos($text, 'курс') !== false)  or (mb_stripos($text, '/what') !== false) ) {
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => '<b>В Базе имеется более 60 курсов от разных спикеров, среди которых есть немалоизвестные</b> <i>Лео Шевченко, Азат Шакуров, Михаил Орлов, Ольга Бохан</i> и многие другие.
-----------------------------------------------------------------------------------------------------				
<b>В курсах представлены не только видеоуроки, но также и приложения к ним в виде Excel-таблиц, Фото, различных файлов, документов и баз поставщиков.</b>
-----------------------------------------------------------------------------------------------------
<b>И всё это в вашем распоряжении всего за 990 рублей!</b>',
			)
		);
		sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/podrobnee/1.png')
			)
		);
 		sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/podrobnee/2.png')
			)
		);
		 sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/podrobnee/3.png')
			)
		);
		$conn->close();
		exit();	
    } 
 
    // Ответ на Доказательства
	if ((mb_stripos($text, 'Доказательства') !== false) or (mb_stripos($text, 'пруф') !== false) or (mb_stripos($text, 'кинете') !== false) or (mb_stripos($text, 'скам') !== false)  or (mb_stripos($text, '/proofs') !== false)) {
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => '<b>Нажав [Подробнее], вы можете ознакомиться со скринами из самой Базы курсов.</b> Также ранее мы работали с клиентами напрямую, не через бота, <b>скидываем переписки:</b>

<i>Бот скидывает случайные 5 скриншотов - они могут повторяться. Нажав ещё раз [Доказательства], вы можете увидеть и другие скриншоты.</i>',
			)
		);
		sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/proofs/'.rand(1,12).'.jpg')
			)
		);
 		sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/proofs/'.rand(1,12).'.jpg')
			)
		);
		 sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/proofs/'.rand(1,12).'.jpg')
			)
		);
		 sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/proofs/'.rand(1,12).'.jpg')
			)
		);
		 sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/proofs/'.rand(1,12).'.jpg')
			)
		);
		$conn->close();
		exit();	
    }
 
    // Ответ на Техподдержка
	if ( (mb_stripos($text, 'техподдержка') !== false) or (mb_stripos($text, 'помощь') !== false) or (mb_stripos($text, 'помог') !== false) or (mb_stripos($text, '/support') !== false) ) {
	
		date_default_timezone_set("Europe/Moscow");
		
		if ( ((int) date("H") > 22) or ((int) date("H") < 9) )
	    {
	        sendTelegram(
			'sendMessage', 
    			array(
    				'chat_id' => $data['message']['chat']['id'],
    				'text' => '<b>Режим работы менеджеров с 9:00 до 22:00 по МСК. 
Пожалуйста, дождитесь начала рабочего дня и после нажмите [Техподдержка].</b>',
    			)
    		);
	    }
	    else
	    {
		    sendTelegram(
			'sendMessage', 
    			array(
    				'chat_id' => $data['message']['chat']['id'],
    				'text' => '<b>Мы отправили администраторам сообщение с вашим ником и призывом к помощи!</b>
-----------------------------------------------------------------------------------------------------
<i>Если у вас нет никнейма в телеграм, просьба внести его в настройках аккаунта и вновь нажать <b>[Техподдержка]</b>, иначе менеджеры не смогут с вами связаться.</i>
-----------------------------------------------------------------------------------------------------',
    			)
    		);
    		
        	foreach($admins as $admin)
            {
                sendTelegram(
        			'sendMessage', 
        			array(
        				'chat_id' => $admin,
        				'text' => $data['message']['from']['username'].' просит помощи!'
        			)
        		);
            }
	    }
        
		$conn->close();
		exit();	
    }  
	
	if (mb_stripos($text, 'привет') !== false) {
		sendTelegram(
			'sendMessage', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'text' => '<b>Доброго времени суток!</b>',
			)
		);
		
        $conn->close();
		exit();	

	}  	
	
	// Отправка фото.
	if (mb_stripos($text, 'фото') !== false) {
		sendTelegram(
			'sendPhoto', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'photo' => curl_file_create(__DIR__ . '/torin.jpg')
			)
		);
		
		$conn->close();
		exit();	
	}
 
	// Отправка файла.
	if (mb_stripos($text, 'файл') !== false) {
		sendTelegram(
			'sendDocument', 
			array(
				'chat_id' => $data['message']['chat']['id'],
				'document' => curl_file_create(__DIR__ . '/example.xls')
			)
		);
		
        $conn->close();
		exit();	
	}	

}

$conn->close();
