<?
require_once('api/Simpla.php');
class Interkassa extends Simpla
{
	public function checkout_form($order_id, $button_text = null)
	{
		$order = $this->orders->get_order((int)$order_id);
		$payment_method = $this->payment->get_payment_method($order->payment_method_id);
		$payment_currency = $this->money->get_currency(intval($payment_method->currency_id));
		$cfg = $this->payment->get_payment_settings($payment_method->id);
		$uri = $this->config->root_url.'/payment/Interkassa/';
		$_SESSION['secret_key'] = $cfg['ik_secret_key'];

		$price = round($this->money->convert($order->total_price, $payment_method->currency_id, false), 2);

		$html = '';

		if(isset($_GET['OG'])) switch($_GET['OG']){
			case 'GG':
				if($this->checkIP()){$html.='Ты мошенник! Пшел вон отсюда!';break;}
				if($_POST['ik_inv_st']!=='success'||$_GET['ik_inv_st']!=='success'){$html.='Что-то тут не чисто . . . =_=<br>А ну-ка оплати!';break;}
				$order = $this->orders->get_order(intval($_POST['ik_pm_no']));
				if(empty($order))	return 'Оплачиваемый заказ не найден';
				$method = $this->payment->get_payment_method(intval($order->payment_method_id));
				if(empty($method)) return "Неизвестный метод оплаты";
				$settings = unserialize($method->settings);
				$payment_currency = $this->money->get_currency(intval($method->currency_id));
				if($settings['ik_merchant_id'] !== $_POST['ik_co_id']) return 'Неверный идентификатор кассы';
				if($order->paid) return 'Этот заказ уже оплачен';
				$this->orders->update_order(intval($order->id), array('paid'=>1));
				$this->notify->email_order_user(intval($order->id));
				$this->notify->email_order_admin(intval($order->id));
				$this->orders->close(intval($order->id));
				return '<link href="'.$uri.'assets/ik.css" rel="stylesheet" type="text/css" />
				<div class="ik_block"><img src="'.$uri.'assets/ik_logo.png" width="50%"><br>
				Оплата успешно проведена
				</div>';
				break;
				case 'PG': $html .= 'Ожидается платёж';break;
				case 'BG': $html .= 'Оплата не произошла';break;
			default:

		}
		$desc = 'Оплата заказа №'.$order->id;

		$success_url = $this->config->root_url.'/order/'.$order->url;

		$action_url = "https://sci.interkassa.com/";

		$params = array(
				'ik_am'           => number_format($price, 2, ".", ""),
				'ik_pm_no'        => $order_id,
				'ik_desc'         => "#".$order_id,
				'ik_cur'          => $payment_currency->code,
				'ik_co_id'        => $cfg['ik_merchant_id'],
				'ik_suc_u'        => $success_url.'?OG=GG',
				'ik_pnd_u'				=> $success_url.'?OG=PG',
				'ik_fal_u'        => $success_url.'?OG=BG',
				'ik_exp'          => date("Y-m-d H:i:s", time() + 24 * 3600)#+
		);
		ksort($params, SORT_STRING);

		$params['secret'] = $cfg['secret_key'];
		$signString = implode(':', $params);

		$signature = base64_encode(md5($signString, true));
		unset($params["secret"]);
		$params["ik_sign"] = $signature;

		$paysys = $this->getIkPaymentSystems($cfg['ik_merchant_id'],$cfg['ik_api_id'],$cfg['ik_api_key']);

		$html .= '
			<script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
			<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
			<link href="'.$uri.'assets/ik.css" rel="stylesheet" type="text/css" />
			<script type="text/javascript" src="'.$uri.'assets/ik.js"></script>
		';
		$html .= '
			<div class="ik_block">
				<form action="'.$action_url.'" method="POST" name="vm_interkassa_form" id="ikform">';
		foreach($params as $k=>$v) $html .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';

		$html.='
				</form>
				<button onclick="document.forms.vm_interkassa_form.submit()" class="btn btn-primary" style="display:none">Подтвердить</button>
				<img src="'.$uri.'assets/ik_logo.png" width="50%"><br>';
		if(is_array($paysys) && !empty($paysys)){
			$html.='
					<button type="button" class="btn btn-info btn-lg" data-toggle="modal" data-target=".ik_modal">Выбрать платежную систему</button>
					<div class="modal fade ik_modal" tabindex="-1" role="dialog">
						<div class="modal-dialog modal-lg" role="document">
							<div class="" id="plans">
								<div class="modal-body">
									<h1>1. Выберите удобный способ оплаты<br>2. Укажите валюту<br>3. Нажмите \'Оплатить\'</h1>
									<div class="row">';
			foreach($paysys as $ps=>$info){
				$tnp = '';

				$tnp .= '
										<div class="col-sm-3 text-center payment_system">
											<div class="panel panel-warning panel-pricing">
												<div class="panel-heading">
													<img src="'.$uri.'assets/paysystems/'.$ps.'.png" alt="'.$info['title'].'">
												</div>
												<div class="form-group">
												<div class="input-group">
													<div id="radioBtn" class="btn-group radioBtn">';
				foreach ($info['currency'] as $currency => $currencyAlias)
					$tnp .= '<a class="btn btn-primary btn-sm '.($currency==$shop_cur?'active':'notActive').'" data-toggle="fun" data-title="'.$currencyAlias.'">'.$currency.'</a>';
				$tnp .= '</div>
													<input type="hidden" name="fun" id="fun">
												</div>
											</div>
											<div class="panel-footer">
												<a class="btn btn-block btn-success ik-payment-confirmation" data-title="'.$ps.'" href="#">Оплатить с
													<br>
													<strong>'.$info['title'].'</strong>
												</a>
											</div>
										</div>
									</div>';
				if($ps=='test'&&!$cfg['ik_testmode']) $tnp = '';
				$html.=$tnp;
			}

			$html .= '
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>';
			}
			else $html.= $paysys; // '<button onclick="document.forms.vm_interkassa_form.submit()" class="btn btn-primary">Подтвердить</button>'
		return $html;
	}
	private function getIkPaymentSystems($ik_co_id, $ik_api_id, $ik_api_key){
    $username = $ik_api_id;
    $password = $ik_api_key;
    $remote_url = 'https://api.interkassa.com/v1/paysystem-input-payway?checkoutId=' . $ik_co_id;

    // Create a stream
    $opts = array(
      'http' => array(
        'method' => "GET",
        'header' => "Authorization: Basic " . base64_encode("$username:$password")
      )
    );

    $context = stream_context_create($opts);
    $file = file_get_contents($remote_url, false, $context);
    $json_data = json_decode($file);

    if($json_data->status != 'error'){
      $payment_systems = array();
      foreach ($json_data->data as $ps => $info) {
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
      return $payment_systems;
    }else{
      return '<strong style="color:red;">API connection error!<br>'.$json_data->message.'</strong>';
    }
  }
	private function checkIP(){
	    $ip_stack = array(
	        'ip_begin'=>'91.231.84.141',
	        'ip_end'=>'91.231.84.141'
	    );
	    if(ip2long($_SERVER['REMOTE_ADDR'])<ip2long($ip_stack['ip_begin']) || ip2long($_SERVER['REMOTE_ADDR'])>ip2long($ip_stack['ip_end'])){
	        return false;
	    }
	    return true;
    }
}/* Tim Frio */
