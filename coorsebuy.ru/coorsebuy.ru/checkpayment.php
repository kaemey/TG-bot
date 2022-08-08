<?php
//Подключение БД
if ($_GET[pass] == md5(coorsebuy)){
    
    $conn = new mysqli("localhost", "kaemey", "KripSan4ik", "bd1");
    $conn->query("UPDATE user SET buy=2 WHERE chat_id = '".$_GET["chat_id"]."'");
    header('Location: https://coorsebuy.ru/bot.php?chat_id='.$_GET["chat_id"].'&addpay='.$_GET[pass]);
    $conn->close();
    exit();
}