<?php

function HgHvgtdbGJ() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}

function User_Passed_Inspection($IP)
{
    $cookie_expires_on = time() + (10 * 365 * 24 * 60 * 60);
    setcookie("_SIRHURTSHIELD", md5($IP . "VeZBsO9181"), $cookie_expires_on, "/", "sirhurt.net");
}

function Return_Valid_Cookie($IP)
{
    return md5($IP . "VeZBsO9181");
}

function Check_Valid_Cookie($IP, $Cookie)
{
    $Correct_Cookie = md5($IP . "VeZBsO9181");
    
    if ($Correct_Cookie == $Cookie)
    {
        return true;
    }
    
    return false;
}

$Header_Check = "NONE";

if (isset($_COOKIE["_SIRHURTSHIELD"]))
{
    $Header_Check = $_COOKIE["_SIRHURTSHIELD"];
}

if (isset($_GET['stopdembois']))
{
   $Header_Check = $_GET['stopdembois'];
   
   if (Check_Valid_Cookie(HgHvgtdbGJ(), $Header_Check) == true)
   {
       User_Passed_Inspection(HgHvgtdbGJ());
   }
}

if (Check_Valid_Cookie(HgHvgtdbGJ(), $Header_Check) == false)
{
    
    $User_Agent = $_SERVER["HTTP_USER_AGENT"];
    $Excluded = false;
    
    if (preg_match('/\bGooglebot\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bDiscordbot\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bBingbot\b/', $User_Agent) || preg_match('/\bbingbot\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (strpos($User_Agent, 'Bingbot') !== false || strpos($User_Agent, 'bingbot') !== false) 
    {
        $Excluded = true;
    }
    if (preg_match('/\bBaiduspider\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bYandexBot\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bia_archiver\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bfacebot\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bfacebookexternalhit\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bSlurp\b/', $User_Agent) || preg_match('/\bYahoo\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bDuckDuckBot\b/', $User_Agent) || preg_match('/\bduckduckbot\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bSogou\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    if (preg_match('/\bExabot\b/', $User_Agent)) 
    {
       $Excluded = true;
    }
    
    if ($Excluded == false)
    {
        $html = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/antiddos.html");
        
        $html = str_replace("RESULTHERE", Return_Valid_Cookie(HgHvgtdbGJ()), $html);
        
        echo $html;
        exit();
    }
}

?>