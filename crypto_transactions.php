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
$cryptooperations_range="'Крипта'!A41:G3000";
$stock_cryptooperations_range="'Крипта'!A3:H29";





//Копируем транзакции криптовалют
$result = $service->spreadsheets_values->get($spreadsheetId, $read_range);
#print_r($result->getValues());
$cryptooperations=array();
foreach($result->getValues() as $row){
  if($row[2]=="Перевод криптовалюты" && $row[28]=="Одобрено")
  {    
    $cryptooperation[0]=$row[27];
    $cryptooperation[1]=$row[0];
    $cryptooperation[2]=$row[1];
    $cryptooperation[3]=$row[9];
    $cryptooperation[4]=$row[7];
    $cryptooperation[5]=$row[14];
    $cryptooperation[6]='Исполнена';
    //$autobuildoperation[6]=build($autobuildoperation, $service, $spreadsheetId, $chipoperations_range, $stock_chips_range);
    $cryptooperations[]=$cryptooperation;
  }
}
//die;

$body = new Google_Service_Sheets_ValueRange([
  'values' => $cryptooperations
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $cryptooperations_range, $body, $params);



//Считаем  баланс крипты
$result = $service->spreadsheets_values->get($spreadsheetId, $cryptooperations_range);
#print_r($result->getValues());
$stock=array();
$names=array();
//Агрегируем в массив сумму
foreach($result->getValues() as $row){
  $stock[$row[3]]=$row[3];
  $names[$row[3]]=$row[3];
  $stock[$row[3]."_total_".$row[4]."_".$row[0]]+=$row[5];
  //print_r($row);
}

//Вычитаем переданные чипы
foreach($result->getValues() as $row){
  $stock[$row[2]]=$row[2];
  $names[$row[2]]=$row[2];
  $stock[$row[2]."_total_".$row[4]."_".$row[0]]-=$row[5];
 // print_r($row);
}


/*
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
*/
//Переводим массив в таблицу
$stocks=array();
$i=0;
foreach($names as $name){
  $stockrow=array();
  foreach($stock as $rowname => $row){
    if($rowname==$name){
      $stockrow[0]="";
      $stockrow[1]=$stock[$name];
      $stockrow[2]=(int)$stock[$name."_total_BTC_1"];
      $stockrow[3]=(int)$stock[$name."_total_USDT_1"];
      $stockrow[4]=(int)$stock[$name."_total_BTC_1"]+(int)$stock[$name."_total_BTC_2"];
      $stockrow[5]=(int)$stock[$name."_total_USDT_1"]+(int)$stock[$name."_total_USDT_2"];
      $stockrow[6]=(int)$stock[$name."_total_BTC_1"]+(int)$stock[$name."_total_BTC_2"]+(int)$stock[$name."_total_BTC_3"];
      $stockrow[7]=(int)$stock[$name."_total_USDT_1"]+(int)$stock[$name."_total_USDT_2"]+(int)$stock[$name."_total_USDT_3"];
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
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $stock_cryptooperations_range, $body, $params);
