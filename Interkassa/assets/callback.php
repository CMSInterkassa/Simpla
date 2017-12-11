<?
if($_SERVER['REQUEST_METHOD']!='POST') die();

function IkSignFormation($data, $secret_key){
  if (!empty($data['ik_sign'])) unset($data['ik_sign']);

  $dataSet = array();
  foreach ($data as $key => $value) {
    if (!preg_match('/ik_/', $key)) continue;
    $dataSet[$key] = $value;
  }

  ksort($dataSet, SORT_STRING);
  array_push($dataSet, $secret_key);
  $arg = implode(':', $dataSet);
  $ik_sign = base64_encode(md5($arg, true));

  return $ik_sign;
}
function getAnswerFromAPI($data){
  $ch = curl_init('https://sci.interkassa.com/');
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = curl_exec($ch);
  #file_put_contents(__DIR__.'/gg.txt',json_encode($result,JSON_PRETTY_PRINT),FILE_APPEND);
  return $result;
}
switch ($_GET['nYg']) {
  case 'nYs':
    $tmp = json_encode(array('sign'=>IkSignFormation($_POST,$_GET['nYsk'])));
    #file_put_contents(__DIR__.'/gg.txt',$tmp,FILE_APPEND);
    echo $tmp;
    break;
  case 'nYa':
    $sign = IkSignFormation($_POST,$_GET['nYsk']);
    $_POST['ik_sign'] = $sign;
    $tmp = getAnswerFromAPI($_POST);
    echo json_encode($tmp);
    #file_put_contents(__DIR__.'/gg.txt',$tmp,FILE_APPEND);
  break;
  default:die();
}
