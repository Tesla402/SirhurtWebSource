<?php

$funds2 = file_get_contents("https://sirhurt.net/autobuy/funds.php");


$funds = $funds2;

$data = file_get_contents("autobuys.html");
$data = str_replace("Unknown",$funds,$data);

$funds = (int)preg_replace('/[^0-9]/', '', $funds);


$special = false;

if ($funds < 1000){
$data = str_replace("green","red",$data);
$data = str_replace("Stock","🛑 Stock",$data);


$data = str_replace("https://ashpokeman.selly.store/product/43464cee","",$data);
$data = str_replace("PAYPAL (1k)","OUT OF STOCK",$data);
}

if ($funds < 5000){ //5000 robux out of stock
$data = str_replace("https://ashpokeman.selly.store/product/9a6b62b9","",$data);
$data = str_replace("PAYPAL (5k)","OUT OF STOCK",$data);
}

if ($funds >= 1000 && $funds < 6000){
$data = str_replace("green","#ff9933",$data);
$data = str_replace("Stock","⚠️ Stock",$data);
}

if ($funds >= 6000){
$data = str_replace("Stock","✅️ Stock",$data);
}

echo $data;
?>