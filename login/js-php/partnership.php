<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

require_once('subscription.php');
require_once('Referral_System.php');
require_once('SecurityHandler.php');
require_once('PartnerHandler.php');
require_once('KeyHandler.php');

define("HEAD_OF_OPERATIONS", 10);
define("VICE_HEAD_OF_OPERATIONS", 8);
define("SALES_REPRESENTATIVE", 7);
define("SECRETARY", 6);
define("UPPER_MANAGEMENT", 5);
define("DEVELOPER", 4);
define("ADMIN", 3);
define("SUPERVISOR", 2);
define("MODERATOR", 1);
define("HELPER", 0.4);
define("FORMER_STAFF", 0.3);
define("YOUTUBE_PARTNER", 0.2);
define("BETA_TESTER", 0.1);

if (isset($_POST['type']))
{
    $type = trim($_POST['type']);
}

if (isset($_POST['advertisement']))
{
    $advertisement = trim($_POST['advertisement']);
}

function BlowFishEncryptionManual($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2) , 'BF-CFB', $key, true, $iv);
}

function GetIP($clearance) 
{
    if ($clearance >= 7)
    return "REDACTED";
    
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
    header("Location: login.html");
    die();
}

$userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
$keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec

$cookiesec_cookie = strip_tags($userdbconn->real_escape_string($cookiesec_cookie));
$username = strip_tags($userdbconn->real_escape_string($username));

if ($type == "submitAd")
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
                
                if ($accesslevel != YOUTUBE_PARTNER && $accesslevel != HEAD_OF_OPERATIONS)
                {
                    $myObj->status = "bad";
                    $myObj->param = "You do not have the clearance to use this functionality.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                if ($discord == "NoDiscord")
                {
                    $myObj->status = "bad";
                    $myObj->param = "Hi there! To use this functionality, you must have a discord account attached to your SirHurt account. To do this, open the sirhurt discord and message 'catbot' with !ownerrole " . $whitelistkey . " or ask a staff member to manually do it for you.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                if (strlen($advertisement) < 4)
                {
                    $myObj->status = "bad";
                    $myObj->param = "Please enter an ad longer than 4 characters.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                if (strlen($advertisement) > 2000)
                {
                    $myObj->status = "bad";
                    $myObj->param = "This message exceeds discord's max message character length.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $is_function_allowed = Is_Partner_Function_Eligible($username, "LAST_AD_POSTAGE");
                
                if ($is_function_allowed == true)
                {
                    $old_data = Fetch_Partnership_Data($username);
                    
                    $next_ad_date = adddaystodate(getnow(), 1);
                    
                    $advertisement = str_replace("'", "", $advertisement);
                    
                    if (Add_Ad_For_Review(GetIP($accesslevel), getnow(), $username, $advertisement) == false)
                    {
                        $myObj->status = "bad";
                        $myObj->param = "Unable to submit ad.";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }
                    
                    UpdatePartnershipEntry(GetIP($accesslevel), $username, $old_data[0], $next_ad_date);
                    
                    
                    postToDiscord("@everyone A pending partner ad has been submitted and needs to be reviewed. Ad submitted by: " . $username, "https://discord.com/api/webhooks/835778877712236544/D50OhN8Ue_CJPkEqVBvu3II5BujllEYlpo82wivOI9tSVeKNFc42VxMuNXUQmcnZxVee", "Partnership Ad Notification");
                    
                    
                    $myObj->status = "good";
                    $myObj->param = "This ad has been submitted for review by our team and will be reviewed shortly. Thanks for being a partner!";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $myObj->status = "bad";
                $myObj->param = "You've already submitted an ad. Please wait 24 hours before submitting another.";
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

if ($type == "requestKeys")
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
                
                if ($accesslevel != YOUTUBE_PARTNER && $accesslevel != HEAD_OF_OPERATIONS)
                {
                    $myObj->status = "bad";
                    $myObj->param = "You do not have the clearance to use this functionality.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                if ($discord == "NoDiscord")
                {
                    $myObj->status = "bad";
                    $myObj->param = "Hi there! To use this functionality, you must have a discord account attached to your SirHurt account. To do this, open the sirhurt discord and message 'catbot' with !ownerrole " . $whitelistkey . " or ask a staff member to manually do it for you.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
            
                $is_function_allowed = Is_Partner_Function_Eligible($username, "LAST_KEY_REQUEST");
                
                if ($is_function_allowed == true)
                {
                    $old_data = Fetch_Partnership_Data($username);
                    
                    $next_key_date = adddaystodate(getnow(), 30);
                    
                    $lifetime_key = md5(generateRandomString(15));
                    $subscription_key = md5(generateRandomString(15));
                    
                    Make_SirHurt_Key($lifetime_key, "HWID", 0, "Windows");
                    Make_SirHurt_Key($subscription_key, "SUBSCRIPTION", 30, "Windows");
                    
                    Log_Partnership_Keys(GetIP($accesslevel), getnow(), $username, $lifetime_key . ", " . $subscription_key);
                    
                    UpdatePartnershipEntry(GetIP($accesslevel), $username, $next_key_date, $old_data[1]);
                    
                    $myObj->status = "good";
                    $myObj->param = "Lifetime: " . $lifetime_key . " 30 Day Subscription: " . $subscription_key;
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
                
                $myObj->status = "bad";
                $myObj->param = "You've already requested your monthly keys. Please wait another 30 days before submitting another request.";
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

?>