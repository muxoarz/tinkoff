<?php

require 'Tinkoff.php';

/*
   Пример создания платежа и редирект пользователя на сайт банка для оплаты
*/
// настройки выдаваемые банком
$terminal_name = '';
$secret_key = '';
$api_url = 'https://rest-api-test.tcsbank.ru/rest';
// параметры платежа
$amount = 10.05; // сумма в рублях
$description = 'Test payment'; // описание платежа
$email = 'user@email.ru'; // email пользователя
$payment_id = 123445; // id платежа в вашей базе

$tinkoff = new Tinkoff($terminal_name, $secret_key, $api_url);
$payment = $tinkoff->setParam('Amount', intval($amount * 100))
    ->setParam('OrderId', $payment_id)
    ->setParam('Description', $description)
    ->setParam('DATA', 'Email=' . $email)
    ->send('Init');

// в случае успеха редиректим на форму оплаты
if ($payment instanceof StdClass && $payment->Success) {
    header("Location: " . $payment->PaymentURL);
    exit;
} else {
    // undefined error...
    var_dump($payment);
}


/*
   Пример обработки уведомления о платеже
*/
$params = $_POST; // массив параметров от банка присланных в POST
$tinkoff = new Tinkoff($terminal_name, $secret_key, $api_url);
$request_token = $params['Token'];
unset($params['Token']);
if ($tinkoff->setParams($params)->generateToken() != $request_token) {
    throw new ForbiddenHttpException();
}
// проверяем статус и сумму, чтобы совпадала с суммой в базе
if ($params['Status'] == 'AUTHORIZED' && $params['Amount']/100 == $amount_from_db) {
    // сохраняем платеж...
}