<?php

$TG_TOKEN = 'Ваш токен от Bot Father';

//Запросы-ответы. Основные ключи - это ввод от пользователя или от клавиатуры, 
//значение - массив из ответов, где ключ - тело ответа (строка, путь до файла), 
//а значение - метод ответа (текстовый, отправка фото, документа).
//сообщения регистронезависимые (неважно: ПриВЕт или привет)

$responses = array(
    'купить' => array(
        "Для покупки перейдите по ссылке:" => "text"
    ),
    'фото' => array(
        "Ваши фото" => "text",
        "/photos/photo1.jpg" => "photo",
        "/photos/photo2.jpg" => "photo"
    ),
    "документы" => array(
        "Ваши документы" => "text",
        "/docs/passwords.txt" => "doc",
        "/docs/secrets.txt" => "doc"
    )
);

$first_message = "Сообщение для пользователя, что вошёл в первый раз.";

//Запросы помощи от администраторов
$calls_admins = array("Поддержка", " Помогите", "Помощь");

//Название кнопок для клавиатуры
$keyboard = [["Купить", "Фото", "Документы", "Поддержка"]];

//Подключение Базы данных
$host = "localhost";
$username = "username";
$password = "password";
$database = "database";
$table = "users";

//При использовании QIWI API раскомментировать

//Ключи от QIWI API
//$QIWI_SECRET_KEY = '';
//$QIWI_PUBLIC_KEY = '';

//На какое сообщение выдавать ссылку на оплату?
//$qiwi_response = "купить";
//Сумма оплаты
//$qiwi_amount = 100;

//Как отвечать на это сообщение?
//$qiwi_answer = "Ваша ссылка на оплату: " /*  тут будет ссылка */;