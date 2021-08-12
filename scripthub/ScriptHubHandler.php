<?php
require("ScriptHubApi.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/subscription.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Referral_System.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/SecurityHandler.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/RankTitles.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Suspension_System.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

$found_account = false;

if (isset($_POST['type']))
{
    $type = ($_POST['type']);
}

if (isset($_POST['scriptname']))
{
    $scriptname = strip_tags(trim($_POST['scriptname']));
}

if (isset($_POST['scripttags']))
{
    $scripttags = strip_tags(trim($_POST['scripttags']));
}

if (isset($_POST['scriptdescription']))
{
    $scriptdescription = strip_tags(trim($_POST['scriptdescription']));
}

if (isset($_POST['script_source']))
{
    $script_source = strip_tags(trim($_POST['script_source']));
}

if (isset($_POST['scriptthumbnail']))
{
    $script_thumbnail = strip_tags(trim($_POST['scriptthumbnail']));
}

if (isset($_POST['scriptid']))
{
    $scriptid = strip_tags(trim($_POST['scriptid']));
}


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

function Suspend_Account($secusername, $reason)
{
    $userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB");
    
    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($secusername == $whitelist["username"])
        {
            $username = $whitelist['username'];
            
            $secdata = $whitelist;
            $data = 'authkey = ' . "'$secdata[0]'" . ', hwid = ' . "'$secdata[1]'" . ', email = ' . "'$secdata[2]'" . ", username = " . "'$secdata[3]'" . ", pass = " . "'$secdata[4]'" . ", whitelistkey = " . "'$secdata[5]'" . ", ip = " . "'$secdata[6]'" . ", discord = " . "'$secdata[7]'" . ", date = " . "'$secdata[8]'" . ", expire = " . "'$secdata[9]'" . ", flagged = " . "'TRUE'" . ", hwidlock = " . "'$secdata[11]'" . ", lastreset = " . "'$secdata[12]'" . ", flagreason = " . "'$reason'" . ", cookiesec = " . "'$secdata[14]'" . ", oldpwd = " . "'$secdata[15]'" . ", accesslevel = " . "'$secdata[16]'" . ", mac_hwid = " . "'$secdata[17]'" . ", mac_whitelistkey = " . "'$secdata[18]'" . ", mac_expire = " . "'$secdata[19]'" . ", cookiesec_expires = " . "'$secdata[20]'" . ", securitypin = " . "'$secdata[21]'";
            $sql = "UPDATE UserDB SET $data WHERE username = '$secdata[3]'";
            if ($userdbconn->query($sql) == true)
            {
                addSuspension($whitelist["username"], $whitelist['hwid'], 0, 0, "Automatic");

                return true;
            }
        }
    }
    
    $userdbconn->close();
    
    return false;
}

if ($type == "createScript" || $type == "editScript")
{
    if (!isset($_COOKIE["_ASSHURTSTAFFTOKEN"]))
    {
        $res = post_captcha($_POST['captcha1']);
        if (!$res['success']) {
            $myObj->status = "bad";
            $myObj->param  = "Captcha Failed";
            $jsonoutput    = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
    }
}

$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec
$conn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
$passs = strip_tags($conn->real_escape_string($username));

$cookiesec_cookie = strip_tags($conn->real_escape_string($cookiesec_cookie));
$username = strip_tags($conn->real_escape_string($username));

//$identifier = str_replace('"', "", $identifier);
//$identifier = str_replace("'", "", $identifier);

$scriptdescription = ($conn->real_escape_string($scriptdescription));

$Rank_Title = "";
$Staff_Member = false;
$clearance = 0;
$Is_Buyer = false;
$Is_Subscription = false;
$Is_User_Suspended = false;

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
        
        $Is_User_Suspended = $flagged == "TRUE";
        
        if ($whitelistkey != "NONE" && $expire == "NONE")
        {
            $Is_Buyer = true;
        }
        
        if ($whitelistkey != "NONE" && $expire != "NONE")
        {
            $Is_Subscription = true;
        }
        
        if ($whitelistkey == "NONE" && ($mac_whitelistkey == "NONE" || strlen($mac_whitelistkey) < 3))
        {
            $Rank_Title = "Free Member";
        }
        else
        {
            $Rank_Title = "Licensed User";
        }
        
        $clearance = $accesslevel;
        if ($accesslevel > 0)
        {
            $Rank_Title = GetRankFromAccessLevel($accesslevel);
        }
        if ($accesslevel >= 1)
        {
            $Staff_Member = true;
        }
        
        $found_account = true;
    }
}

if ($found_account == false)
{
    header("Location: https://www.sirhurt.net/login/logout.php");
    die();
}

if ($Is_User_Suspended == true)
{
    $myObj->status = "bad";
    $myObj->param = "You are not eligible to use the scripthub in any shape or form due to your account being suspended.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "installScript")
{
    if ($Is_Buyer == false && $Is_Subscription == false)
    {
        $myObj->status = "bad";
        $myObj->param = "You are not eligible to install scripts. Only lifetime/subscription members can install/uninstall scripts.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    Update_Installs_Entries($username, "INSTALL", $scriptid);
    
    $myObj->status = "good";
    $myObj->param = "Script has been installed.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "uninstallScript")
{
    if ($Is_Buyer == false && $Is_Subscription == false)
    {
        $myObj->status = "bad";
        $myObj->param = "You are not eligible to install scripts. Only lifetime/subscription members can install/uninstall scripts.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    Update_Installs_Entries($username, "UNINSTALL", $scriptid);
    
    $myObj->status = "good";
    $myObj->param = "Script has been uninstalled.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "deleteScript")
{
    $data = Fetch_Script_Info_By_ID($scriptid);
    
    if ($data[5] != $username && $clearance < 1)
    {
        $myObj->status = "bad";
        $myObj->param = "You do not have authorization to delete this upload.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $script_path = $data[11];
    
    if (Delete_Script($scriptid, $script_path) == true)
    {
        $myObj->status = "good";
        $myObj->param = "This upload has been deleted from our database.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $myObj->status = "bad";
    $myObj->param = "An unknown error occured while trying to delete this upload.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "approveScript")
{
    if ($clearance < 1)
    {
        $myObj->status = "bad";
        $myObj->param = "You do not have authorization to approve or deny scripts.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    Moderation_Update_Script($scriptid, $username, "APPROVED");
    
    $myObj->status = "good";
    $myObj->param = "Script has been marked as approved.";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "declineScript")
{
    if ($clearance < 1)
    {
        $myObj->status = "bad";
        $myObj->param = "You do not have authorization to approve or deny scripts.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $data = Fetch_Script_Info_By_ID($scriptid);
    
    if ($data[5] != $username && $clearance < 1)
    {
        $myObj->status = "bad";
        $myObj->param = "You do not have authorization to delete this upload.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $script_path = $data[11];
    
    if (Delete_Script($scriptid, $script_path) == true)
    {
        $myObj->status = "good";
        $myObj->param = "This upload has been deleted from our database.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
}

if ($type == "declineScriptWithSuspension")
{
    if ($clearance < 2)
    {
        $myObj->status = "bad";
        $myObj->param = "You do not have authorization to apply account suspensions.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $data = Fetch_Script_Info_By_ID($scriptid);
    
    if ($data[5] != $username && $clearance < 1)
    {
        $myObj->status = "bad";
        $myObj->param = "You do not have authorization to delete this upload.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $script_path = $data[11];
    
    if (Delete_Script($scriptid, $script_path) == true)
    {
        $myObj->status = "good";
        $myObj->param = "This upload has been deleted from our database.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
}

if ($type == "createScript")
{
    /*
    $myObj->status = "bad";
    $myObj->param = base64_decode($script_source);
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
    */
    
    if ($Is_Buyer == false)
    {
        $myObj->status = "bad";
        $myObj->param = "You are not eligible to upload scripts. Only lifetime members can create new uploads.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (strlen($script_source) < 5)
    {
        $myObj->status = "bad";
        $myObj->param = "Please provide a valid script source.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (strlen($scripttags) < 2 || strlen($scripttags) > 100)
    {
        $scripttags = "N/A";
    }
    
    if (strlen($scriptdescription) < 5 || strlen($scriptname) < 3 || strlen($scriptname) > 40)
    {
        $myObj->status = "bad";
        $myObj->param = "Please provide a valid script title & description.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (strlen($script_thumbnail) < 5)
    {
        $script_thumbnail = "https://sirhurt.net/scripthub/WebHub_Unknown.png";
    }
    else
    {
        if (strncmp($script_thumbnail, 'https://i.imgur.com/', strlen('https://i.imgur.com/')) !== 0 && strncmp($script_thumbnail, 'i.imgur.com/', strlen('i.imgur.com/')) !== 0)
        {
            $myObj->status = "bad";
            $myObj->param = "Please provide a valid script thumbnail link. Link must be of imgur.com orgin";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
        
        if (!preg_match('/\bpng\b/', $script_thumbnail) && !preg_match('/\bjpg\b/', $script_thumbnail)) 
        {
            $myObj->status = "bad";
            $myObj->param = "Please provide a valid script thumbnail link. Link must either be a .png or .jpg.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
    }
    
    if (strpos(strtolower($scriptname), "https") !== false || strpos(strtolower($scriptname), "synapse") !== false || strpos(strtolower($scriptname), "script-ware") !== false)
    {
       $myObj->status = "bad";
       $myObj->param = "Advertising third-party software is not allowed.";
       $jsonoutput = json_encode($myObj);
       echo $jsonoutput;
       exit();     
    }
    
    $script_source = base64_decode($script_source);
    
    if (strlen($script_source) >= 1000000)
    {
        $myObj->status = "bad";
        $myObj->param = "Your script is to big. Please put your script in a loadstring with a pastebin.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }

    $enc_key_gen = HKJhfdkjdhkhsdkhk(32);
    $script_path_gen = HKJhfdkjdhkhsdkhk(15) . ".lua";
    
    $protected_script_src = Blowfish_Enc($script_source, $enc_key_gen, "G+KbPdSgVkYp3s6v");
    
    
    $blacklisted_terms = array("pedo", "p3do", "p3d0", "ped0", "p e d o");
    foreach ($blacklisted_terms as $array_element)
    {
        if (strpos(strtolower($scripttags), $array_element) !== false || strpos(strtolower($scriptname), $array_element) !== false || strpos(strtolower($scriptdescription), $array_element) !== false) 
        {
            Suspend_Account($username, "Automatic Suspension for inappropriate scripthub upload containing blacklisted word $array_element");
            
            $myObj->status = "bad";
            $myObj->param = "You're account has been suspended due to inappropriate misusage of the SirHurt ScriptHub.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
    }
    
    
    $ret_script_id = Upload_New_Script($username, $scriptname, $script_thumbnail, $scripttags, $scriptdescription, $script_path_gen, $enc_key_gen);
    
    if ($ret_script_id == "ERROR_INVALID_SCRIPT_LENGTH" || $ret_script_id == "ERROR_INVALID_NAME" || $ret_script_id == "ERROR_INVALID_DESCRIPTION" || $ret_script_id == "ERROR_INVALID_SCRIPT_TAGS")
    {
        $myObj->status = "bad";
        $myObj->param = "An error occured while publishing. Error code $ret_script_id";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    Update_Installs_Entries($username, "CREATED", $ret_script_id);
    
    Create_Script_Path($protected_script_src, $script_path_gen);
    
    $myObj->status = "good";
    $myObj->param = "https://sirhurt.net/scripthub/viewscript.php?id=$ret_script_id";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

if ($type == "editScript")
{
    if ($Is_Buyer == false)
    {
        $myObj->status = "bad";
        $myObj->param = "You are not eligible to upload scripts. Only lifetime members can create new uploads.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    /*if (strlen($script_source) < 5)
    {
        $myObj->status = "bad";
        $myObj->param = "Please provide a valid script source.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }*/
    
    if (strlen($scripttags) < 2)
    {
        $scripttags = "N/A";
    }
    
    if (strlen($scriptdescription) < 5 || strlen($scriptname) < 3 || strlen($scriptname) > 40)
    {
        $myObj->status = "bad";
        $myObj->param = "Please provide a valid script title & description.";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    if (strlen($script_thumbnail) < 5)
    {
        $script_thumbnail = "https://sirhurt.net/scripthub/WebHub_Unknown.png";
    }
    else
    {
        if (strncmp($script_thumbnail, 'https://i.imgur.com/', strlen('https://i.imgur.com/')) !== 0 && strncmp($script_thumbnail, 'i.imgur.com/', strlen('i.imgur.com/')) !== 0)
        {
            $myObj->status = "bad";
            $myObj->param = "Please provide a valid script thumbnail link. Link must be of imgur.com orgin";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
        
        if (!preg_match('/\bpng\b/', $script_thumbnail) && !preg_match('/\bjpg\b/', $script_thumbnail)) 
        {
            $myObj->status = "bad";
            $myObj->param = "Please provide a valid script thumbnail link. Link must either be a .png or .jpg.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
    }
    
    $blacklisted_terms = array("pedo", "p3do", "p3d0", "ped0", "p e d o");
    foreach ($blacklisted_terms as $array_element)
    {
        if (strpos(strtolower($scripttags), $array_element) !== false || strpos(strtolower($scriptname), $array_element) !== false || strpos(strtolower($scriptdescription), $array_element) !== false) 
        {
            Suspend_Account($username, "Automatic Suspension for inappropriate scripthub upload containing blacklisted word $array_element");
            
            $myObj->status = "bad";
            $myObj->param = "You're account has been suspended due to inappropriate misusage of the SirHurt ScriptHub.";
            $jsonoutput = json_encode($myObj);
            echo $jsonoutput;
            exit();
        }
    }
    
    if (strpos(strtolower($scriptname), "https") !== false || strpos(strtolower($scriptname), "synapse") !== false || strpos(strtolower($scriptname), "script-ware") !== false)
    {
       $myObj->status = "bad";
       $myObj->param = "Advertising third-party software is not allowed.";
       $jsonoutput = json_encode($myObj);
       echo $jsonoutput;
       exit();     
    }
    
    $ret_script_id = Update_Existing_Script($scriptid, $scriptname, $script_thumbnail, $scripttags, $scriptdescription, $script_source);
    
    if ($ret_script_id == "ERROR_INVALID_SCRIPT_LENGTH" || $ret_script_id == "ERROR_INVALID_NAME" || $ret_script_id == "ERROR_INVALID_DESCRIPTION" || $ret_script_id == "ERROR_INVALID_SCRIPT_TAGS")
    {
        $myObj->status = "bad";
        $myObj->param = "An error occured while publishing. Error code $ret_script_id";
        $jsonoutput = json_encode($myObj);
        echo $jsonoutput;
        exit();
    }
    
    $myObj->status = "good";
    $myObj->param = "https://sirhurt.net/scripthub/viewscript.php?id=$ret_script_id";
    $jsonoutput = json_encode($myObj);
    echo $jsonoutput;
    exit();
}

?>