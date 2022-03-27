<?php
require_once 'vendor/autoload.php';

function build($autobuildoperation, $service, $spreadsheetId, $chipoperations_range, $stock_chips_range){
    $chip_needed_for_one_car=1;
    $check=false;
    $result = $service->spreadsheets_values->get($spreadsheetId, $stock_chips_range);
    #print_r($result->getValues());
    $stocks=array();
    foreach($result->getValues() as $row){
    //Читаем рейндж склада
    if ($row[1]==$autobuildoperation[2]){
      if($autobuildoperation[5]*$chip_needed_for_one_car<=$row[$autobuildoperation[0]+1] ){
        
        //Так как во втором такте чипы из первого такта переходят нужно вычесть чипы из последующих тактов
        if ($autobuildoperation[0]==1){ 
          $row[$autobuildoperation[0]+1]-=$autobuildoperation[5]*$chip_needed_for_one_car;
          $row[$autobuildoperation[0]+2]-=$autobuildoperation[5]*$chip_needed_for_one_car;
          $row[$autobuildoperation[0]+3]-=$autobuildoperation[5]*$chip_needed_for_one_car;
        }
        if ($autobuildoperation[0]==2){ 
          $row[$autobuildoperation[0]+1]-=$autobuildoperation[5]*$chip_needed_for_one_car;
          $row[$autobuildoperation[0]+2]-=$autobuildoperation[5]*$chip_needed_for_one_car;
        }
        if ($autobuildoperation[0]==3){ 
          $row[$autobuildoperation[0]+1]-=$autobuildoperation[5]*$chip_needed_for_one_car;
        }
        $check=true;
        //Поиск по имени и такту нужной строки
        //Сравнение и корректировка
      }
      else{
        $check=false;
      }
    }

      $stock[0]=(string)$row[0];
      $stock[1]=(string)$row[1];
      $stock[2]=(int)$row[2];
      $stock[3]=(int)$row[3];
      $stock[4]=(int)$row[4];
      $stocks[]=$stock;
    }
    //print_r($stocks); die;
    if($check==true){
      $body = new Google_Service_Sheets_ValueRange([
        'values' => $stocks
      ]);
      $params = [
        'valueInputOption' => 'RAW'
      ];
      $update_sheet = $service->spreadsheets_values->update($spreadsheetId, $stock_chips_range, $body, $params);

      return 'Исполнена';
    }

    else return 'Недостаточно чипов';
  

}


$client = new \Google_Client();
$client->setApplicationName('Google Sheets and PHP');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/credentials.json');

$service = new Google_Service_Sheets($client);
$spreadsheetId = "10cUddNXpYHNt49HTLw7B_cZTSvPl_-gd_EvAY7tiKQs";
$read_range="'Ответы на форму (1)'!A1:AD1000";    
$chipsbuildoperations_range="'Рынок Чипов'!A32:G3000";
$stock_chips_range="'Рынок Чипов'!A14:E29";


$chipcost_range="'Рынок Чипов'!K1:K1";  
$result = $service->spreadsheets_values->get($spreadsheetId, $chipcost_range);
foreach($result->getValues() as $row){
  $chipcost=$row[0];
}


//Копируем транзакции производства чипов
$result = $service->spreadsheets_values->get($spreadsheetId, $read_range);
#print_r($result->getValues());
$chipsbuildoperations=array();
foreach($result->getValues() as $row){
  if($row[2]=="Производство чипов" && $row[28]=="Одобрено")
  {
    print_r($row);
    $chipsbuildoperation[0]=$row[27];
    $chipsbuildoperation[1]=$row[0];
    $chipsbuildoperation[2]=$row[1];
    $chipsbuildoperation[3]=$row[23];
    $chipsbuildoperation[4]='Исполнена';
    $chipsbuildoperation[5]=$chipcost*$row[23];
    //$autobuildoperation[6]=build($autobuildoperation, $service, $spreadsheetId, $chipoperations_range, $stock_chips_range);
    $chipsbuildoperations[]=$chipsbuildoperation;
  }
}


$body = new Google_Service_Sheets_ValueRange([
  'values' => $chipsbuildoperations
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $chipsbuildoperations_range, $body, $params);

