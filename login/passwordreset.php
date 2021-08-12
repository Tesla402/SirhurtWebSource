<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

$token = trim($_GET['tok']);

function GetIP()
{
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    
    return $_SERVER['REMOTE_ADDR'];
}

function BlowFishEncryptionManual($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2), 'BF-CFB', $key, true, $iv);
}

$cookiesec = BlowFishDecryptionManual($token, "C?D(G+KbPeSh?mSq3t2wEycD&E)H@McB", "b2C*F?ec");

$keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
$userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$cookiesec = strip_tags($keydbconn->real_escape_string($cookiesec));

$hashedpass = md5($password . $md5seed);

 if($stmt = $keydbconn->prepare("SELECT authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel FROM UserDB WHERE cookiesec = ?")) {
    $stmt->bind_param("s", $cookiesec); 
    $stmt->execute(); 
    $stmt->bind_result($authkey_db, $hwid_db, $email, $usern, $pass_db, $whitelistkey, $ip_db, $discord, $date, $expire, $flagged, $hwidlock, $lastreset, $flagreason, $cookiesec_db, $oldpwd, $accesslevel);
    while ($stmt->fetch()) 
    {
        /* Compare cookiesec in DB */
        if ($cookiesec == $cookiesec_db)
        {
             setcookie("_ASSHURTSECTOKEN", BlowFishEncryptionManual($cookiesec_db, COOKIE_ENC_KEY, COOKIE_ENC_IV), time() + (86400 * 1), "/"); // 86400 = 1 day
    
             $replace = file_get_contents("passwordreset.html");
             $replace = str_replace("DEFAULTUSERNAME", $usern, $replace);
             echo $replace;
             exit();
        }
    }
 }


echo "Invalid token. Please request a new password reset and try again.";
?>