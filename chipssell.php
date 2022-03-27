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
$stock_chips_range="'Рынок Машин'!A10:F29";
$chipoperations_range="'Рынок Машин'!A50:F380";
$chipsselloperations_range="'Рынок Чипов'!H32:W380";
$market_fact_range="'Рынок Чипов'!E4:H6";
$market_size_range="'Рынок Чипов'!A4:D6";

function get_market_fact_by_country($country, $tact, $service, $spreadsheetId, $market_fact_range){
  
  $result = $service->spreadsheets_values->get($spreadsheetId, $market_fact_range);
  //print_r($result->getValues()); die;
    //Проверяем что товара достаточно на складе
    foreach($result->getValues() as $row){
      if($row[0]==$country) return $row[$tact];
    }
    return 0;
}
function get_market_size_by_country($country, $tact, $service, $spreadsheetId, $market_size_range){
 
  $result = $service->spreadsheets_values->get($spreadsheetId, $market_size_range);
    
    //Проверяем что товара достаточно на складе
    foreach($result->getValues() as $row){
      if($row[0]==$country) return $row[$tact];
    }
    return 0;
}

function sell($autobuildoperation, $service, $spreadsheetId, $stock_chips_range, $market_fact_range, $market_size_range){
  $check=false;
  $message="";
  $result = $service->spreadsheets_values->get($spreadsheetId, $stock_chips_range);
  #print_r($result->getValues());
  $stocks=array();

  //Проверяем что товара достаточно на складе
  foreach($result->getValues() as $row){
  //Читаем рейндж склада
  if ($row[1]==$autobuildoperation[2]){
    //if($row[1]=="Nvidia"){ print_r($row); die; }
    if($autobuildoperation[4]<=$row[$autobuildoperation[0]+1] ){
      //Так как во втором такте машины из первого такта переходят нужно вычесть машины из последующих тактов
      if ($autobuildoperation[0]==1){
        $row[$autobuildoperation[0]+1]-=$autobuildoperation[4];
        $row[$autobuildoperation[0]+2]-=$autobuildoperation[4];
        $row[$autobuildoperation[0]+3]-=$autobuildoperation[4];
      }
      if ($autobuildoperation[0]==2){
        $row[$autobuildoperation[0]+1]-=$autobuildoperation[4];
        $row[$autobuildoperation[0]+2]-=$autobuildoperation[4];
      }
      if ($autobuildoperation[0]==3){
        $row[$autobuildoperation[0]+1]-=$autobuildoperation[4];
      }
      
      $check=true;
      $message="Исполнена";
      //Поиск по имени и такту нужной строки
      //Сравнение и корректировка
    }
    else{
      $check=false;
      $message="Недостаточно чипов на складе";
    }
  }

    $stock[0]=(string)$row[0];
    $stock[1]=(string)$row[1];
    $stock[2]=(int)$row[2];
    $stock[3]=(int)$row[3];
    $stock[4]=(int)$row[4];
    $stocks[]=$stock;
 
  }

  


  $country=$autobuildoperation[3];
  $tact=$autobuildoperation[0];




  //Подгружаем факт
  $fact=get_market_fact_by_country($country, $tact, $service, $spreadsheetId, $market_fact_range);
  //Подгружаем емкость рынка
  $market_size=get_market_size_by_country($country, $tact, $service, $spreadsheetId, $market_size_range);
  //print_r($autobuildoperation); die;
  //Проверяем что вписываемся в емкость рынка на этом такте
  if ($market_size-$fact<=0){
    $message="Превышена емкость рынка.";
  }


    //Обновляем склад
    //print_r($stocks); die;
    if($check==true){
      $body = new Google_Service_Sheets_ValueRange([
        'values' => $stocks
      ]);
      $params = [
        'valueInputOption' => 'RAW'
      ];
      $update_sheet = $service->spreadsheets_values->update($spreadsheetId, $stock_chips_range, $body, $params);


      if($check==true)  {
            //Обновить факт для операции autobuild
            $result = $service->spreadsheets_values->get($spreadsheetId, $market_fact_range);
            $facts=array();
            foreach($result->getValues() as $row){
            $factvalue=array();
            if ($row[0]==$autobuildoperation[3]){
                $row[$autobuildoperation[0]]+=$autobuildoperation[4]; 
            }
              $factvalue[0]=(string)$row[0];
              $factvalue[1]=(int)$row[1];
              $factvalue[2]=(int)$row[2];
              $factvalue[3]=(int)$row[3];
              $facts[]=$factvalue;
            }
            $body = new Google_Service_Sheets_ValueRange([
              'values' => $facts
            ]);
            $params = [
              'valueInputOption' => 'RAW'
            ];
            $update_sheet = $service->spreadsheets_values->update($spreadsheetId, $market_fact_range, $body, $params);
        
        }


      return $message;
    }

    else return $message;

  }



//Сбросим факт продаж
$facts=array(array('USA',0,0,0), array('China',0,0,0), array('Europe',0,0,0));
$body = new Google_Service_Sheets_ValueRange([
  'values' => $facts
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $market_fact_range, $body, $params);


//Копируем транзакции продаджи чипов
$result = $service->spreadsheets_values->get($spreadsheetId, $read_range);
#print_r($result->getValues());
$autoselloperations=array();

//Сортируем транзакции по ставке
$values=$result->getValues();
usort($values, function($a, $b){
  return ((int)$a[17] - (int)$b[17]);
});

foreach($values as $row){
  //print_r($row);
  if($row[2]=="Продать на рынок" && $row[15]=="Чипы" && $row[28]=="Одобрено")
  {
    $autoselloperation[0]=$row[27];
    $autoselloperation[1]=$row[0];
    $autoselloperation[2]=$row[1];
    $autoselloperation[3]=$row[19];
    $autoselloperation[4]=$row[16];
    $autoselloperation[5]=$row[17];


    $country=$autoselloperation[3];
    $tact=$autoselloperation[0];
    //Подгружаем факт
    $fact=get_market_fact_by_country($country, $tact, $service, $spreadsheetId, $market_fact_range);
    //Подгружаем емкость рынка
    $market_size=get_market_size_by_country($country, $tact, $service, $spreadsheetId, $market_size_range);
    //print_r($autobuildoperation); die;
    //Корректируем факт в заявке
    if ($market_size-$fact<=$autoselloperation[4]){
      $autoselloperation[4]=$market_size-$fact;
    }
   
   

    $autoselloperation[6]=sell($autoselloperation, $service, $spreadsheetId, $stock_chips_range, $market_fact_range, $market_size_range);
    $autoselloperation[7]=$autoselloperation[4]*$autoselloperation[5];
    $autoselloperation[8]=$row[16];
    $autoselloperations[]=$autoselloperation;
  }
}




$body = new Google_Service_Sheets_ValueRange([
  'values' => $autoselloperations
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $chipsselloperations_range, $body, $params);

