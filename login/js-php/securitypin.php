<?php
require_once('subscription.php');
require_once('Referral_System.php');
require_once ('SecurityHandler.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

if (isset($_POST['type']))
{
    $type = trim($_POST['type']);
}

if (isset($_POST['securitypin']))
{
    $secpin = trim($_POST['securitypin']);
}

if (isset($_POST['confirmsecuritypin']))
{
    $confirmsecuritypin = trim($_POST['confirmsecuritypin']);
}

if (isset($_POST['pwd']))
{
    $pwd = trim($_POST['pwd']);
}

$md5seed = "78hsdSJH#$%";

function BlowFishEncryptionManual($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2) , 'BF-CFB', $key, true, $iv);
}

function GetIP() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}

function generateRandomString($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0;$i < $length;$i++)
    {
        $randomString .= $characters[rand(0, $charactersLength - 1) ];
    }
    return $randomString;
}

if (isset($_COOKIE["_ASSHURTSECURITY"]))
{
    $username = $_COOKIE["_ASSHURTSECURITY"];
}
else
{
    header("Location: login.html");
    die();
}

$userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
$keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec

$cookiesec_cookie = strip_tags($keydbconn->real_escape_string($cookiesec_cookie));
$username = strip_tags($keydbconn->real_escape_string($username));

/* Reset Security Pin */
if ($type == "resetSecurityPin")
{
    if (strlen($secpin) > 0)
    {
        if (strlen($secpin) > 6 || strlen($secpin) < 4) 
        {
            $myObj->status = "bad";
            $myObj->param  = "Security pin must contain between 4 to 6 numbers.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
        
        if ($secpin != $confirmsecuritypin)
        {
            $myObj->status = "bad";
            $myObj->param  = "Security pins do not match. Please correct them then try again.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
        
        if (!is_numeric($secpin))
        {
            $myObj->status = "bad";
            $myObj->param  = "Security pin can only contain numbers.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
    }
    
    $hashed_security_pin = md5($secpin . "UiItBU0eqm");
    
    if (strlen($secpin) == 0)
    {
        $hashed_security_pin = "NONE";
    }

    /* check DB for account */
    if ($stmt = $keydbconn->prepare("SELECT authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel, mac_hwid, mac_whitelistkey, mac_expire, cookiesec_expires, securitypin FROM UserDB WHERE username = ?"))
    {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($authkey, $hwid, $email, $usern, $pass_db, $whitelistkey, $ip, $discord, $date, $expire, $flagged, $hwidlock, $lastreset, $flagreason, $cookiesec, $oldpwd, $accesslevel, $mac_hwid, $mac_whitelistkey, $mac_expire, $cookiesec_expires, $securitypin);
        while ($stmt->fetch())
        {
            if ($username == $usern)
            {

                if ($cookiesec != $cookiesec_cookie)
                {
                    header("Location: logout.php");
                    die();
                }
                
                if (Is_Cookie_Expired($username) == "EXPIRED")
                {
                   header("Location: logout.php");
                   die();
                }

                /* check password */
                $pwd_salt = substr($pwd, 0, 2);
                $rand_salt = md5($pwd . "_6g{m#UhG+2nuNx" . $pwd_salt);
                
                if (md5($pwd . $md5seed) != $pass_db && !password_verify($pwd . $rand_salt, $pass_db))
                {
                    $myObj->status = "bad";
                    $myObj->param = "Incorrect password, password does not match records.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }

                $data = 'authkey = ' . "'$authkey'" . ', hwid = ' . "'$hwid'" . ', email = ' . "'$email'" . ", username = " . "'$usern'" . ", pass = " . "'$pass_db'" . ", whitelistkey = " . "'$whitelistkey'" . ", ip = " . "'$ip'" . ", discord = " . "'$discord'" . ", date = " . "'$date'" . ", expire = " . "'$expire'" . ", flagged = " . "'$flagged'" . ", hwidlock = " . "'$hwidlock'" . ", lastreset = " . "'$lastreset'" . ", flagreason = " . "'$flagreason'" . ", cookiesec = " . "'$cookiesec'" . ", oldpwd = " . "'$oldpwd'" . ", accesslevel = " . "'$accesslevel'" . ", mac_hwid = " . "'$mac_hwid'" . ", mac_whitelistkey = " . "'$mac_whitelistkey'" . ", mac_expire = " . "'$mac_expire'" . ", cookiesec_expires = " . "'$cookiesec_expires'" . ", securitypin = " . "'$hashed_security_pin'";
                $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
                if ($userdbconn->query($sql) === false)
                {
                    $myObj->status = "bad";
                    $myObj->param = "A unknown error occured while trying to update the security pin on this account.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                else
                {
                    $myObj->status = "good";
                    $myObj->param = "Security pin has been updated.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
            }
        }

        $myObj->status = "bad";
        $myObj->param = "Unable to locate whitelist information.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
}

$myObj->status = "bad";
$myObj->param = "An unknown error occured.";
$jsonoutput = json_encode($myObj);
echo $jsonoutput;
exit();

?>
