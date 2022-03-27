<?php
require_once 'vendor/autoload.php';



$client = new \Google_Client();
$client->setApplicationName('Google Sheets and PHP');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/credentials.json');

$service = new Google_Service_Sheets($client);
$spreadsheetId = "10cUddNXpYHNt49HTLw7B_cZTSvPl_-gd_EvAY7tiKQs";
$read_range="'Ответы на форму (1)'!A1:AD1000";    
$stock_chips_range="'Рынок Машин'!A10:F37";
$chipoperations_range="'Рынок Машин'!A50:H380";
$chipsbuildoperations_range="'Рынок Чипов'!A32:G3000";






//Копируем транзакции передачи чипов
$result = $service->spreadsheets_values->get($spreadsheetId, $read_range);
#print_r($result->getValues());
$chipoperations=array();
foreach($result->getValues() as $row){
  if($row[2]=="Передать чипы" && $row[28]=="Одобрено")
  {
    
    $chipoperation[0]=$row[27];
    $chipoperation[1]=$row[0];
    $chipoperation[2]=$row[1];
    $chipoperation[3]=$row[2];
    $chipoperation[4]=$row[24];
    $chipoperation[5]=$row[25];
    $chipoperation[6]="Исполнена";
    $chipoperations[]=$chipoperation;
    
  }
}


$body = new Google_Service_Sheets_ValueRange([
  'values' => $chipoperations
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $chipoperations_range, $body, $params);


//Считаем склад чипов
$result = $service->spreadsheets_values->get($spreadsheetId, $chipoperations_range);
#print_r($result->getValues());
$stock=array();
$names=array();
//Агрегируем в массив сумму
foreach($result->getValues() as $row){
  $stock[$row[4]]=$row[4];
  $names[$row[4]]=$row[4];
  $stock[$row[4]."_total_".$row[0]]+=$row[5];
  print_r($row);
}
//Вычитаем переданные чипы
foreach($result->getValues() as $row){
  $stock[$row[2]]=$row[2];
  $names[$row[2]]=$row[2];
  $stock[$row[2]."_total_".$row[0]]-=$row[5];
  print_r($row);
}
//print_r($stock); die;
//Учитываем произведенные чипы
$result = $service->spreadsheets_values->get($spreadsheetId, $chipsbuildoperations_range);
//Агрегируем в массив сумму
foreach($result->getValues() as $row){
  $stock[$row[2]]=$row[2];
  $names[$row[2]]=$row[2];
  if($row[4]=='Исполнена'){
    $stock[$row[2]."_total_".$row[0]]+=$row[3];
  }
 // print_r($row); 
}
//print_r($stock); die;

//Переводим массив в таблицу
$stocks=array();
$i=0;
foreach($names as $name){
  $stockrow=array();
  foreach($stock as $rowname => $row){
    if($rowname==$name){
      $stockrow[0]="";
      $stockrow[1]=$stock[$name];
      $stockrow[2]=(int)$stock[$name."_total_1"];
      $stockrow[3]=(int)$stock[$name."_total_1"]+(int)$stock[$name."_total_2"];
      $stockrow[4]=(int)$stock[$name."_total_1"]+(int)$stock[$name."_total_2"]+(int)$stock[$name."_total_3"];
    }
  }
  $stocks[]=$stockrow;
}
print_r($stocks);


$body = new Google_Service_Sheets_ValueRange([
  'values' => $stocks
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $stock_chips_range, $body, $params);

