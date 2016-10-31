<?php

// Модуль разработан в компании GateOn предназначен для CMS Simpla 2.3.7
// Сайт разработчикa: www.gateon.net
// E-mail: www@smartbyte.pro

require_once('api/Simpla.php');

class Interkassa2 extends Simpla
{	
	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';
		
		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
		$settings = $this->payment->get_payment_settings($payment_method->id);
	
		$price = round($this->money->convert($order->total_price, $payment_method->currency_id, false), 2);
		$desc = '#'.$order->id;
		$success_url = $this->config->root_url.'/order/'.$order->url;
		$payment_settings = unserialize($payment_method->settings);
		$key = $settings['ik_secret_key'];
		
		if($payment_currency->code == 'RUR'){
			$currency = 'RUB';
		}else{
			$currency = $payment_currency->code;
		}

		$arg = array(
		'ik_cur'=>$currency,
		'ik_co_id'=>$settings['ik_co_id'],
		'ik_pm_no'=>intval($order->id),
		'ik_am'=>round($price,2),
		'ik_desc'=>'#'.intval($order->id),
		'ik_suc_url'=> $success_url
		);

		//Формируем цифровую подпись для отправки на Интеркассу
		$dataSet = $arg;
		ksort($dataSet, SORT_STRING);
		array_push($dataSet, $key);
		$signString = implode(':', $dataSet); 
		$sign = base64_encode(md5($signString, true));

		$button = "<form name='payment' method='post' action='https://sci.interkassa.com/' accept-charset='UTF-8'> 
		<input type='hidden' name='ik_co_id' value='".$settings['ik_co_id']."'>
		<input type='hidden' name='ik_pm_no' value='".$order->id."'>
		<input type='hidden' name='ik_cur'   value='".$payment_currency->code."'>
		<input type='hidden' name='ik_am'    value='$price'>
		<input type='hidden' name='ik_desc'  value='$desc'>
		<input type='hidden' name='ik_suc_u'  value='$success_url'>
		<input type='hidden' name='ik_sign'  value='$sign'>
		<input type='submit' name='process'  value='$button_text' class='checkout_button'>
		</form>";
		return $button;
}
}