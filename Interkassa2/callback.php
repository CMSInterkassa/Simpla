<?php

// Модуль разработан в компании GateOn предназначен для CMS Simpla 2.3.7
// Сайт разработчикa: www.gateon.net
// E-mail: www@smartbyte.pro

chdir ('../../');
require_once('api/Simpla.php');
$simpla = new Simpla();

//Получение всех нужных настроек
$order = $simpla->orders->get_order(intval($_REQUEST['ik_pm_no']));
$method = $simpla->payment->get_payment_method(intval($order->payment_method_id));	
$settings = unserialize($method->settings);

//Вывод всего ответа Интеркассы в log.txt(в корне сайта) по желанию
// foreach ($_REQUEST as $key => $value) {
// 	$str = $key.' => '.$value;
// 	wrlog($str);
// }
// wrlog('--------');

if(isset($_REQUEST['ik_pw_via']) && $_REQUEST['ik_pw_via'] == 'test_interkassa_test_xts'){
	$secret_key = $settings['ik_test_key'];
} else {
	$secret_key = $settings['ik_secret_key'];
}
//Формирование цифровой подписи из ответа Интеркассы
$dataSet = $_REQUEST;

$request_sign = $dataSet['ik_sign'];

unset($dataSet['ik_sign']);

ksort($dataSet, SORT_STRING); 
array_push($dataSet, $secret_key);  
$signString = implode(':', $dataSet); 
$sign = base64_encode(md5($signString, true)); 

//Только при совпадении подписей и идентификатора кассы осуществиться обновление статуса заказа
if($request_sign == $sign && $_REQUEST['ik_co_id'] == $settings['ik_co_id']){
	//Смена статуса оплаты в админке 
	$simpla->orders->update_order(intval($order->id), array('paid'=>1));
}

$order = $simpla->orders->get_order(intval($_POST['ik_pm_no']));
if(empty($order))
	err('Оплачиваемый заказ не найден');
 
if(empty($method))
	err("Неизвестный метод оплаты");

if($settings['ik_co_id'] !== $_POST['ik_co_id'])
	err('Неверный идентификатор кассы');

if($order->paid)
	err('Этот заказ уже оплачен');

$simpla->notify->email_order_user(intval($order->id));
$simpla->notify->email_order_admin(intval($order->id));
$simpla->orders->close(intval($order->id));

function err($msg)
{
	header($_SERVER['SERVER_PROTOCOL'].' 400 Bad Request', true, 400);
	die($msg);
}
function wrlog($content){
	$file = 'log.txt';
	$doc = fopen($file, 'a');
	file_put_contents($file, PHP_EOL . $content, FILE_APPEND);	
	fclose($doc);
}
