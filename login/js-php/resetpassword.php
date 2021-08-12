<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

$type = "resetPassword";

if (isset($_POST['user']))
{
    $user = ($_POST['user']);
}

if (isset($_POST['newp']))
{
    $pass = ($_POST['newp']);
}

if (isset($_POST['verifyp']))
{
    $vpass = ($_POST['verifyp']);
}

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
    return openssl_decrypt(base64_decode($part2), 'BF-CFB', $key, true, $iv);
}

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

if (!isset($_COOKIE["_ASSHURTSECTOKEN"]))
{
    header("Location: login.php");
    die();
}

$res = post_captcha($_POST['g-recaptcha-response']);
if (!$res['success']) {
    $myObj->status = "bad";
    $myObj->param  = "Captcha Failed";
    $jsonoutput    = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec

$userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$cookiesec_cookie = strip_tags($userdbconn->real_escape_string($cookiesec_cookie));

$type = $userdbconn->real_escape_string(strip_tags(trim($type)));
$user = $userdbconn->real_escape_string(strip_tags(trim($user)));
$pass = $userdbconn->real_escape_string(strip_tags(trim($pass)));
$vpass = $userdbconn->real_escape_string(strip_tags(trim($vpass)));

//$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username

if ($type == "resetPassword")
{
    $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB WHERE `cookiesec` LIKE '$cookiesec_cookie'") or die (mysqli_error($userdbconn));
    $invalid_cookie = true;
    
    while ($whitelist = mysqli_fetch_array($loop)) 
    {
        if ($cookiesec_cookie == $whitelist["cookiesec"])
        {
            $invalid_cookie = false;
            /* Found our Whitelist */
            
            if ($vpass != $pass){
                    $myObj->status = "bad";
                    $myObj->param  = "Passwords do not match. Please check them and try again.";
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
            
            if (strlen($pass) > 40){
                $myObj->status = "bad";
                $myObj->param = "Passwords cannot be longer than 40 characters.";
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
            
            $username = $whitelist['username'];
            $newcookiesec = generateRandomString(9);
            
            
            $pwd_salt = substr($pass, 0, 2);
            $rand_salt = md5($pass . "_6g{m#UhG+2nuNx" . $pwd_salt);
            //$hashedpass = md5($pass . $rand_salt); 
            $hashedpass = password_hash($pass . $rand_salt, PASSWORD_ARGON2ID); //sha256_hash($pass . $rand_salt);
            
            $data = 'authkey = ' . "'$whitelist[0]'" . ', hwid = ' . "'$whitelist[1]'" . ', email = ' . "'$whitelist[2]'" . ", username = " . "'$whitelist[3]'" . ", pass = " . "'$hashedpass'" . ", whitelistkey = " . "'$whitelist[5]'" . ", ip = " . "'$whitelist[6]'" . ", discord = " . "'$whitelist[7]'" . ", date = " . "'$whitelist[8]'" . ", expire = " . "'$whitelist[9]'" . ", flagged = " . "'$whitelist[10]'" . ", hwidlock = " . "'$whitelist[11]'" . ", lastreset = " . "'$whitelist[12]'". ", flagreason = " . "'$whitelist[13]'" . ", cookiesec = " . "'$newcookiesec'" . ", oldpwd = " . "'$whitelist[15]'" . ", accesslevel = " . "'$whitelist[16]'" . ", mac_hwid = " . "'$whitelist[17]'" . ", mac_whitelistkey = " . "'$whitelist[18]'" . ", mac_expire = " . "'$whitelist[19]'" . ", cookiesec_expires = " . "'$whitelist[20]'" . ", securitypin = " . "'$whitelist[21]'";
            $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
            if ($userdbconn->query($sql) === TRUE) 
            {
                $myObj->status = "good";
                $myObj->param = "Your password has been successfully changed. Please login again at https://sirhurt.net/login/login.php";
                $jsonoutput = json_encode($myObj);
                echo $jsonoutput;
                exit(); 
            }
        }
    }
    
    if ($invalid_cookie == true)
    {
        $myObj->status = "bad";
        $myObj->param = "We were unable to verify the SirHurt cookies. Please try logging in again.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit(); 
    }
    
    $myObj->status = "bad";
    $myObj->param = "A unknown error occured while trying to reset this account.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit(); 
}
?>