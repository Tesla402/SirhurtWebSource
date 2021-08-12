<?php
require_once ($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/TicketHandler.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/subscription.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Referral_System.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/SecurityHandler.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/RankTitles.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function BlowFishEncryptionManual($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2) , 'BF-CFB', $key, true, $iv);
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
        
        if ($accesslevel > 0)
        {
            $Rank_Title = GetRankFromAccessLevel($accesslevel);
        }
        
        if ($whitelistkey == "NONE" && ($mac_whitelistkey == "NONE" || strlen($mac_whitelistkey) < 3))
        {
            $Rank_Title = "Free Member";
        }
        else
        {
            $Rank_Title = "Licensed User";
        }
    }
}

$ticket_html = file_get_contents("ticket_new_template.html");
$ticket_html = str_replace("User", $username, $ticket_html);
echo $ticket_html;
?>