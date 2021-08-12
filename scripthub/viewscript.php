<?php
require("ScriptHubApi.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/subscription.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Referral_System.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/SecurityHandler.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/RankTitles.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Suspension_System.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

//exit();


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
    if (Is_UserAgent_Excluded($_SERVER["HTTP_USER_AGENT"]) == "FAIL")
    {
        header("Location: https://www.sirhurt.net/login/login.html");
        die();
    }
}

$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec
$conn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$cookiesec_cookie = strip_tags($conn->real_escape_string($cookiesec_cookie));
$username = strip_tags($conn->real_escape_string($username));

$passs = strip_tags($conn->real_escape_string($username));
$Rank_Title = "";
$Is_Staff = false;
$found_account = false;

if($stmt = $conn->prepare("SELECT authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel, mac_hwid, mac_whitelistkey, mac_expire, cookiesec_expires, securitypin FROM UserDB WHERE username = ?")) {

   $stmt->bind_param("s", $passs); 
   $stmt->execute(); 
   $stmt->bind_result($authkey, $hwid, $email, $usern, $pass, $whitelistkey, $ip, $discord, $date, $expire, $flagged, $hwidlock, $lastreset, $flagreason, $cookiesec, $oldpwd, $accesslevel, $mac_hwid, $mac_whitelistkey, $mac_expire, $cookiesec_expires, $securitypin);

   while ($stmt->fetch()) 
   {

        if ($cookiesec != $cookiesec_cookie)
        {
            if (Is_UserAgent_Excluded($_SERVER["HTTP_USER_AGENT"]) == "FAIL")
            {
                header("Location: https://www.sirhurt.net/login/logout.php");
                die();
            }
        }
        
        if (Is_Cookie_Expired($usern) == "EXPIRED")
        {
            if (Is_UserAgent_Excluded($_SERVER["HTTP_USER_AGENT"]) == "FAIL"){
                header("Location: https://www.sirhurt.net/login/logout.php");
                die();
            }
        }
        
        if ($accesslevel >= 1)
        {
            $Is_Staff = true;
            $Rank_Title = GetRankFromAccessLevel($accesslevel);
        }
        
        if ($whitelistkey == "NONE" && ($mac_whitelistkey == "NONE" || strlen($mac_whitelistkey) < 3))
        {
            $Rank_Title = "Free Member";
        }
        else
        {
            $Rank_Title = "Licensed User";
        }
        
        $found_account = true;
    }
}

if ($found_account == false)
{
    if (Is_UserAgent_Excluded($_SERVER["HTTP_USER_AGENT"]) == "FAIL")
    {
        header("Location: https://www.sirhurt.net/login/logout.php");
        die();
    }
}

$flag_information = getSuspensionHistory($username, "username");
if ($flag_information[0] == "GLOBAL_BLACKLIST" || $flag_information[0] == "ACTIVE")
{
    $replace = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/login/suspended.html');
    $replace = str_replace("MODERATOR_NAME", $flag_information[4], $replace);
    $replace = str_replace("MODERATOR_NOTE", $flagreason, $replace);
    $replace = str_replace("EXPIRATION_DATE", "Never", $replace);
    echo $replace;
    exit();
}

$scriptid = $_GET["id"];
if (!isset($_GET["id"]))
{
    echo "Invalid Script ID";
    exit();
}

$scriptid = strip_tags($conn->real_escape_string($scriptid));

if (strlen($scriptid) > 14 || strlen($scriptid) < 10)
{
    echo "Please provide a Script ID";
    exit();
}

if (!ctype_alnum($scriptid))
{
    echo "Please provide a valid Script ID";
    exit();
}

$html_page = file_get_contents("viewscript.html");

$script_data = Fetch_Script_Info_By_ID($scriptid);
if (sizeof($script_data) == 0)
{
    echo "This script does not exist.";
    exit();
}


$uploader = $script_data[5];
$upload_date = $script_data[0];
$partnered = $script_data[2];
$script_id_db = $script_data[6];
$script_name_db = $script_data[7];
$script_desc_db = $script_data[10];
$script_tags_db = $script_data[9];
$script_thumbnail_db = $script_data[8];
$status_db = $script_data[3];

$script_desc_db = str_replace('\n', "<br>", strip_tags(base64_decode($script_desc_db)));

$html_page = str_replace("Script_Name", $script_name_db, $html_page);
$html_page = str_replace("Uploader_Name", $uploader, $html_page);
$html_page = str_replace("Test Description", $script_desc_db, $html_page);
$html_page = str_replace("Script_Tags", $script_tags_db, $html_page);
$html_page = str_replace("4/21/2021", $upload_date, $html_page);
$html_page = str_replace("INSTALLS_NUM", Fetch_Scripts_Installs($script_id_db), $html_page);

if ($uploader == $username)
{
    $creator_html_code = '
    <button class="btn btn-success" id="deletescript" type="button" style="color: rgb(255,255,255);background: rgb(89,48,45);margin-right: 4px;margin-top: 10px;border-style: none;" onclick="deleteScript();">Delete</button>
	<button class="btn btn-success" id="deletescript" type="button" style="color: rgb(255,255,255);background: rgb(119,98,67);margin-right: 4px;margin-top: 10px;border-style: none;" onclick="location.href=' . "'" . 'https://sirhurt.net/scripthub/editupload.php?id=SCRIPTIDREPLACE' . "'" . '">Edit Upload</button>
    ';
    
    $html_page = str_replace("<!-- CREATOR_BUTTONS -->", $creator_html_code, $html_page);
}

if ($accesslevel > 1)
{
    $html_page = str_replace("assets/img/WebHub_Unknown.png", $script_thumbnail_db, $html_page);
    
    $staff_buttons = '
    <button class="btn btn-success" id="approveupload" type="button" style="color: rgb(255,255,255);background: rgb(53,67,50);margin-right: 4px;margin-top: 10px;border-style: none;" onclick="ModeratorApproveUpload();">Approve</button>
	<button class="btn btn-success" id="declineupload" type="button" style="color: rgb(255,255,255);background: rgb(126,57,53);margin-right: 4px;margin-top: 10px;border-style: none;" onclick="DeclineUpload();">Decline</button>
    ';
    
    $html_page = str_replace("<!-- STAFF BUTTONS -->", $staff_buttons, $html_page);
}

if ($status_db != "PENDING_REVIEW")
{
    $html_page = str_replace("assets/img/WebHub_Unknown.png", $script_thumbnail_db, $html_page);
    $html_page = str_replace("This script has not yet been reviewed by our staff team. We cannot guarantee unreviewed uploads are functional or safe to use. Use at your own risk.", "This script has been approved by our staff team. While this script has been tested and marked as functional, we cannot guarantee the complete safety of any user-uploaded third party scripts. We do not endorse third party scripts. Use at your own risk.", $html_page);
    $html_page = str_replace("rgb(205,77,77)", "rgb(73,211,95)", $html_page);
    $html_page = str_replace("assets/img/error_mark.png", "assets/img/checkmark_1.png", $html_page);
}

$html_page = str_replace("SCRIPTIDREPLACE", $script_id_db, $html_page);

if (Does_User_Have_Installed($username, $script_id_db))
{
    $html_page = str_replace("rgb(49,69,54)", "rgb(102,59,56)", $html_page);
    $html_page = str_replace('onclick="installScript();">Install', 'onclick="uninstallScript();">Uninstall', $html_page);
}


echo $html_page;
?>