<?php
require_once 'vendor/autoload.php';
$bank_total_range="'Фиат'!A3:K10000";
function getBankNameByCountry($country){
  if ($country=="USA") return "USA Bank";
  if ($country=="China") return "China Bank";
  if ($country=="Cnina") return "China Bank";
  if ($country=="Europe") return "Europe Bank";
  
}


$client = new \Google_Client();
$client->setApplicationName('Google Sheets and PHP');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig(__DIR__ . '/credentials.json');

$service = new Google_Service_Sheets($client);

$spreadsheetId = "10cUddNXpYHNt49HTLw7B_cZTSvPl_-gd_EvAY7tiKQs";

$read_range="'Ответы на форму (1)'!A1:AF1000";    
$update_range="'Фиат'!A26:I10000";
$autobuildoperations_range="'Рынок Машин'!H50:N380";
$chipsbuildoperations_range="'Рынок Чипов'!A32:F1000";
$countrybyname_range="'Собственность игроков'!A3:B30";
function getCountryByName($name, $service, $spreadsheetId, $countrybyname_range){
    $result = $service->spreadsheets_values->get($spreadsheetId, $countrybyname_range);
    #print_r($result->getValues());
    $transfers=array();
    foreach($result->getValues() as $row){
      $transfers[$row[1]]=$row[0];
    }
    //print_r($transfers); print_r($name); die;
   return $transfers[$name];
}


$result = $service->spreadsheets_values->get($spreadsheetId, $read_range);
#print_r($result->getValues());
$transfers=array();
foreach($result->getValues() as $row){
  if($row[2]=="Перевод денег" && $row[28]=="Одобрено")
  {
    $transfer[0] = $row[0];  
    $transfer[1] = $row[1];
    $transfer[2] = $row[2];
    $transfer[3] = $row[3];
    $transfer[4] = $row[4];
    $transfer[5] = $row[5];
    $transfer[6] = $row[6];
    $transfer[7] = $row[27];
    $transfer[8] = $row[26];
    $transfers[]=$transfer;
  }
}
//print_r($transfers); die;
$transferswithcomisstion=array();
foreach ($transfers as $transfer){
  $totalcomission=0;
  $tact=$transfer[7];
 // if ($transfer[6]=="-85") {print_r($transfer); die;}
  if($transfer[8]!="666"){
 
      if($transfer[3]==$transfer[4]){ //Внутренний перевод
        
        $newtransfer[0] = $transfer[0];  
        $newtransfer[1] = $transfer[1];
        $newtransfer[2] = "Комиссия за перевод внутри страны";
        $newtransfer[3] = $transfer[4];
        $newtransfer[4] = $transfer[3];
        $newtransfer[5] = getBankNameByCountry($transfer[3]);
        $newtransfer[6] = $transfer[6]*0.1; //комиссия внутри страны
        $newtransfer[7] = $tact;
        $totalcomission+=$transfer[6]*0.1;
        $transferswithcomisstion[]=$newtransfer;
      }
      else if ($transfer[3]!=$transfer[4]){ //Трансграничный перевод
        $newtransfer[0] = $transfer[0];  
        $newtransfer[1] = $transfer[1];
        $newtransfer[2] = "Комиссия за перевод исходящего банка";
        $newtransfer[3] = $transfer[3];
        $newtransfer[4] = $transfer[3];
        $newtransfer[5] = getBankNameByCountry($transfer[3]);
        $newtransfer[6] = $transfer[6]*0.05; //комиссия для банка отправителя
        $totalcomission+=$transfer[6]*0.05;
        $newtransfer[7] = $tact;
        $transferswithcomisstion[]=$newtransfer;
        
        $newtransfer[0] = $transfer[0];  
        $newtransfer[1] = $transfer[1];
        $newtransfer[2] = "Комиссия за перевод входящего банка";
        $newtransfer[3] = $transfer[3];
        $newtransfer[4] = $transfer[4];
        $newtransfer[5] = getBankNameByCountry($transfer[4]);
        $newtransfer[6] = $transfer[6]*0.05; //комиссия для банка получателя
        $totalcomission+=$transfer[6]*0.05;
        $newtransfer[7] = $tact;
        $transferswithcomisstion[]=$newtransfer;

        $newtransfer[0] = $transfer[0];  
        $newtransfer[1] = $transfer[1];
        $newtransfer[2] = "Комиссия swift";
        $newtransfer[3] = $transfer[3];
        $newtransfer[4] = 'Europe';
        $newtransfer[5] = 'Swift';
        $newtransfer[6] = $transfer[6]*0.1; //комиссия для банка получателя
        $totalcomission+=$transfer[6]*0.1;
        $newtransfer[7] = $tact;
        $transferswithcomisstion[]=$newtransfer;
      }
      $transfer[6]=$transfer[6]-$totalcomission;
  }
  $transferswithcomisstion[]=$transfer;
}

//Добавить транзакции обязательные расходы для второго и третьего такта
//Добавить транзакции на производство чипов
$result = $service->spreadsheets_values->get($spreadsheetId, $chipsbuildoperations_range);
#print_r($result->getValues());
$transfers=array();

foreach($result->getValues() as $row){
  //print_r($row); die;
  if ($row[4]=="Исполнена"){
    
    $newtransfer[0] = "";  //Время
    $newtransfer[1] = $row[2];
    $newtransfer[2] = "Затраты на производство чипов";
    $newtransfer[3] = getCountryByName($row[2], $service, $spreadsheetId, $countrybyname_range); //откуда
    $newtransfer[4] = getCountryByName($row[2], $service, $spreadsheetId, $countrybyname_range); //куда
    $newtransfer[5] = "Игротехник"; //Кому
    $newtransfer[6] = $row[5]; //Сумма
    $newtransfer[7] = $tact;
    $newtransfer[8] = "";
    $transferswithcomisstion[]=$newtransfer; 
    
  }
  
}




//Добавить транзакции на производство машин
$result = $service->spreadsheets_values->get($spreadsheetId, $autobuildoperations_range);
#print_r($result->getValues());
$transfers=array();
foreach($result->getValues() as $row){
  if ($row[6]=="Исполнена"){
     //print_r($row); die;
    $newtransfer[0] = "";  //Время
    $newtransfer[1] = $row[2];
    $newtransfer[2] = "Затраты на производство машин";
    $newtransfer[3] = getCountryByName($row[2], $service, $spreadsheetId, $countrybyname_range); //откуда
    $newtransfer[4] = getCountryByName($row[2], $service, $spreadsheetId, $countrybyname_range); //куда
    $newtransfer[5] = "Игротехник"; //Кому
    $newtransfer[6] = $row[5]; //Сумма
    $newtransfer[7] = $tact;
    $newtransfer[8] = "";
    $transferswithcomisstion[]=$newtransfer; 
    
  }
  
}
//Добавить перевод денег в штабквартиру в конце такта

$body = new Google_Service_Sheets_ValueRange([

  'values' => $transferswithcomisstion

]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $update_range, $body, $params);




//Считаем банк
$result = $service->spreadsheets_values->get($spreadsheetId, $update_range);
#print_r($result->getValues());
$stock=array();
$names=array();
//Агрегируем в массив сумму
foreach($result->getValues() as $row){
  
  $stock[$row[5]]=$row[5];
  $names[$row[5]]=$row[5];
  $bank=getBankNameByCountry($row[4]);
  $stock[$row[5]."_total_".$row[7]."_".$bank]+=$row[6];
  print_r($row);
}
print_r($stock);

//print_r($stock); die;
//Вычитаем переданные чипы
foreach($result->getValues() as $row){
  //print_r($row); die; 
  $stock[$row[1]]=$row[1];
  $names[$row[1]]=$row[1];
  $bank=getBankNameByCountry($row[3]);
  $stock[$row[1]."_total_".$row[7]."_".$bank]-=$row[6];
  print_r($row);
}
/*
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
      $stockrow[2]=(int)$stock[$name."_total_1_USA Bank"];
      $stockrow[3]=(int)$stock[$name."_total_1_China Bank"];
      $stockrow[4]=(int)$stock[$name."_total_1_Europe Bank"];
      $stockrow[5]=(int)$stock[$name."_total_1_USA Bank"]+(int)$stock[$name."_total_2_USA Bank"];
      $stockrow[6]=(int)$stock[$name."_total_1_China Bank"]+(int)$stock[$name."_total_2_China Bank"];
      $stockrow[7]=(int)$stock[$name."_total_1_Europe Bank"]+(int)$stock[$name."_total_2_Europe Bank"];
      $stockrow[8]=(int)$stock[$name."_total_1_USA Bank"]+(int)$stock[$name."_total_2_USA Bank"]+(int)$stock[$name."_total_3_USA Bank"];
      $stockrow[9]=(int)$stock[$name."_total_1_China Bank"]+(int)$stock[$name."_total_2_China Bank"]+(int)$stock[$name."_total_3_China Bank"];
      $stockrow[10]=(int)$stock[$name."_total_1_Europe Bank"]+(int)$stock[$name."_total_2_Europe Bank"]+(int)$stock[$name."_total_3_Europe Bank"];
      /*
      $stockrow[3]=(int)$stock[$name."_total_1"]+(int)$stock[$name."_total_2"];
      $stockrow[4]=(int)$stock[$name."_total_1"]+(int)$stock[$name."_total_2"]+(int)$stock[$name."_total_3"];
      */
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
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $bank_total_range, $body, $params);




$autoselloperations_range="'Р/сч+имущество'!A30:T380";
$spreadsheetId="1foqT1ZD0_6LJRewErgmcioP45CYO_-yClQjJD09dwlE";
$body = new Google_Service_Sheets_ValueRange([
  'values' => $stocks
]);
$params = [
  'valueInputOption' => 'RAW'
];
$update_sheet = $service->spreadsheets_values->update($spreadsheetId, $autoselloperations_range, $body, $params);
