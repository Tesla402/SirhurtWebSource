<?php
include($_SERVER['DOCUMENT_ROOT'] . "/autobuy/RobuxHandler.php");
include($_SERVER['DOCUMENT_ROOT'] . "/autobuy/FundsHandler.php");

function HttpGet($url)
{
    $curl = curl_init();

    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_USERAGENT => 'Roblox/WinInet']);

    $resp = curl_exec($curl);

    curl_close($curl);

    return $resp;
}

$pending_limit = 350000;
$funds_information = json_decode(FetchGroupData(FetchMarketInformation()), true);
$funds_information_account = json_decode(FetchTShirtData(FetchMarketInformation()), true);


$pending_funds = $funds_information['backhall'] + $funds_information_account['backhall'];
$current_funds = $funds_information['robux'] + $funds_information_account['robux'];
$total_robux = $pending_funds + $current_funds;

$file = file_get_contents("sellrobux.html");

if ($total_robux > $pending_limit)
{
    $file = str_replace("<!--NOTIFICATION-->", '<a style="color:#FE3838;"><b><h1>🛑WARNING</h1></b>We currently are overstocked on ROBUX.<br>Due to this, we are not currently buying more stock. We currently have ' . $total_robux . ' robux in backstock, please wait until our backstock robux is less than ' . $pending_limit . '. This is to minimize losses in case of a deletion. Thanks for understanding!</a><br><br>', $file);
}

echo $file;
exit();
?>