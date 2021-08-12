<?php

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

if (isset($_GET['legacykey']))
{
    $legacykey = ($_GET['legacykey']);
    $replace = file_get_contents("login.html");
    $replace = str_replace("INSERTLEGACYKEYHERE", $legacykey, $replace);
    
    echo $replace;
    exit();
}

/* stop ddos attacks from using as much resources */
if (!empty($_SERVER["QUERY_STRING"]))
{
    if ($_SERVER["QUERY_STRING"] != "legacykey" && !preg_match('/\bstopdembois\b/', $_SERVER["QUERY_STRING"]))
    {
        die("Forbidden Request");
    }
}

echo file_get_contents("login.html");
?>