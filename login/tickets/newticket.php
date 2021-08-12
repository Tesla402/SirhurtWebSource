<?php
date_default_timezone_set('America/New_York');
include_once ($_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php");
include_once ($_SERVER['DOCUMENT_ROOT'] . "/login/trelloapi/submission_handler.php");
require_once ($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/TicketHandler.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/subscription.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Referral_System.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/SecurityHandler.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/RankTitles.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function post_captcha($user_response)
{
    $fields_string = '';
    $fields        = array(
        'secret' => '6Lcy0-EUAAAAAGlOWxzIk-pXJu986WnEqMm-tFf5',
        'response' => $user_response
    );
    foreach ($fields as $key => $value)
        $fields_string .= $key . '=' . $value . '&';
    $fields_string = rtrim($fields_string, '&');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, True);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}

function BlowFishEncryptionManual($part2, $key, $iv){
return base64_encode(openssl_encrypt ($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv){
return openssl_decrypt (base64_decode($part2), 'BF-CFB', $key, true, $iv);
}

function generateRandomString($n)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0;$i < $n;$i++)
    {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

$res = post_captcha($_POST['captcha1']);
if (!$res['success']) {
    $myObj->status = "bad";
    $myObj->param = "Invalid Captcha";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if (isset($_COOKIE["_ASSHURTSECURITY"]))
{
    $username = $_COOKIE["_ASSHURTSECURITY"];
}
else
{
    header("Location: https://www.sirhurt.net/login/login.html");
    die();
}

$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec
$conn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$cookiesec_cookie = strip_tags($conn->real_escape_string($cookiesec_cookie));
$username = strip_tags($conn->real_escape_string($username));

$passs = strip_tags($conn->real_escape_string($username));
$Rank_Title = "";
$found_account = false;

$query = $_POST['query'];
$querytype = $_POST['querytype'];
$type = $_POST['type'];

$query = str_replace('"', "", $query);
$query = str_replace("'", "", $query);
$query = ($conn->real_escape_string($query));

if($stmt = $conn->prepare("SELECT authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel, mac_hwid, mac_whitelistkey, mac_expire, cookiesec_expires, securitypin FROM UserDB WHERE username = ?")) {

   $stmt->bind_param("s", $passs); 
   $stmt->execute(); 
   $stmt->bind_result($authkey, $hwid, $email, $usern, $pass, $whitelistkey, $ip, $discord, $date, $expire, $flagged, $hwidlock, $lastreset, $flagreason, $cookiesec, $oldpwd, $accesslevel, $mac_hwid, $mac_whitelistkey, $mac_expire, $cookiesec_expires, $securitypin);

   while ($stmt->fetch()) 
   {

        if ($cookiesec != $cookiesec_cookie)
        {
            header("Location: https://www.sirhurt.net/login/logout.php");
            die();
        }
        
        if (Is_Cookie_Expired($usern) == "EXPIRED")
        {
           header("Location: https://www.sirhurt.net/login/logout.php");
           die();
        }
        
        if ($whitelistkey == "NONE" && ($mac_whitelistkey == "NONE" || strlen($mac_whitelistkey) < 3))
        {
            $Rank_Title = "Free Member";
        }
        else
        {
            $Rank_Title = "Licensed User";
        }
        
        if ($accesslevel > 0)
        {
            $Rank_Title = GetRankFromAccessLevel($accesslevel);
        }
        
        $found_account = true;
    }
}

if ($found_account == false)
{
    $myObj->status = "good";
    $myObj->param = "https://www.sirhurt.net/login/logout.php";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "SubmitTicket")
{
    if (strlen($query) <= 3)
    {
        $myObj->status = "bad";
        $myObj->param = "Invalid Query Length. Please provide a query longer than 4 characters.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    if ($querytype != "general" && $querytype != "technical" && $querytype != "blacklist" && $querytype != "bug")
    {
        $myObj->status = "bad";
        $myObj->param = "Did you provide a valid query type...?";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (Does_User_Have_Open_Ticket($username) == true)
    {
       $myObj->status = "bad";
        $myObj->param = "You are only allowed to have one open ticket at a time. Please mark your previous ticket as solved before making a new ticket.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit(); 
    }
    
    if ($querytype == "general")
    {
        $querytype = "General Inquiry";
    }
    
    if ($querytype == "technical")
    {
        $querytype = "Technical Issues";
    }
    
    if ($querytype == "blacklist")
    {
        $querytype = "Account Suspension";
    }
    
    if ($querytype == "bug")
    {
        $querytype = "Bug Report";
    }

    $myObj->Queries = 
    array(  
        array(
          "Query" => base64_encode($query),
          "Member" => $username,
          "IsStaff" => false,
          "Rank" => $Rank_Title,
          "Query_Date" => $date = date('Y-m-d')
        )  
    );
    
    $query = json_encode($myObj);
    
    $ticketidnew = generateRandomString(10);
    
    Create_New_Ticket($ticketidnew, $querytype, $username, $query);
    
    $myObj->status = "good";
    $myObj->param = "https://sirhurt.net/login/tickets/viewticket.php?ticketid=$ticketidnew";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

$myObj->status = "bad";
$myObj->param = "Invalid Request Type";
$jsonoutput = json_encode($myObj);
echo $jsonoutput;
exit();

?>