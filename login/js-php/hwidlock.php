<?php
require_once ('SecurityHandler.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

if (isset($_POST['type']))
{
    $type = ($_POST['type']);
}

date_default_timezone_set('America/New_York');

function BlowFishEncryptionManual($part2, $key, $iv){
return base64_encode(openssl_encrypt ($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv){
return openssl_decrypt (base64_decode($part2), 'BF-CFB', $key, true, $iv);
}

if(isset($_COOKIE["_ASSHURTSECURITY"])) {
$username = $_COOKIE["_ASSHURTSECURITY"];
}
else
{
header("Location: login.html");
die();
}

$userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec


$cookiesec_cookie = strip_tags($userdbconn->real_escape_string($cookiesec_cookie));
$username = strip_tags($userdbconn->real_escape_string($username));


if ($type == "resetHwid")
{
    
    
$loop = mysqli_query($userdbconn, "SELECT * FROM UserDB")
or die (mysqli_error($userdbconn));
    
while ($whitelist = mysqli_fetch_array($loop)) {
if ($username == $whitelist["username"]){
    
    if ($whitelist['cookiesec'] != $cookiesec_cookie){
        header("Location: logout.php");
        die();
    }
    
    if (Is_Cookie_Expired($username) == "EXPIRED")
    {
       header("Location: logout.php");
       die();
    }
    
    $default = 0; //0 = unlocked, 1 = locked
    $successmsg = "Something weird has happened.";
    
    /* Determine if user's first time/hasn't set HWID yet */
    if ($whitelist["lastreset"] == "NONE" && $whitelist["hwid"] == "NONE")
    {
        $myObj->status = "bad";
        $myObj->param = "Unable to lock HWID. Your HWID has not yet been set, locking your hwid would prevent it from being updated in our system after first use.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    
    /* Determine Set Type */
    if ($whitelist["hwidlock"] == 0)
    {
        $default = 1;
        $successmsg = "Successfully locked HWID.";
    }
    else
    {
        $default = 0;
        $successmsg = "Successfully unlocked HWID.";
    }
    
    $updatedate = date('m-d-Y H:i:s');
    
    
    $data = 'authkey = ' . "'$whitelist[0]'" . ', hwid = ' . "'$whitelist[1]'" . ', email = ' . "'$whitelist[2]'" . ", username = " . "'$whitelist[3]'" . ", pass = " . "'$whitelist[4]'" . ", whitelistkey = " . "'$whitelist[5]'" . ", ip = " . "'$whitelist[6]'" . ", discord = " . "'$whitelist[7]'" . ", date = " . "'$whitelist[8]'" . ", expire = " . "'$whitelist[9]'" . ", flagged = " . "'$whitelist[10]'" . ", hwidlock = " . "'$default'" . ", lastreset = " . "'$updatedate'". ", flagreason = " . "'$whitelist[13]'" . ", cookiesec = " . "'$whitelist[14]'" . ", oldpwd = " . "'$whitelist[15]'" . ", accesslevel = " . "'$whitelist[16]'" . ", mac_hwid = " . "'$whitelist[17]'" . ", mac_whitelistkey = " . "'$whitelist[18]'" . ", mac_expire = " . "'$whitelist[19]'" . ", cookiesec_expires = " . "'$whitelist[20]'" . ", securitypin = " . "'$whitelist[21]'";
    $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
    if ($userdbconn->query($sql) === TRUE) 
    {
    $myObj->status = "good";
    $myObj->param = $successmsg;
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit(); 
    }
    
}
}
    
    
}

$myObj->status = "bad";
$myObj->param = "An unknown error occured while attempting to set lock data.";
$jsonoutput = json_encode($myObj);
echo $jsonoutput;
exit();

?>