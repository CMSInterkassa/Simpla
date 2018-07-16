<?php
/**
 * Interkassa payment module
 *
 * @copyright 	2018 Sviatoslav Patenko
 * @link 		https://marat.ua/
 * @author 		Sviatoslav Patenko
 * @decription	The module was created in cooperation with Interkassa.
 *
 * IPN Script for Interkassa
 *
 */
require_once('api/Simpla.php');
class Interkassa extends Simpla
{
	public function checkout_form($order_id, $button_text = null)
	{
		if(empty($button_text))
			$button_text = 'Перейти к оплате';
		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));

		$config = $this->payment->get_payment_settings($payment_method->id);
		$price = round($this->money->convert($order->total_price, $payment_method->currency_id, false), 2);

		$success_url = $this->config->root_url.'/order/'.$order->url;
		$callback_url = $this->config->root_url.'/payment/Interkassa/callback.php';
		$path = "/files/interkassa/";

		$action_url = "https://sci.interkassa.com/";
		$params = array(
				'ik_co_id'	=> $config['ik_merchant_id'],
				'ik_pm_no'	=> $order_id,
				'ik_am'		=>  number_format($price, 2, ".", ""),
				'ik_cur'	=> $payment_currency->code,
				'ik_desc'	=> "#".$order_id,
				'ik_suc_u'	=> $success_url,
				'ik_fal_u'	=> $success_url,
				'ik_pnd_u'	=> $success_url,
				'ik_ia_u'	=> $callback_url,
		);
		if ($config['test_mode']) {
			$params["ik_pw_via"] ='test_interkassa_test_xts';
		} elseif ($config["api_mode"]) {
			$params["ik_act"] ='payways';
			$params["ik_int"] ='json';
			$params["payments_systems"] =$this->getPaymentsAPI($params['ik_co_id'], $config['api_id'], $config['api_key']);

		}
		$params["ik_sign"] = $this->createSign($params,$config['ik_secret_key']);
		ob_start();

		include(__DIR__."/ik_payment_form.tpl");

		$html = ob_get_contents();

		ob_end_clean();
		return $html;
	}
	public function getPaymentsAPI($ik_co_id, $api_id, $api_key) {
		$payment_systems = array();
		$host = "https://api.interkassa.com/v1/paysystem-input-payway?checkoutId=" . $ik_co_id;
		$username = $api_id;
		$password = $api_key;
		$ch = curl_init($host);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:Basic ' . base64_encode("$username:$password")));
		$response = curl_exec($ch);
		if(isset($response) && $response) {
			$returnInter = json_decode($response);
			if ($returnInter->status == "ok" && $returnInter->code == 0) {
				$payways = $returnInter->data;
			}
		}
		if (isset($payways) && $payways) {
			foreach ($payways as $ps => $info) {
				$payment_system = $info->ser;
				if (!array_key_exists($payment_system, $payment_systems)) {
					$payment_systems[$payment_system] = array();
					foreach ($info->name as $name) {
						if ($name->l == 'en') {
							$payment_systems[$payment_system]['title'] = ucfirst($name->v);
						}
						$payment_systems[$payment_system]['name'][$name->l] = $name->v;
					}
				}
				$payment_systems[$payment_system]['currency'][strtoupper($info->curAls)] = $info->als;
			}
		}
		return $payment_systems;
	}
	private function createSign($data, $secret_key) {
		if (!empty($data['ik_sign'])) unset($data['ik_sign']);

		$dataSet = array();
		foreach ($data as $key => $value) {
			if (!preg_match('/ik_/', $key)) continue;
			$dataSet[$key] = $value;
		}

		ksort($dataSet, SORT_STRING);
		array_push($dataSet, $secret_key);
		$signString = implode(':', $dataSet);
		$sign = base64_encode(md5($signString, true));
		return $sign;
	}

}
