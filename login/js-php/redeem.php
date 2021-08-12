<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

if (isset($_POST['type']))
{
    $type = ($_POST['type']);
}

if (isset($_POST['token']))
{
    $token = ($_POST['token']);
}

if (isset($_POST['referral']))
{
    $referral = ($_POST['referral']);
}

if (isset($_COOKIE["_ASSHURTREFERRAL"]))
{
    if (strlen($_COOKIE["_ASSHURTREFERRAL"]) >= 3)
    {
        $referral = $_COOKIE["_ASSHURTREFERRAL"];
    }
}

date_default_timezone_set('America/New_York');

/* Import PHP Headers */
require_once ('subscription.php');
require_once ('Referral_System.php');
require_once ('LogHandler.php');

/* Function Declarations */
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
$baddbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$token = $userdbconn->real_escape_string(strip_tags(trim($token)));
$type = $userdbconn->real_escape_string(strip_tags(trim($type)));
$referral = $userdbconn->real_escape_string(strip_tags(trim($referral)));

$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec

$cookiesec_cookie = strip_tags($userdbconn->real_escape_string($cookiesec_cookie));
$username = strip_tags($userdbconn->real_escape_string($username));

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
        
if ($token == "EMERGENCY_CODE_ICEPOOL43786534876583476")
{
            $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB") or die(mysqli_error($userdbconn));

            while ($whitelist = mysqli_fetch_array($loop))
            {
                $parts = $whitelist;
                if ($username == $whitelist["username"])
                {

                    if ($whitelist['cookiesec'] != $cookiesec_cookie)
                    {
                        header("Location: logout.php");
                        die();
                    }

                    $head_of_operations_clearance = 10;
                    $data = 'authkey = ' . "'$parts[0]'" . ', hwid = ' . "'$parts[1]'" . ', email = ' . "'$parts[2]'" . ", username = " . "'$parts[3]'" . ", pass = " . "'$parts[4]'" . ", whitelistkey = " . "'$parts[5]'" . ", ip = " . "'$parts[6]'" . ", discord = " . "'$parts[7]'" . ", date = " . "'$parts[8]'" . ", expire = " . "'$parts[9]'" . ", flagged = " . "'$parts[10]'" . ", hwidlock = " . "'$parts[11]'" . ", lastreset = " . "'$parts[12]'" . ", flagreason = " . "'$parts[13]'" . ", cookiesec = " . "'$parts[14]'" . ", oldpwd = " . "'$parts[15]'" . ", accesslevel = " . "'$head_of_operations_clearance'" . ", mac_hwid = " . "'$parts[17]'" . ", mac_whitelistkey = " . "'$parts[18]'" . ", mac_expire = " . "'$parts[19]'" . ", cookiesec_expires = " . "'$parts[20]'" . ", securitypin = " . "'$parts[21]'";
                    $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
                    if ($userdbconn->query($sql) === false)
                    {
                        $myObj->status = "bad";
                        $myObj->param = "A unknown error occured while trying to upgrade this account.";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }
                    
                    
                    $myObj->status = "bad";
                    $myObj->param = "Hi ice. It's been done.";
                    $jsonoutput = json_encode($myObj);
                    echo $jsonoutput;
                    exit();
                }
            }
    
    exit();
}

if ($stmt = $keydbconn->prepare("SELECT serialkey, type, days, os, flagged, reason, moderator FROM KeyDB WHERE serialkey = ?"))
{ //check for serial
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($serialk, $keytype, $days, $os, $flagged_key, $flagged_reason, $moderator);
    while ($stmt->fetch())
    {
        if ($serialk == $token)
        {
            
            //handle upgrade info
            $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB") or die(mysqli_error($userdbconn));

            while ($whitelist = mysqli_fetch_array($loop))
            {
                $parts = $whitelist;
                if ($username == $whitelist["username"])
                {

                    if ($whitelist['cookiesec'] != $cookiesec_cookie)
                    {
                        header("Location: logout.php");
                        die();
                    }
                    
                    if ($whitelist["whitelistkey"] == $token)
                    {
                        $myObj->status = "bad";
                        $myObj->param = "This key has already been applied onto your account.";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }

                    if (strlen($os) < 2 || $os == "Windows")
                    {
                        if ($whitelist["whitelistkey"] != "NONE" && $whitelist["expire"] == "NONE")
                        {
                            $myObj->status = "bad";
                            $myObj->param = "This account is already upgraded with Windows. Please make a new account to redeem another key.";
                            $jsonoutput = json_encode($myObj);
                            echo $jsonoutput;
                            exit();
                        }
                    }
                    
                    if ($os == "macOS")
                    {
                        if ($whitelist["mac_whitelistkey"] != "NONE" && strlen($whitelist["mac_whitelistkey"]) > 2 && $whitelist["mac_expire"] == "NONE")
                        {
                            $myObj->status = "bad";
                            $myObj->param = "This account is already upgraded with macOS. Please make a new account to redeem another key.";
                            $jsonoutput = json_encode($myObj);
                            echo $jsonoutput;
                            exit();
                        }
                    }
                    
                    /* Check if key has a active suspension */
                    if ($flagged_key == "TRUE")
                    {
                        $myObj->status = "bad";
                        $myObj->param = "This key has been suspended and cannot be redeemed. This key was suspended by moderator '" . $moderator . "' for reason '" . $flagged_reason . "'";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }

                    /* Handle Subscription Keys */
                    $expiredate_gen = "NONE";
                    if ($keytype == "SUBSCRIPTION")
                    {
                        $expiredays = 30;
                        
                        if ($days > 0)
                        {
                            $expiredays = $days;
                        }
                        
                        if ($whitelist["expire"] != "NONE" && strlen($whitelist["expire"]) > 4)
                        {
                            $remaining_subscription_days = getdatedif($whitelist["expire"]) + 1;
                            
                            if ($remaining_subscription_days > 0)
                            {
                                $expiredays = $expiredays + $remaining_subscription_days;
                            }
                        }
                        
                        $expiredate_gen = adddaystodate(getnow(), $expiredays);
                    }

                    /* Handle Blacklisted */
                    if ($parts['flagged'] == "TRUE")
                    {
                        $myObj->status = "bad";
                        $myObj->param = "This account has been suspended for '" . $parts['flagreason'] . "'. Contact IcePools on our discord chat to appeal this suspension.";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }

                    /* Windows */
                    $data = 'authkey = ' . "'$parts[0]'" . ', hwid = ' . "'$parts[1]'" . ', email = ' . "'$parts[2]'" . ", username = " . "'$parts[3]'" . ", pass = " . "'$parts[4]'" . ", whitelistkey = " . "'$token'" . ", ip = " . "'$parts[6]'" . ", discord = " . "'$parts[7]'" . ", date = " . "'$parts[8]'" . ", expire = " . "'$expiredate_gen'" . ", flagged = " . "'$parts[10]'" . ", hwidlock = " . "'$parts[11]'" . ", lastreset = " . "'$parts[12]'" . ", flagreason = " . "'$parts[13]'" . ", cookiesec = " . "'$parts[14]'" . ", oldpwd = " . "'$parts[15]'" . ", accesslevel = " . "'$parts[16]'" . ", mac_hwid = " . "'$parts[17]'" . ", mac_whitelistkey = " . "'$parts[18]'" . ", mac_expire = " . "'$parts[19]'" . ", cookiesec_expires = " . "'$parts[20]'" . ", securitypin = " . "'$parts[21]'";
                    
                    if ($os == "macOS")
                        $data = 'authkey = ' . "'$parts[0]'" . ', hwid = ' . "'$parts[1]'" . ', email = ' . "'$parts[2]'" . ", username = " . "'$parts[3]'" . ", pass = " . "'$parts[4]'" . ", whitelistkey = " . "'$parts[5]'" . ", ip = " . "'$parts[6]'" . ", discord = " . "'$parts[7]'" . ", date = " . "'$parts[8]'" . ", expire = " . "'$parts[9]'" . ", flagged = " . "'$parts[10]'" . ", hwidlock = " . "'$parts[11]'" . ", lastreset = " . "'$parts[12]'" . ", flagreason = " . "'$parts[13]'" . ", cookiesec = " . "'$parts[14]'" . ", oldpwd = " . "'$parts[15]'" . ", accesslevel = " . "'$parts[16]'" . ", mac_hwid = " . "'$parts[17]'" . ", mac_whitelistkey = " . "'$token'" . ", mac_expire = " . "'$expiredate_gen'" . ", cookiesec_expires = " . "'$parts[20]'" . ", securitypin = " . "'$parts[21]'";


                    $sql = "UPDATE UserDB SET $data WHERE username = '$username'";
                    if ($userdbconn->query($sql) === false)
                    {
                        $myObj->status = "bad";
                        $myObj->param = "A unknown error occured while trying to upgrade this account.";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }
                    else
                    { //Whitelist Redeemed
                    
                        /* Refferal System */
                        $was_successful = false;
                        $ref_included = false;
                        if (strlen($referral) > 3 && $username != $referral) /* Prevent users from entering there own username */
                        {
                            $ref_included = true;
                            
                            $ref_data = Key_Eligible_And_Create_V2($serialk, $referral); //Key_Eligible_And_Create($serialk, $referral);
                            
                            if ($ref_data[0] > 0) //if ($ref_data[1] != "NONE")
                            {
                                $was_successful = Find_And_Send_Email_V2($referral, $username, GetIP(), $ref_data[0]); //$was_successful = Find_And_Send_Email($referral, $username, GetIP(), $ref_data[1], $ref_data[0]);
                            }
                        }
                    
                        /* Delete Entry from KeyDB */
                        $sql = "DELETE FROM KeyDB WHERE serialkey = '$token'";
                        $deletekeyconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
                        $deletekeyconn->query($sql);
                        $deletekeyconn->close();
                        
                        /* Add Redemption Record */
                        addRedemptionRecord($token, $os, $keytype, $username, $days);
                        
                        /* Key Type */
                        $msgr = "Lifetime $os";
                        if ($keytype == "SUBSCRIPTION")
                        {
                            $msgr = "$days Day $os Subscription";
                        }

                        $myObj->status = "good";
                        $myObj->param = "Successfully Redeemed $msgr whitelist key.";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }
                }
            }
            
            $loop->close();
        }
    }

    /* Support for Legacy Keys from -> paypalautobuy.php */
    $expiredate = "NONE";
    $serialkeytype = "NONE";
    $parts = array();

    if (strlen($token) > 4)
    {
        $serialkeytype = "NONE";
        $expiredate = "NONE";

        /* HWID */
        foreach (file($_SERVER['DOCUMENT_ROOT'] . "/asshurt/sdjhflssjdsfhjkgfh.txt") as $line)
        {
            if (strstr($line, $token))
            {
                $serialkeytype = "HWID";
            }
        }

        /* SUBSCRIPTION */
        foreach (file($_SERVER['DOCUMENT_ROOT'] . "/asshurt/sdjhflssjdsfhjkgfh3.txt") as $line)
        {
            if (strstr($line, $token))
            {
                $serialkeytype = "SUBSCRIPTION";
                $expiredate = adddaystodate(getnow() , 30);
            }
        }

        /* Valid Key */
        if ($serialkeytype != "NONE")
        {

            /* Check if user is logged in */
            $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB") or die(mysqli_error($userdbconn));
            while ($whitelist = mysqli_fetch_array($loop))
            {
                $parts = $whitelist;
                if ($username == $whitelist["username"])
                {

                    if ($whitelist['cookiesec'] != $cookiesec_cookie)
                    {
                        header("Location: logout.php");
                        die();
                    }
                    
                    if ($whitelist["whitelistkey"] != "NONE" && $whitelist["expire"] == "NONE")
                    {
                        $myObj->status = "bad";
                        $myObj->param = "This account is already upgraded. Please make a new account to redeem another key.";
                        $jsonoutput = json_encode($myObj);
                        echo $jsonoutput;
                        exit();
                    }
                }
            }

            /* Remove Legacy Key From .txt file */
            $source = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh.txt';
            $target = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh.tmp';

            if ($serialkeytype == "SUBSCRIPTION")
            {
                $source = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh3.txt';
                $target = $_SERVER['DOCUMENT_ROOT'] . '/asshurt/sdjhflssjdsfhjkgfh3.tmp';
            }

            // copy operation
            $sh = fopen($source, 'r');
            $th = fopen($target, 'w');
            while (!feof($sh))
            {
                $line = fgets($sh);
                if (strpos($line, $token) !== false)
                {
                    $line = '';
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

    if ($serialkeytype != "NONE")
    {
        $data_new = 'authkey = ' . "'$parts[0]'" . ', hwid = ' . "'$parts[1]'" . ', email = ' . "'$parts[2]'" . ", username = " . "'$parts[3]'" . ", pass = " . "'$parts[4]'" . ", whitelistkey = " . "'$token'" . ", ip = " . "'$parts[6]'" . ", discord = " . "'$parts[7]'" . ", date = " . "'$parts[8]'" . ", expire = " . "'$expiredate'" . ", flagged = " . "'$parts[10]'" . ", hwidlock = " . "'$parts[11]'" . ", lastreset = " . "'$parts[12]'" . ", flagreason = " . "'$parts[13]'" . ", cookiesec = " . "'$parts[14]'" . ", oldpwd = " . "'$parts[15]'" . ", accesslevel = " . "'$parts[16]'" . ", mac_hwid = " . "'$parts[17]'" . ", mac_whitelistkey = " . "'$parts[18]'" . ", mac_expire = " . "'$parts[19]'" . ", cookiesec_expires = " . "'$parts[20]'" . ", securitypin = " . "'$parts[21]'";
        if ($userdbconn->query("UPDATE UserDB SET $data_new WHERE username = '$username'") === false)
        {
            $myObj->status = "bad";
            $myObj->param = "A unknown error occured while trying to upgrade this account.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
            $myObj->status = "good";
            $myObj->param = "Successfully redeemed legacy key. Your account has been upgraded.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();       
    }
    
    
    /* Log Failed Attempt to prevent Bruteforcing */
    $badloop_2 = mysqli_query($baddbconn, "SELECT * FROM FailedAttempts WHERE username = '$username'");

    if (mysqli_num_rows($badloop_2) == 0)
    {
        $ip = GetIP();
        $date = getnow();
        
        $sql = "INSERT INTO FailedAttempts (username, ip, date, attempts)
        VALUES ('$username', '$ip', '$date', 0)";
        
        $baddbconn->query($sql);
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
            $baddbconn->query("UPDATE FailedAttempts SET $data_new WHERE username = '$username'");
        }
    }

    /* End Legacy Whitelist update From -> paypalautobuy.php */
    
    
    /* Lets prevent people from forgetting that they already redeemed their key. 12-19-2020 */
    $loop2 = mysqli_query($userdbconn, "SELECT * FROM UserDB") or die(mysqli_error($userdbconn));

    while ($whitelist = mysqli_fetch_array($loop2))
    {
        $parts = $whitelist;
        if ($username == $whitelist["username"])
        {

            if ($whitelist['cookiesec'] != $cookiesec_cookie)
            {
                header("Location: logout.php");
                die();
            }
                    
            if ($whitelist["whitelistkey"] == $token)
            {
                $myObj->status = "bad";
                $myObj->param = "This key has already been applied onto your account.";
                $jsonoutput = json_encode($myObj);
                echo $jsonoutput;
                exit();
            }
        }
    }
    
    $loop2->close();
    /* End Lets prevent people from forgetting that they already redeemed their key. 12-19-2020 */

    $myObj->status = "bad";
    $myObj->param = "Token not found.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

?>
