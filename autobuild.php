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
$stock_chips_range="'Рынок Машин'!A10:F15";
$stock_cars_range="'Рынок Машин'!F10:K15";
$chipoperations_range="'Рынок Машин'!A50:F380";
$autobuildoperations_range="'Рынок Машин'!H50:N380";
$autoselloperations_range="'Рынок Машин'!O50:T380";
$autocost_range="'Рынок Машин'!K1:K1";  
$result = $service->spreadsheets_values->get($spreadsheetId, $autocost_range);
foreach($result->getValues() as $row){
  $autocost=$row[0];
}




//Копируем транзакции производства машин
$result = $service->spreadsheets_values->get($spreadsheetId, $read_range);
#print_r($result->getValues());
$autobuildoperations=array();
foreach($result->getValues() as $row){
  if($row[2]=="Производство машин" && $row[28]=="Одобрено")
  {
    //print_r($row);
    $autobuildoperation[0]=$row[27];
    $autobuildoperation[1]=$row[0];
    $autobuildoperation[2]=$row[1];
    $autobuildoperation[3]=$autocost*$row[21];
    $autobuildoperation[4]=$row[20];
    $autobuildoperation[5]=$row[21];
    $autobuildoperation[6]=build($autobuildoperation, $service, $spreadsheetId, $chipoperations_range, $stock_chips_range);
    $autobuildoperations[]=$autobuildoperation;
  }
}


$body = new Google_Service_Sheets_ValueRange([
  'values' => $autobuildoperations
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $autobuildoperations_range, $body, $params);



//Считаем склад машин
$result = $service->spreadsheets_values->get($spreadsheetId, $autobuildoperations_range);
#print_r($result->getValues());
$stock=array();
$names=array();
//Агрегируем в массив сумму
foreach($result->getValues() as $row){
  $stock[$row[2]]=$row[2];
  $names[$row[2]]=$row[2];
  if($row[6]=='Исполнена'){
    $stock[$row[2]."_total_".$row[0]]+=$row[5];
  }
  //print_r($row); die;
}


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
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $stock_cars_range, $body, $params);
