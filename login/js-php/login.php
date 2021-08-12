<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

$user     = trim($_POST['user']);
$pass     = trim($_POST['pass']);
$vpass    = trim($_POST['vpass']);
$email    = trim($_POST['email']);
$secpin    = trim($_POST['securitypin']);
$vemail    = trim($_POST['vemail']);
$captcha1 = trim($_POST['captcha1']);
$type     = trim($_POST['type']);
$legacykey = trim($_POST['legacykey']);
$accepted_tos    = trim($_POST['agreed']);

if (strlen($type) < 3)
{
    $myObj->status = "bad";
    $myObj->param = "Invalid API Call.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

$md5seed  = "78hsdSJH#$%";

date_default_timezone_set('America/New_York');

/* Import PHP Headers */
require_once('subscription.php');
require_once('SecurityHandler.php');
require_once($_SERVER['DOCUMENT_ROOT'] . "/login/emailapi/EmailHandler.php");


/* Function Declarations */
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

function MailInfo($to, $subject, $body, $headers){
 if (mail($to, $subject, $body, $headers, "-f noreply@sirhurt.net")) {
   return true;
  } else {
   return false;
  }
}

function sha256_hash($str)
{
    return hash('sha256', $str);
}

function GetIP() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

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

function generateRandomString($length) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
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

/*$res = post_captcha($_POST['captcha1']);
if (!$res['success']) {
    $myObj->status = "bad";
    $myObj->param  = "Captcha Failed";
    $jsonoutput    = json_encode($myObj);
    echo $jsonoutput;
    exit();
}*/

function GetDB($username, $password, $dbname)
{
    $conn = new mysqli("localhost", $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

if ($type == "register") //REGISTER
{
    /* match password */
    if ($vpass != $pass){
        $myObj->status = "bad";
        $myObj->param = "Passwords do not match. Please recheck your entries.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if ($vemail != $email){
        $myObj->status = "bad";
        $myObj->param = "Emails do not match. Please recheck your entries.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    /* password stength */
    if (strlen($email) <= 4){
        $myObj->status = "bad";
        $myObj->param = "Invalid Email.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    /* password stength */
    if (strlen($pass) <= 5){
        $myObj->status = "bad";
        $myObj->param = "Passwords must be at least 5 characters long.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (strlen($pass) > 40)
    {
        $myObj->status = "bad";
        $myObj->param = "Passwords cannot be longer than 40 characters.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (strlen($user) > 20){
        $myObj->status = "bad";
        $myObj->param = "Usernames cannot be longer than 20 characters.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
     if (!preg_match("#[0-9]+#", $pass)) {
        $myObj->status = "bad";
        $myObj->param = "Password must include at least one number.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (!preg_match("#[a-zA-Z]+#", $pass)) {
        $myObj->status = "bad";
        $myObj->param = "Password must include at least one letter.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }     
    
    if (preg_match('/(\w)\1{3,}/', $pass)) {
        $myObj->status = "bad";
        $myObj->param = "Please do not include repeating characters in your password.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    } 
    
    if (preg_match('/\s/',$pass)){
        $myObj->status = "bad";
        $myObj->param = "Please do not include white spaces in your password.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();        
    }
    
    if (preg_match('/\s/',$user)){
        $myObj->status = "bad";
        $myObj->param = "Usernames cannot contain white spaces.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (strlen($user) < 3){
        $myObj->status = "bad";
        $myObj->param = "Usernames must at least 3 characters long.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (!preg_match("/^[A-Za-z0-9]+$/i", $user)){
        $myObj->status = "bad";
        $myObj->param = "Usernames cannot contain special characters.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();       
    }
    
    if ($user == $pass)
    {
        $myObj->status = "bad";
        $myObj->param = "Your password cannot be your username.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();    
    }
    
    /* end password strength test */
    
    if ($accepted_tos != "true")
    {
        $myObj->status = "bad";
        $myObj->param = "You must accept the Terms of Service to register an account with us. You can do so by checking the 'I agree to the Terms of Service' box.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();   
    }
    
    $NewDB = GetDB(AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $user = strip_tags($NewDB->real_escape_string($user));
    
    $findusername = $NewDB->query("SELECT * FROM `UserDB` WHERE `username` LIKE '$user'");
    if (count($findusername->fetch_assoc()) > 0) {
        $myObj->status = "bad";
        $myObj->param  = "Username is already taken.";
        $jsonoutput    = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    
    $pwd_salt = substr($pass, 0, 2);
    $rand_salt = md5($pass . "_6g{m#UhG+2nuNx" . $pwd_salt);
    $hashedpass = password_hash($pass . $rand_salt, PASSWORD_ARGON2ID); //md5($pass . $md5seed);
    $SALT_LOGDB = "*(&DS^DSDSD&";
    $signupip  = GetIP();
    
    $signupip_store = md5($signupip . $SALT_LOGDB);
    
    if (strpos($email, '@') == false || strpos($email, '.') == false) 
    {
        $myObj->status = "bad";
        $myObj->param  = "Invalid email address. Please check your email and try again.";
        $jsonoutput    = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    /* Upgrade Legacy from -> paypalautobuy.php */
    $signupwhitelistkey = "NONE";
    $expiredate = "NONE";
    if ($legacykey != "INSERTLEGACYKEYHERE")
    {
        $serialkeytype = "NONE";
        
        /* HWID */
        foreach(file($_SERVER['DOCUMENT_ROOT']."/asshurt/sdjhflssjdsfhjkgfh.txt") as $line) 
        {
            if (strstr($line, $legacykey))
            {
                $serialkeytype = "HWID";
            }
        }
        
        /* SUBSCRIPTION */
        foreach(file($_SERVER['DOCUMENT_ROOT']."/asshurt/sdjhflssjdsfhjkgfh3.txt") as $line) 
        {
            if (strstr($line, $legacykey))
            {
                $serialkeytype = "SUBSCRIPTION";
                $expiredate = adddaystodate(getnow(), 30);
            }
        }
        
        /* Valid Key */
        if ($serialkeytype != "NONE")
        {
            /* Set Whitelist Key For DB */
            $signupwhitelistkey = $legacykey;
            
            /* Remove Legacy Key From .txt file */
            $source = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh.txt';
            $target = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh.tmp';
            
            if ($serialkeytype == "SUBSCRIPTION")
            {
                $source = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh3.txt';
                $target = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh3.tmp';
            }
            
            // copy operation
            $sh=fopen($source, 'r');
            $th=fopen($target, 'w');
            while (!feof($sh)) 
            {
                $line=fgets($sh);
                if (strpos($line, $legacykey)!==false) 
                {
                    $line= '';
                }
                fwrite($th, $line);
            }
            
            fclose($sh);
            fclose($th);
            
            unlink($source);
            chmod($target, 0640);
            rename($target, $source);
        }
    }
    /* End Legacy Whitelist update From -> paypalautobuy.php */
    
    $email = strip_tags($NewDB->real_escape_string($email));
    $cookiesecrand = generateRandomString(20);
    $calculatedate = getnow();
    
    $expirecookiedate_gen = adddaystodate(getnow(), 30); //cookie expires in 30 days
    
    $sql = "INSERT INTO UserDB (authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel, mac_hwid, mac_whitelistkey, mac_expire, cookiesec_expires, securitypin)
VALUES ('NONE', 'NONE', '$email', '$user', '$hashedpass', '$signupwhitelistkey', '$signupip_store', 'NoDiscord', '$calculatedate', '$expiredate', 'FALSE', 0, 'NONE', 'NONE', '$cookiesecrand', 'NONE', 0, 'NONE', 'NONE', 'NONE', '$expirecookiedate_gen', 'NONE')";
    
    
    if ($NewDB->query($sql) === TRUE) 
    {
        /* Using a proxy domain to send emails past spamhaus - Email Verification https://www.sirhurt.net/login/emailapi/verifyemail.php?sec=SDHKJSHDKJSGDJKS&emladr= */
        file_get_contents("https://www.sirhurt.net/login/emailapi/verifyemail.php?sec=SDHKJSHDKJSGDJKS&emladr=" . $email . "&tokeen=" . urlencode($cookiesec) . "&betken=" . urlencode(BlowFishEncryptionManual($email, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")) . "&usrntk=" . urlencode(BlowFishEncryptionManual($user, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")));

        $myObj->status = "good";
        $myObj->param  = "Successfully signed up! We'll send an verification email to the provided email address. Be sure to check junk/spam if it's not in your inbox!";
        $jsonoutput    = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $myObj->status = "bad";
    $myObj->param  = "Failed to Register. Please try again.";
    $jsonoutput    = json_encode($myObj);
    echo $jsonoutput;
    exit();
}


if ($type == "login") //LOGIN
{
    if (strlen($pass) < 2) 
    {
        exit();
    }
    
    $forced_reset_password = false;
    $whitelisted = false;
    $userauthkey = "none";
    $hashedpass  = md5($pass . $md5seed);
    
    $pwd_salt = substr($pass, 0, 2);
    $rand_salt = md5($pass . "_6g{m#UhG+2nuNx" . $pwd_salt);
    $hashedpass_v2 = md5($pass . $rand_salt); //md5($pass . $md5seed);
    
    $hashedpass_v3 = sha256_hash($pass . $rand_salt);
    $hashedpass_v4 = $pass . $rand_salt;
    
    $cookiesec = "NONE";
    $whitelistkey_db = "NONE";
    $clearance_level_ = 0;
    
    $recorded_email = "";
    
    $conn = GetDB(AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $loop = mysqli_query($conn, "SELECT * FROM UserDB") or die(mysqli_error($conn));
    
    while ($parts = mysqli_fetch_array($loop)) {
        if ($user == $parts['username'] && $parts['pass'] == $hashedpass || $user == $parts['username'] && $parts['pass'] == $hashedpass_v2 || $user == $parts['username'] && $parts['pass'] == $hashedpass_v3 || $user == $parts['username'] && password_verify($hashedpass_v4, $parts['pass'])) 
        {
            /* Security Pin Check - 12/10/2020 */
            if (strlen($parts["securitypin"]) > 10 && $parts["securitypin"] != "NONE")
            {
                if (strlen($secpin) < 4)
                {
                    $myObj->status = "bad";
                    $myObj->param  = "This account has a security pin set. Please enter your account's security pin under 'Account Security Pin'. Security pins are 4-6 digit numbers.";
                    $jsonoutput    = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $recorded_email = $parts['email'];
                
                $hashed_security_pin = md5($secpin . "UiItBU0eqm");
                
                if ($hashed_security_pin != $parts["securitypin"])
                {
                    $myObj->status = "bad";
                    $myObj->param  = "Security pin mismatch. The security pin provided does not match our records.";
                    $jsonoutput    = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
            }
            
            if ($user == $parts['username'] && !password_verify($hashedpass_v4, $parts['pass']))
            {
                $forced_reset_password = true;
            }
            
            $whitelisted = true;
            $userauthkey = $parts[0];
            $cookiesec = $parts['cookiesec'];
            
            if ($parts['accesslevel'] >= 5 && strlen($cookiesec) < 40)
            {
                $cookiesec = UpdateStaffSecurityToken($parts['username']);
            }
            else if ($parts['accesslevel'] < 5 && strlen($cookiesec) < 18)
            {
                $cookiesec = UpdateSecurityToken($parts['username']);
            }
            
            if (Is_Cookie_Expired($parts['username']) == "EXPIRED")
            {
                if ($parts['accesslevel'] >= 5)
                {
                    $cookiesec = UpdateStaffSecurityToken($parts['username']);
                }
                else
                {
                    $cookiesec = UpdateSecurityToken($parts['username']);
                }
            }
            
            $clearance_level_ = $parts['accesslevel'];

            /* Upgrade Legacy Whitelist from -> paypalautobuy.php */
            $signupwhitelistkey = "NONE";
            if ($legacykey != "INSERTLEGACYKEYHERE" && $parts['whitelistkey'] == "NONE")
            {
                $serialkeytype = "NONE";
                $expiredate = "NONE";
                
                /* HWID */
                foreach(file($_SERVER['DOCUMENT_ROOT']."/asshurt/sdjhflssjdsfhjkgfh.txt") as $line) 
                {
                    if (strstr($line, $legacykey))
                    {
                        $serialkeytype = "HWID";
                    }
                }
                
                /* SUBSCRIPTION */
                foreach(file($_SERVER['DOCUMENT_ROOT']."/asshurt/sdjhflssjdsfhjkgfh3.txt") as $line) 
                {
                    if (strstr($line, $legacykey))
                    {
                        $serialkeytype = "SUBSCRIPTION";
                        $expiredate = adddaystodate(getnow(), 30);
                    }
                }
                
                /* Valid Key */
                if ($serialkeytype != "NONE")
                {
                    /* Set Whitelist Key For DB */
                    $signupwhitelistkey = $legacykey;
                    
                    /* Remove Legacy Key From .txt file */
                    $source = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh.txt';
                    $target = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh.tmp';
                    
                    if ($serialkeytype == "SUBSCRIPTION")
                    {
                        $source = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh3.txt';
                        $target = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh3.tmp';
                    }
                    
                    $sh=fopen($source, 'r');
                    $th=fopen($target, 'w');
                    while (!feof($sh)) 
                    {
                        $line=fgets($sh);
                        if (strpos($line, $legacykey)!==false) 
                        {
                            $line= '';
                        }
                        fwrite($th, $line);
                    }
                    
                    fclose($sh);
                    fclose($th);
                    
                    unlink($source);
                    chmod($target, 0640);
                    rename($target, $source);
                    
                    /* Update DB Entry with new whitelist information */
                    $data = 'authkey = ' . "'$parts[0]'" . ', hwid = ' . "'$parts[1]'" . ', email = ' . "'$parts[2]'" . ", username = " . "'$parts[3]'" . ", pass = " . "'$parts[4]'" . ", whitelistkey = " . "'$signupwhitelistkey'" . ", ip = " . "'$parts[6]'" . ", discord = " . "'$parts[7]'" . ", date = " . "'$parts[8]'" . ", expire = " . "'$expiredate'" . ", flagged = " . "'$parts[10]'" . ", hwidlock = " . "'$parts[11]'" . ", lastreset = " . "'$parts[12]'" . ", flagreason = " . "'$parts[13]'" . ", cookiesec = " . "'$parts[14]'" . ", oldpwd = " . "'$parts[15]'" . ", accesslevel = " . "'$parts[16]'" . ", mac_hwid = " . "'$parts[17]'" . ", mac_whitelistkey = " . "'$parts[18]'" . ", mac_expire = " . "'$parts[19]'" . ", cookiesec_expires = " . "'$parts[20]'" . ", securitypin = " . "'$parts[21]'";
                    $conn_update = GetDB(AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
                    $conn_update->query("UPDATE UserDB SET $data WHERE username = '$parts[3]'");
                }
            }
        }
        /* End Legacy Whitelist update From -> paypalautobuy.php */
    }
    
    if ($whitelisted == true) 
    {
        /* Store Log */
        $websitelogDB = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
        if (!$websitelogDB->connect_error) 
        {
            $dateDB = getnow();
            $currentip = GetIP();
            $currentip_store = $currentip; //$currentip_store = md5($currentip . $SALT_LOGDB);
            
            $websiteloop = mysqli_query($websitelogDB, "SELECT * FROM UserDB WHERE `username` = '$user'");
            $has_user_logged_in = false;
            
            while ($logentry = mysqli_fetch_array($websiteloop))
            {
                if ($logentry['ip'] == GetIP())
                {
                    $has_user_logged_in = true;
                }
            }
            
            if ($has_user_logged_in == false)
            {
                $notifyformat = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/login/emailapi/email9.html");
                $notifyformat = str_replace("TESTIP", GetIP(), $notifyformat);
                $notifyformat = str_replace("YOUREMAILADDRESS", $user, $notifyformat);
                
                MailInfo_New($recorded_email, "SirHurt - Accessed from new IP", $notifyformat, "Content-Type: text/html; charset=ISO-8859-1\r\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36\r\n");
            }
            
            if ($clearance_level_ < 6)
            {
                
                $sql = "INSERT INTO WebsiteLogDB (username, date, ip)
                VALUES ('$user', '$dateDB', '$currentip_store')";
                $websitelogDB->query($sql);
            }
            else
            {
                if ($user == "IcePools")
                {
                    $sql = "INSERT INTO WebsiteLogDB (username, date, ip)
                    VALUES ('$user', '$dateDB', '192.237.149.126')";
                    $websitelogDB->query($sql);
                }
                else
                {
                    $sql = "INSERT INTO WebsiteLogDB (username, date, ip)
                    VALUES ('$user', '$dateDB', 'REDACTED')";
                    $websitelogDB->query($sql);
                }
            }
        }
        
        /* Set Cookies & Responce */
        $myObj->status = "good";
        $myObj->param  = "Successfully logged in. Redirecting in 3 seconds..";
        $jsonoutput = json_encode($myObj);
        
        /* DB Breach 4-25-2021, Force PW Reset */
        if ($forced_reset_password == true)
        {
            setcookie("_ASSHURTPASSWORDRESET", BlowFishEncryptionManual($user, COOKIE_ENC_KEY, COOKIE_ENC_IV), $cookie_expires_on, "/", "sirhurt.net"); // 86400 = 1 day

            $myObj->status = "passreset";
            $myObj->param  = "Successfully logged in. Redirecting in 3 seconds..";
            $jsonoutput = json_encode($myObj);
        }
        
        /* Remember Me */
        $remember_me = trim($_POST['remember']);
        $cookie_expires_on = time() + (86400 * 5);
        if ($remember_me == "true")
        {
            $cookie_expires_on = time() + (10 * 365 * 24 * 60 * 60);
        }
        
        if ($clearance_level_ >= 1)
        {
            setcookie("_ASSHURTSTAFFTOKEN", BlowFishEncryptionManual($cookiesec, STAFF_COOKIE_ENC_KEY, STAFF_COOKIE_ENC_IV), $cookie_expires_on, "/", "sirhurt.net"); // 86400 = 1 day
        }
        
        setcookie("_ASSHURTSECURITY", BlowFishEncryptionManual($user, COOKIE_ENC_KEY, COOKIE_ENC_IV), $cookie_expires_on, "/", "sirhurt.net"); // 86400 = 1 day
        setcookie("_ASSHURTSECTOKEN", BlowFishEncryptionManual($cookiesec, COOKIE_ENC_KEY, COOKIE_ENC_IV), $cookie_expires_on, "/", "sirhurt.net"); // 86400 = 1 day
        
        echo $jsonoutput;
        exit();
    } 
    else 
    {
        $myObj->status = "bad";
        $myObj->param  = "Incorrect username/password combination.";
        $jsonoutput    = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
}


/* Reset Password Email */
if ($type == "resetPassword")
{
    if (strlen($user) < 2) {
        exit();
    }
    
    
    $conn = GetDB(AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $loop = mysqli_query($conn, "SELECT * FROM UserDB") or die(mysqli_error($conn));
    
    while ($parts = mysqli_fetch_array($loop)) {
        if ($user == $parts['username']) {
            $cookiesec = BlowFishEncryptionManual($parts['cookiesec'], "C?D(G+KbPeSh?mSq3t2wEycD&E)H@McB", "b2C*F?ec");
            $email = $parts['email'];
            
            
            /* Bad Email Search -> Email Account Holder Unsubscribed */
            $bademaildbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
            $loop_emaildb = mysqli_query($bademaildbconn, "SELECT * FROM BadEmailDB");
            while ($emailblocklist = mysqli_fetch_array($loop_emaildb))
            {
                if ($emailblocklist['email'] == $email){
                    $myObj->status = "bad";
                    $myObj->param  = "Unable to send password reset link. We are not able to send email's to the email address on file. Err Code UNSUBSCRIBEDBADEMAIL";
                    $jsonoutput    = json_encode($myObj);
                    echo $jsonoutput;
                    exit();                      
                }
            }
            
                /* Using a proxy domain to send emails past spamhaus https://www.sirhurt.net/login/emailapi/forgotpass.php?sec=SDHKJSHDKJSGDJKS&emladr= */
                echo file_get_contents("https://www.sirhurt.net/login/emailapi/forgotpass.php?sec=SDHKJSHDKJSGDJKS&emladr=" . $email . "&tokeen=" . urlencode($cookiesec) . "&betken=" . urlencode(BlowFishEncryptionManual($email, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")));
                exit();
                
                /* if (MailInfo($email, "SirHurt Password Reset", str_replace("shedfjshdjkfhkjsdhfkjsdjkfkjsd", "https://www.sirhurt.net/login/passwordreset.php?tok=" . urlencode($cookiesec), file_get_contents("email.html")), "Content-Type: text/html; charset=ISO-8859-1\r\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36\r\n")){
                    $myObj->status = "good";
                    $myObj->param  = "We've sent an email to the email attached to this account. Check your inbox, if you don't see it, check junk/spam.";
                    $jsonoutput    = json_encode($myObj);
                    echo $jsonoutput;
                    exit();                        
                }
                else
                {
                    $myObj->status = "bad";
                    $myObj->param  = "Password reset request failed: Failed to send email to email attached to account.";
                    $jsonoutput    = json_encode($myObj);
                    echo $jsonoutput;
                    exit();                    
                } */
        }
    }
    
    $myObj->status = "bad";
    $myObj->param  = "Unable to send password reset link. We aren't able to find an account with this username in our records.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();  
}

if ($type == "requestUsernames")
{
    if (strlen($user) < 2) {
        $myObj->status = "bad";
        $myObj->param  = "Bad Email.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit(); 
    }
    
    
    $conn = GetDB(AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $user = strip_tags($conn->real_escape_string($user));
    
    if (!filter_var($user, FILTER_VALIDATE_EMAIL)) 
    {
        $myObj->status = "bad";
        $myObj->param  = "Incorrect email format.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit(); 
    }

    $loop = mysqli_query($conn, "SELECT * FROM UserDB WHERE email = '$user'") or die(mysqli_error($conn));
    
    $emails = "";

    while ($parts = mysqli_fetch_array($loop)) {
        if ($user == $parts['email']) {
            /* Bad Email Search -> Email Account Holder Unsubscribed */
            $bademaildbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
            $loop_emaildb = mysqli_query($bademaildbconn, "SELECT * FROM BadEmailDB");
            while ($emailblocklist = mysqli_fetch_array($loop_emaildb))
            {
                if ($emailblocklist['email'] == $parts['email']){
                    $myObj->status = "bad";
                    $myObj->param  = "Unable to send password reset link. We are not able to send email's to the email address on file. Err Code UNSUBSCRIBEDBADEMAIL";
                    $jsonoutput    = json_encode($myObj);
                    echo $jsonoutput;
                    exit();                      
                }
            }
            
                $emails = $emails . $parts['username'] . ", ";
        }
    }
    
    if (strlen($emails) <= 2)
    {
        $myObj->status = "bad";
        $myObj->param  = "No accounts match this record.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit(); 
    }
    
    $emails = substr($emails, 0, -2);
    
    echo file_get_contents("https://www.sirhurt.net/login/emailapi/forgotusername.php?sec=SDHKJSHDKJSGDJKS&emladr=" . urlencode($user) . "&tokeen=" . urlencode($emails) . "&betken=" . urlencode(BlowFishEncryptionManual($user, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")));
    exit();
}

$myObj->status = "bad";
$myObj->param  = "Unknown problem. Please try again.";
$jsonoutput    = json_encode($myObj);
echo $jsonoutput;
exit();
?>