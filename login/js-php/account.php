<?php
require_once('subscription.php');
require_once('Referral_System.php');
require_once ('SecurityHandler.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

if (isset($_POST['type']))
{
    $type = trim($_POST['type']);
}

if (isset($_POST['oldp']))
{
    $oldpassword = trim($_POST['oldp']);
}

if (isset($_POST['newp']))
{
    $newpassword = trim($_POST['newp']);
}

if (isset($_POST['newe']))
{
    $newemail = trim($_POST['newe']);
}

$md5seed = "78hsdSJH#$%";

function sha256_hash($str)
{
    return hash('sha256', $str);
}

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

function postToDiscord($message, $webhook, $webhookname)
{
    $data = array("content" => $message, "username" => $webhookname);
    $curl = curl_init($webhook);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Connection: Keep-Alive' ));
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    return curl_exec($curl);
}

if (isset($_COOKIE["_ASSHURTSECURITY"]))
{
    $username = $_COOKIE["_ASSHURTSECURITY"];
}
else
{
    header("Location: login.php");
    die();
}

$userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
$keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec

$cookiesec_cookie = strip_tags($userdbconn->real_escape_string($cookiesec_cookie));
$username = strip_tags($userdbconn->real_escape_string($username));


if ($type == "withdrawPaypalBalance")
{
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
                
                $paypalemail = trim($_POST['paypalemail']);
                $confirmpaypalemail = trim($_POST['cpaypalemail']);
                
                if ($paypalemail != $confirmpaypalemail || strlen($paypalemail) <= 3)
                {
                    $myObj->status = "bad";
                    $myObj->param = "Emails do not match.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();  
                }
                
                if (strpos($paypalemail, '@') == false || strpos($paypalemail, '.') == false) 
                {
                    $myObj->status = "bad";
                    $myObj->param  = "Invalid email address. Please check your email and try again.";
                    $jsonoutput    = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }

                
                $information = Fetch_Pending_Balance($username);
                
                if ($information[0] == false || $information[1] <= 0)
                {
                    $myObj->status = "bad";
                    $myObj->param = "You do not have any pending funds to withdraw.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $payout_usd_value = 0.0052 * $information[1];
                
                if ($payout_usd_value < 5.00)
                {
                    setlocale(LC_MONETARY, 'en_US');
                    $value_format = money_format('%.2n', $payout_usd_value) . " USD";
                    
                    $myObj->status = "bad";
                    $myObj->param = "To request a paypal payout, you must have a minimum of 5$ in your pending payout balance. Your pending payout balance is: " . $value_format;
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $serial_key = Paypal_Withdrawl_Balance($username, $paypalemail);
                
                if ($serial_key == "EMPTY_BALANCE" || $serial_key == "UPDATE_FAILED")
                {
                    $myObj->status = "bad";
                    $myObj->param = "An unknown issue occured while trying to request a payout.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                postToDiscord("<@506951692743606274> A user has requested a paypal payout and is currently awaiting your review. Thanks!", "https://discordapp.com/api/webhooks/822182441594126346/KnkEAavMLCh_nNse_71Q4dbQyYJ4p9pIzpug7JEY6CTtyWPwY7FlWBcYcnCHRBiwoSAD", "Notification Manager");
                
                $myObj->status = "good";
                $myObj->param = "The request has been recieved. Your request will be reviewed within 3-4 days.";
                $jsonoutput = json_encode($myObj);
                echo $jsonoutput;
                exit();
            }
        }
    }
    
    $myObj->status = "bad";
    $myObj->param = "An unknown error occured.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "withdrawBalance")
{
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
                
                $information = Fetch_Pending_Balance($username);
                
                if ($information[0] == false || $information[1] <= 0)
                {
                    $myObj->status = "bad";
                    $myObj->param = "You do not have any pending funds to withdraw.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $serial_key = Withdrawl_Balance($username);
                
                if ($serial_key == "EMPTY_BALANCE" || $serial_key == "UPDATE_FAILED")
                {
                    $myObj->status = "bad";
                    $myObj->param = "An unknown issue occured while trying to request a payout.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $myObj->status = "good";
                $myObj->param = "Your ROBUX serial key is: " . $serial_key . " redeem at https://sirhurt.net/autobuy/buyrobux.html";
                $jsonoutput = json_encode($myObj);
                echo $jsonoutput;
                exit();
            }
        }
    }
    
    $myObj->status = "bad";
    $myObj->param = "An unknown error occured.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

/* Password Reset */
if ($type == "resetPassword")
{

    /* if data is empty / null */
    if (strlen($oldpassword) <= 1 || strlen($newpassword) <= 1)
    {
        $myObj->status = "bad";
        $myObj->param = "Please fill out all fields and try again.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }

    /* password stength */
    if (strlen($newpassword) <= 5)
    {
        $myObj->status = "bad";
        $myObj->param = "Passwords must be at least 5 characters long.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }

    if (strlen($newpassword) > 40)
    {
        $myObj->status = "bad";
        $myObj->param = "Passwords cannot be longer than 40 characters.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }

    if (!preg_match("#[0-9]+#", $newpassword))
    {
        $myObj->status = "bad";
        $myObj->param = "Password must include at least one number.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }

    if (!preg_match("#[a-zA-Z]+#", $newpassword))
    {
        $myObj->status = "bad";
        $myObj->param = "Password must include at least one letter.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }

    if (preg_match('/(\w)\1{3,}/', $newpassword))
    {
        $myObj->status = "bad";
        $myObj->param = "Please do not include repeating characters in your password.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }

    if (preg_match('/\s/', $newpassword))
    {
        $myObj->status = "bad";
        $myObj->param = "Please do not include white spaces in your password.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    /* end password strength test */

    /* hash password */
    //$hashedpass = md5($newpassword . $md5seed);
    
    $pwd_salt = substr($newpassword, 0, 2);
    $rand_salt = md5($newpassword . "_6g{m#UhG+2nuNx" . $pwd_salt);
    $hashedpass = password_hash($newpassword . $rand_salt, PASSWORD_ARGON2ID); //md5($pass . $md5seed);

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
                
                $pwd_salt = substr($oldpassword, 0, 2);
                $rand_salt = md5($oldpassword . "_6g{m#UhG+2nuNx" . $pwd_salt);
                $hashedpass_old = md5($oldpassword . $rand_salt); //md5($pass . $md5seed);
                
                $hashedpass_new_old_pw = sha256_hash($oldpassword . $rand_salt); //md5($pass . $md5seed);
                
                $pwd_salt = substr($newpassword, 0, 2);
                $rand_salt = md5($newpassword . "_6g{m#UhG+2nuNx" . $pwd_salt);
                $hashedpass_new = md5($newpassword . $rand_salt); //md5($pass . $md5seed);
                
                $hashedpass_new_v1 = password_hash($newpassword . $rand_salt, PASSWORD_ARGON2ID); //sha256_hash($newpassword . $rand_salt);
                
                /* check password */
                if (md5($oldpassword . $md5seed) != $pass_db && $hashedpass_old != $pass_db && $hashedpass_new_old_pw != $pass_db && !password_verify($oldpassword . $rand_salt, $pass_db))
                {
                    $myObj->status = "bad";
                    $myObj->param = "Incorrect password, password does not match records.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }

                $newcookiesec = generateRandomString(5);
                $data = 'authkey = ' . "'$authkey'" . ', hwid = ' . "'$hwid'" . ', email = ' . "'$email'" . ", username = " . "'$usern'" . ", pass = " . "'$hashedpass_new_v1'" . ", whitelistkey = " . "'$whitelistkey'" . ", ip = " . "'$ip'" . ", discord = " . "'$discord'" . ", date = " . "'$date'" . ", expire = " . "'$expire'" . ", flagged = " . "'$flagged'" . ", hwidlock = " . "'$hwidlock'" . ", lastreset = " . "'$lastreset'" . ", flagreason = " . "'$flagreason'" . ", cookiesec = " . "'$newcookiesec'" . ", oldpwd = " . "'$oldpwd'" . ", accesslevel = " . "'$accesslevel'" . ", mac_hwid = " . "'$mac_hwid'" . ", mac_whitelistkey = " . "'$mac_whitelistkey'" . ", mac_expire = " . "'$mac_expire'" . ", cookiesec_expires = " . "'$cookiesec_expires'" . ", securitypin = " . "'$securitypin'";
                $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
                if ($userdbconn->query($sql) === false)
                {
                    $myObj->status = "bad";
                    $myObj->param = "A unknown error occured while trying to reset the account $username.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                else
                {
                    setcookie("_ASSHURTSECTOKEN", BlowFishEncryptionManual($newcookiesec, COOKIE_ENC_KEY, COOKIE_ENC_IV) , time() + (86400 * 5) , "/"); // 86400 = 1 day
                    $myObj->status = "good";
                    $myObj->param = "Your password has been successfully changed.";
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


/* Old Database Transfer */
if ($type == "databaseTransfer")
{
    $baddbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $badloop = mysqli_query($baddbconn, "SELECT * FROM FailedAttempts WHERE username = '$username'");

    /* Prevent Bruteforcing Attacks */
    while ($entry = mysqli_fetch_array($badloop))
    {
        if ($entry['attempts'] > 5 && getdatedif($entry['date']) >= 0)
        {
            $myObj->status = "bad";
            $myObj->param = "You've had to many unsuccessful upgrade attempts today. Please try again in 24 hours from now.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
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
            }

            /* Search old DB */
            $olddbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
            $pass = strip_tags($olddbconn->real_escape_string($oldpassword));
            $pass = md5($pass . "Y9@GHT7#cZ&Y");

            if ($olddbstmt = $olddbconn->prepare("SELECT hwid, whitelistkey, authkey, flagged, ip, discord, expire, date, pass FROM Whitelist WHERE pass = ?"))
            {

                $olddbstmt->bind_param("s", $pass);
                $olddbstmt->execute();
                $olddbstmt->bind_result($hwid_1, $whitelistkey_1, $authkey_1, $flagged_1, $ip_1, $discord_1, $expire_1, $date_1, $pass_1);

                while ($olddbstmt->fetch())
                {
                    if ($pass == $pass_1)
                    {

                        /* whitelist is suspended */
                        if ($flagged_1 == 'TRUE' || $flagged == 'TRUE')
                        {
                            $myObj->status = "bad";
                            $myObj->param = "This whitelist is suspended and cannot be transfered.";
                            $jsonoutput = json_encode($myObj);
                            echo $jsonoutput;
                            exit();
                        }

                        /* old wl check */
                        if ($whitelistkey_1 == "NONE")
                        {
                            $whitelistkey_1 = generateRandomString(10);
                        }

                        /* check if this accounts already upgraded & said whitelist isn't a subscription */
                        if ($whitelistkey != "NONE" && $expire == "NONE")
                        {
                            $myObj->status = "bad";
                            $myObj->param = "Your account already has a lifetime membership.";
                            $jsonoutput = json_encode($myObj);
                            echo $jsonoutput;
                            exit();
                        }

                        /* check if already upgraded another account */
                        $userdb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
                        $userdbloop = mysqli_query($userdb, "SELECT 'oldpwd' FROM UserDB WHERE oldpwd = '$pass'") or die(mysqli_error($userdbloop));
                        $numentries = mysqli_num_rows($userdbloop);

                        /* found on another account */
                        if ($numentries >= 1)
                        {
                            $myObj->status = "bad";
                            $myObj->param = "This whitelist has already been transfered to an account.";
                            $jsonoutput = json_encode($myObj);
                            echo $jsonoutput;
                            exit();
                        }

                        /* begin transfer */

                        $data = 'authkey = ' . "'$authkey_1'" . ', hwid = ' . "'$hwid'" . ', email = ' . "'$email'" . ", username = " . "'$usern'" . ", pass = " . "'$pass_db'" . ", whitelistkey = " . "'$whitelistkey_1'" . ", ip = " . "'$ip_1'" . ", discord = " . "'$discord_1'" . ", date = " . "'$date_1'" . ", expire = " . "'$expire_1'" . ", flagged = " . "'$flagged'" . ", hwidlock = " . "'$hwidlock'" . ", lastreset = " . "'$lastreset'" . ", flagreason = " . "'$flagreason'" . ", cookiesec = " . "'$cookiesec'" . ", oldpwd = " . "'$pass'" . ", accesslevel = " . "'$accesslevel'" . ", mac_hwid = " . "'$mac_hwid'" . ", mac_whitelistkey = " . "'$mac_whitelistkey'" . ", mac_expire = " . "'$mac_expire'" . ", cookiesec_expires = " . "'$cookiesec_expires'" . ", securitypin = " . "'$securitypin'";
                        $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
                        if ($userdbconn->query($sql) === false)
                        {
                            $myObj->status = "bad";
                            $myObj->param = "A unknown error occured while trying to upgrade this account.";
                            $jsonoutput = json_encode($myObj);
                            echo $jsonoutput;
                            exit();
                        }

                        $myObj->status = "good";
                        $myObj->param = "Succcessfully transfered over whitelist information to this account. Thanks for purchasing!";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();

                    }
                }
                
                /* Log Failed Attempt to prevent Bruteforcing */
                $baddbconn_2 = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
                $badloop_2 = mysqli_query($baddbconn_2, "SELECT * FROM FailedAttempts WHERE username = '$username'");
            
                if (mysqli_num_rows($badloop_2) == 0)
                {
                    $ip = GetIP();
                    $date = getnow();
                    
                    $sql = "INSERT INTO FailedAttempts (username, ip, date, attempts)
                    VALUES ('$username', '$ip', '$date', 0)";
                    
                    $baddbconn_2->query($sql);
                }
                else
                {
                    while ($entry = mysqli_fetch_array($badloop_2))
                    {
                        $newattempts = $entry['attempts'] + 1;
                        $replacedate = $entry['date'];
                        
                        /* Reset Attempts */
                        if (getdatedif($entry['date']) < 0)
                        {
                            $replacedate = getnow();
                            $newattempts = 1;
                        }
                        
                        /* Update Failed Attempts */
                        $data_new = 'username = ' . "'$entry[0]'" . ', ip = ' . "'$entry[1]'" . ', date = ' . "'$replacedate'" . ", attempts = " . "'$newattempts'";
                        $baddbconn_2->query("UPDATE FailedAttempts SET $data_new WHERE username = '$username'");
                    }
                }

                /* Invalid Login Information */
                $myObj->status = "bad";
                $myObj->param = "Invalid password, this information does not exist.";
                $jsonoutput = json_encode($myObj);
                echo $jsonoutput;
                exit();
            }
        }
    }
}

/* Delete Account */
if ($type == "terminateAccount")
{
    
    /* check DB for account */
    if ($stmt = $keydbconn->prepare("SELECT authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel FROM UserDB WHERE username = ?"))
    {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($authkey, $hwid, $email, $usern, $pass_db, $whitelistkey, $ip, $discord, $date, $expire, $flagged, $hwidlock, $lastreset, $flagreason, $cookiesec, $oldpwd, $accesslevel);
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
            }
        }
    }
    
    if ($oldpassword != "DELETE")
    {
        $myObj->status = "bad";
        $myObj->param = "To delete your account, please enter 'DELETE' in the confirmation box to prevent an accidental deletion. Are you sure you want to delete your account? Your data will be unrecoverable.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();        
    }
    
    /* Delete SirHurt Authentication History in Compliance with COPPA */
    if ($whitelistkey != "NONE")
    {
        $logdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
        $logdbconn->query("DELETE FROM LogDB WHERE USERNAME = '$username'");
    }
    
    /* Delete Login History (COPPA Compilance) */
    $websitelogins_db = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $websitelogins_db->query("DELETE FROM WebsiteLogDB WHERE username = '$username'");

    $conn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

    /* Delete Account */
    if ($conn->query("DELETE FROM UserDB WHERE username = '$username'"))
    {
        /* Recreate Serial Key. TODO: Subscription Keys */
        if ($whitelistkey != "NONE" && $expire == "NONE")
        {
            $sql = "INSERT INTO KeyDB (serialkey, type, days)
            VALUES ('$whitelistkey', 'HWID', 0)";
            $keyDB = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
            $keyDB->query($sql);
        }
        
        $myObj->status = "good";
        $myObj->param = "Your account and all collected information has been deleted from our systems. We're sorry to see you go!";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    else
    {
        $myObj->status = "bad";
        $myObj->param = "We were unable to delete your account.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
}

/* Email Address Reset */
if ($type == "resetEmail")
{

    if (strpos($newemail, '@') == false) {
        $myObj->status = "bad";
        $myObj->param  = "Invalid email address. Please check your email and try again.";
        $jsonoutput    = json_encode($myObj);
        echo $jsonoutput;
        exit();
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
                
                $pwd_salt = substr($oldpassword, 0, 2);
                $rand_salt = md5($oldpassword . "_6g{m#UhG+2nuNx" . $pwd_salt);
                $hashedpass = md5($oldpassword . $rand_salt); //md5($pass . $md5seed);

                /* check password */
                if (md5($oldpassword . $md5seed) != $pass_db && $hashedpass != $pass_db)
                {
                    $myObj->status = "bad";
                    $myObj->param = "Incorrect password, password does not match records.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }

                $data = 'authkey = ' . "'$authkey'" . ', hwid = ' . "'$hwid'" . ', email = ' . "'$newemail'" . ", username = " . "'$usern'" . ", pass = " . "'$pass_db'" . ", whitelistkey = " . "'$whitelistkey'" . ", ip = " . "'$ip'" . ", discord = " . "'$discord'" . ", date = " . "'$date'" . ", expire = " . "'$expire'" . ", flagged = " . "'$flagged'" . ", hwidlock = " . "'$hwidlock'" . ", lastreset = " . "'$lastreset'" . ", flagreason = " . "'$flagreason'" . ", cookiesec = " . "'$cookiesec'" . ", oldpwd = " . "'$oldpwd'" . ", accesslevel = " . "'$accesslevel'" . ", mac_hwid = " . "'$mac_hwid'" . ", mac_whitelistkey = " . "'$mac_whitelistkey'" . ", mac_expire = " . "'$mac_expire'" . ", cookiesec_expires = " . "'$cookiesec_expires'" . ", securitypin = " . "'$securitypin'";
                $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
                if ($userdbconn->query($sql) === false)
                {
                    $myObj->status = "bad";
                    $myObj->param = "A unknown error occured while trying to update the email on this account.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                else
                {
                    /* Using a proxy domain to send emails past spamhaus - Email Verification */
                    file_get_contents("https://www.sirhurt.net/login/emailapi/verifyemail.php?sec=SDHKJSHDKJSGDJKS&emladr=" . $newemail . "&tokeen=" . urlencode($cookiesec) . "&betken=" . urlencode(BlowFishEncryptionManual($newemail, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")) . "&usrntk=" . urlencode(BlowFishEncryptionManual($usern, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")));
                    
                    $myObj->status = "good";
                    $myObj->param = "Your email has successfully been updated. We've sent a verification email to your inbox to verify your email address. It may take a few minutes to appear. Please check junk/spam too if it doesn't appear in your inbox!";
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

/* Email Address Reset */
if ($type == "sendverifyemail")
{
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
                
                /* Using a proxy domain to send emails past spamhaus - Email Verification */
                file_get_contents("https://www.sirhurt.net/login/emailapi/verifyemail.php?sec=SDHKJSHDKJSGDJKS&emladr=" . $email . "&tokeen=" . urlencode($cookiesec) . "&betken=" . urlencode(BlowFishEncryptionManual($email, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")) . "&usrntk=" . urlencode(BlowFishEncryptionManual($usern, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")));
                    
                $myObj->status = "good";
                $myObj->param = "We've resent the verification email. It can take a few minutes to appear. Junk junk/spam inbox as sometimes our emails are marked as spam.";
                $jsonoutput = json_encode($myObj);
                echo $jsonoutput;
                exit();
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
