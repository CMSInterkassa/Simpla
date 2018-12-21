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

require_once('InterkassaController.php');
$interkassa = new InterkassaController($_POST);
if (isset($_GET['send_sign']) && $_GET['send_sign'] == 1) {
	$interkassa->sendSign();
} else {
	$interkassa->callback();
}