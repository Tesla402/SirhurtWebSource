<?php
/* Robux Handler */
include_once ($_SERVER['DOCUMENT_ROOT'] . "/autobuy/RobuxHandler.php");
include_once ($_SERVER['DOCUMENT_ROOT'] . "/autobuy/FundsHandler.php");
include_once ($_SERVER['DOCUMENT_ROOT'] . "/asshurt/update/v4/SirHurtVersionHandler.php");
require($_SERVER['DOCUMENT_ROOT'] . "/antiddos.php");

function GetIP() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}


/* Allow first request to skip ddos check page. If requests exceed more than 1 per minute, check. */
$history = Fetch_Connection_History(GetIP());
if ($history[2] > 1)
{
    require($_SERVER['DOCUMENT_ROOT'] . "/antiddoshandler.php");
}

function HttpGet($url)
{
    $curl = curl_init();

    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_URL => $url, CURLOPT_USERAGENT => 'Roblox/WinInet']);

    $resp = curl_exec($curl);

    curl_close($curl);

    return $resp;
}

if (isset($_GET['ref']))
{
    $cookie_expires_on = time() + (86400 * 5);
    setcookie("_ASSHURTREFERRAL", $_GET['ref'], $cookie_expires_on, "/", "sirhurt.net"); // 86400 = 1 day
}

/*$data = file_get_contents("site.html");
$data = str_replace("InfoAboutThisStuff","Asshurt has been used " . file_get_contents("https://sirhurt.net/asshurt/logins.php") . " times since September 2017.",$data);
$data = str_replace("CoolWhitelistInfo","Asshurt has " . file_get_contents("https://sirhurt.net/asshurt/whitelists.php") . " whitelisted users.",$data);*/

$data = file_get_contents("site_new3.html"); //site_new.html

/* Begin Robux Funds Check */
$funds_information = json_decode(FetchGroupData(FetchMarketInformation()), true);

$funds2 = (int)preg_replace('/[^0-9]/', '', $funds_information['robux']);
$funds_account = (int)preg_replace('/[^0-9]/', '', $funds_information['robux_direct']);

$funds = $funds2 + $funds_account;


$replace = str_replace("STOCKS", $funds, $data);
$replace = str_replace("Backorder", $funds_information['backhall'] . " GROUP Pending", $replace);
$replace = str_replace("AccountBOrder", $funds_information['backhall_direct'] . " Account Funds Pending", $replace);

$replace = str_replace("GROUP_FUNDS_HERE", $funds_information['robux'], $replace);
$replace = str_replace("TSHIRT_FUNDS_HERE", $funds_information['robux_direct'], $replace);


if (Is_SirHurt_Updated() == false)
{
    $replace = str_replace("SirHurt is currently working<br>&amp; up-to-date", "SirHurt is not currently working<br>&amp; not up-to-date", $replace);
    $replace = str_replace("80,211,35,0.6", "244,26,26,0.6", $replace);
}
            

if ($funds < 500)
{
    $replace = str_replace("green", "red", $replace);
    $replace = str_replace("Unknown", "🛑 Stock", $replace);
}
else
{
    $replace = str_replace("#f40b1d", "#60f060", $replace);
}
if ($funds >= 500 && $funds < 6000)
{
    $replace = str_replace("green", "#ff9933", $replace);
    $replace = str_replace("Unknown", "⚠️ Stock", $replace);
}
if ($funds >= 6000)
{
    $replace = str_replace("Unknown", "✅️ Stock", $replace);
}
/* End Robux Funds Check */

/* System Message */
$system_message = FetchMarketInformation() [2];
if (strlen($system_message) > 4 && $system_message != "NONE")
{
    //site_new.html $html_code_system_message = "<div style='vertical-align:middle;background-color:#663030;width:100%;height:32px;display: flex; align-items: center;justify-content: center;'><svg style='margin-bottom:3px;' width='18px' height='18px' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><path style='fill:white;' d='M12 5.99L19.53 19H4.47L12 5.99M12 2L1 21h22L12 2zm1 14h-2v2h2v-2zm0-6h-2v4h2v-4z'></path></svg><font face='Arial' style='color:white;'>&nbsp;INSERTMESSAGEHERE</font></div>";
    $html_code_system_message = "<div class='alert alert-success' role='alert' style='font-size: 15px;text-align: center;color: rgb(255,255,255);background: rgb(166,56,56);border: 1px solid rgb(166,56,56);border-left-style: none;border-top-left-radius: 0px;margin-bottom: -10px;height: 32px;padding-bottom: 16px;padding-top: 5px;'><span style='height: auto;margin: 0px;margin-top: 0px;margin-bottom: 0px;margin-right: 0px;padding-bottom: 0px;padding-top: 0px;'><strong>INSERTMESSAGEHERE</strong></span></div>";
    if (preg_match('/-WARNING-/', $system_message))
    {
        $system_message = str_replace("-WARNING-", "", $system_message);
        $html_code_system_message = str_replace("#663030", "#9b8f42", $html_code_system_message);
    }
    
    if (preg_match('/-ORANGE-/', $system_message))
    {
        $system_message = str_replace("-ORANGE-", "", $system_message);
        $html_code_system_message = str_replace("#663030", "#7d5420", $html_code_system_message);
    }

    $html_code_system_message = str_replace("INSERTMESSAGEHERE", $system_message, $html_code_system_message);

    $replace = str_replace("<!-- SYSTEM_MESSAGE -->", $html_code_system_message, $replace);
}
/* End System Message */

/*
$data = str_replace("InfoAboutThisStuff","",$data);
$data = str_replace("CoolWhitelistInfo","",$data);
*/

echo $replace;

?>
