<?php
require_once 'vendor/autoload.php';



$client = new \Google_Client();
$client->setApplicationName('Google Sheets and PHP');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/credentials.json');

$service = new Google_Service_Sheets($client);
$spreadsheetId = "10cUddNXpYHNt49HTLw7B_cZTSvPl_-gd_EvAY7tiKQs";
$read_range="'Ответы на форму (1)'!A2:AD1000";    
$security_code_range="'Коды игроков'!A2:C1000";
$security_column="'Ответы на форму (1)'!AD2:AE1000";



//Загружаем коды 
$result = $service->spreadsheets_values->get($spreadsheetId, $security_code_range);
#print_r($result->getValues());
$security=array();
foreach($result->getValues() as $row){
    $security[$row[1]]=$row[2];
}






//Читаем cписок действий
$result = $service->spreadsheets_values->get($spreadsheetId, $read_range);
#print_r($result->getValues());
$stock=array();
$names=array();
$check_result=array();
//Агрегируем в массив сумму
foreach($result->getValues() as $pos=>$row){
    $from=$row[1];
    $code=$row[26];
    if ($code==$security[$row[1]]){
      $check_result[$pos]=array(0=>"Код валиден");
    }
    else
    {
      $check_result[$pos]=array(0=>"Ошибка");
    }
    $body = new Google_Service_Sheets_ValueRange([
      'values' => $check_result
    ]);
    $params = [
      'valueInputOption' => 'RAW'
    ];
  }
    $update_sheet = $service->spreadsheets_values->update($spreadsheetId, $security_column, $body, $params);
    die;

    




