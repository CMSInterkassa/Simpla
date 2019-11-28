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

require_once(dirname(dirname(__DIR__)) . '/api/Simpla.php');
class InterkassaController extends Simpla
{
	protected $data;
	protected $order;
	protected $method;
	protected $settings;
	protected $payment_currency;

	public function __construct($request) {
		$this->data = $request;
		if (!isset($request['ik_pm_no']))
			die('payment_id does not exist');
		$this->order = $this->orders->get_order(intval($request['ik_pm_no']));
		if(empty($this->order))
			die('order does not exist');
		$this->method = $this->payment->get_payment_method(intval($this->order->payment_method_id));
		if(empty($this->method))
			die('method does not exist');
		// Payment method settings
		$this->settings = unserialize($this->method->settings);
		$this->payment_currency = $this->money->get_currency(intval($this->method->currency_id));
	}
	public function callback() {
		if($this->settings['ik_merchant_id'] !== $this->data['ik_co_id'])
			die('bad merchant_id');
		$request_sign = $this->data['ik_sign'];
		$price = round($this->money->convert($this->order->total_price, $this->method->currency_id, false), 2);
		$amount = number_format($price, 2, ".", "");
		if ($this->data['ik_am'] < $amount)
			die('Payment amount mismatch');
		if (isset($this->data['ik_pw_via']) && $this->data['ik_pw_via'] == 'test_interkassa_test_xts') {
			$key = $this->settings['ik_test_key'];
		}
		else {
			$key = $this->settings['ik_secret_key'];
		}
		$sign = $this->createSign($this->data,$key);
		if ($request_sign != $sign)
			die('Payment signature mismatch');
		if($this->order->paid)
			die('This order has already been paid');
		// Set order status paid
		$this->orders->update_order(intval($this->order->id), array('paid'=>1));

		// Write off products
		$this->orders->close(intval($this->order->id));

		// Send notification email
		$this->notify->email_order_user(intval($this->order->id));
		$this->notify->email_order_admin(intval($this->order->id));


		echo 'success';
		//echo 'pending';
	}
	public function sendSign() {
		$sign = $this->createSign($this->data,$this->settings['ik_secret_key']);
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		echo $sign;
		exit;
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