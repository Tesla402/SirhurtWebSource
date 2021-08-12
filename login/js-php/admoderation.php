<?php
date_default_timezone_set('America/New_York');
include_once($_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/KeyHandler.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/PartnerHandler.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

/* Functions */
function BlowFishDecryptionManual($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2) , 'BF-CFB', $key, true, $iv);
}

function BlowFishEncryptionManual($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
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

if (isset($_GET['cardid']))
{
    $cardid = BlowFishDecryptionManual($_GET['cardid'], "C?D(A+KbPeShCQYq3t6w9ycL&E)H@McQ", "D%C*F-Ca3dRfUjcA");
}

if (isset($_GET['reviewer']))
{
    $reviewer = BlowFishDecryptionManual($_GET['reviewer'], "C?D(A+KbPeShCQYq3t6w9ycL&E)H@McQ", "D%C*F-Ca3dRfUjcA");
}

/* Security */
$clearance_level = 0;
$secusername = "N/A";
$discordid = "N/A";

if (isset($_COOKIE["_ASSHURTSECURITY"]))
{
    if (!isset($_COOKIE["_ASSHURTSTAFFTOKEN"]))
    {
        header("Location: https://www.sirhurt.net/login/logout.php");
        die();
    }

    $secusername = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECURITY"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
    $cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec
    $cookiesec_staff_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSTAFFTOKEN"], STAFF_COOKIE_ENC_KEY, STAFF_COOKIE_ENC_IV); //decrypt cookie to get cookiesec
    $userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $cookiesec_cookie = strip_tags($userdbconn->real_escape_string($cookiesec_cookie));
    $secusername = strip_tags($userdbconn->real_escape_string($username));

    $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB");
    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($secusername == $whitelist["username"])
        {
            $clearance_level = $whitelist['accesslevel'];
            
            if ($whitelist['cookiesec'] != $cookiesec_cookie || $whitelist['cookiesec'] != $cookiesec_staff_cookie)
            {
                header("Location: https://www.sirhurt.net/login/logout.php");
                die();
            }
            
            $discordid = $whitelist['discord'];

            if ($clearance_level < 2)
            {
                echo "Invalid clearance level. You are not authorized to view this content.";
                die();
            }
        }
    }

}
else
{
    header("Location: https://www.sirhurt.net/login/login.html");
    die();
}


if (isset($_GET['cardid']))
{
    $fetched_results = FetchAdSubmissions($cardid, "SECRET");
    
    if (count($fetched_results) != 1)
    {
        echo "Something quite not right happened here..";
        exit();
    }
    
    if ($fetched_results[0][4] != "PENDING")
    {
        echo "This ad was already reviewed.";
        exit();
    }
    
    if ($_GET['decision'] == "APPROVED")
    {
        if ($discordid == "NoDiscord")
        {
            $discordid = $secusername;
        }
        else
        {
            $discordid = "<@" . $discordid . ">";
        }
        
        
        $final_message = str_replace("@here", "", urldecode($fetched_results[0][3]));
        $final_message = str_replace("@everyone", "", $final_message);

        $message = "@here Ad by: " . $fetched_results[0][2] . "\nReviewed By: " . $discordid . "\n\nMessage:\n" . $final_message;
        
        if (strpos($message, 'NO_PING') !== false)
        {
            $message = str_replace("@here ", "", $message);
            $message = str_replace("NO_PING", "", $message);
        }
        
        $message = str_replace('\n', "\n", $message);
        $message = str_replace('\r', "\r", $message);

        postToDiscord($message, "https://discord.com/api/webhooks/835779239961034763/MoY4t2R-e7arpBBjdAlH0ja8WkUwB3r4KFy5pn4YwFt6QmqV1jsqqpwgfTedMjyA_q9d", "Partnership Handler");
    }
    
    postToDiscord("Ad submission for  " . $fetched_results[0][2] . " was reviewed by " . $discordid, "https://discord.com/api/webhooks/835778877712236544/D50OhN8Ue_CJPkEqVBvu3II5BujllEYlpo82wivOI9tSVeKNFc42VxMuNXUQmcnZxVee", "Partnership Ad Notification");
    
    UpdateAdSubmission($fetched_results[0][0], $fetched_results[0][1], $fetched_results[0][2], $fetched_results[0][3], $_GET['decision'], $secusername, $fetched_results[0][6]);
    
    if ($_GET['decision'] == "DENIED")
    {
        $reset_data = Fetch_Partnership_Data($fetched_results[0][2]);
        UpdatePartnershipEntry($fetched_results[0][0], $fetched_results[0][2], $reset_data[0], "NONE"); //ip, username, LAST_KEY_REQUEST, LAST_AD_POSTAGE
    }
    
    echo "Changed Review Status To: " . $_GET['decision'];
    exit();
}

?>