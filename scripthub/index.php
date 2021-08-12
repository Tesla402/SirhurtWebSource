<?php
require("ScriptHubApi.php");
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/subscription.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Referral_System.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/SecurityHandler.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/RankTitles.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . '/login/js-php/Suspension_System.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

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
            if (Is_UserAgent_Excluded($_SERVER["HTTP_USER_AGENT"]) == "FAIL")
            {
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

function Calculate_Row_Codes($script_data)
{
    $pending_review_html = '
    <p class="text-truncate" id="sdate-5" style="background: #252525;margin-left: 3px;margin-top: -6px;color: rgb(237,148,148);">Pending Review</p>
    ';
    
    $reviewed_html = '
    <p class="text-truncate" id="sdate-5" style="background: #252525;margin-left: 3px;margin-top: -6px;color: rgb(92,159,86);">Reviewed</p>
    ';
    
    $element_html = '
    <div class="col" style="cursor: pointer; background: #252525;border-style: solid;border-color: rgb(51,51,51);margin-left: 16px;max-width: 15%;max-height: auto;min-height: auto;height: auto;margin-top: 0px;padding-top: 0px;padding-bottom: 0px;margin-bottom: 0px;" onclick="location.href=REDIRECT_LINK;"><img src="assets/img/WebHub_Unknown.png" style="display: block;margin-left: auto;margin-right: auto;width: 80%;background: #252525;margin-top: 10px;">
    <hr style="background: #636363;">
    <div>
    <h6 class="text-truncate" style="padding-left: 3px;width: 100%;">SCRIPT_TITLE</h6>
    </div>
    <div style="margin-bottom: 0px;padding-bottom: 0px;padding-top: 0px;font-size: 12px;color: rgb(125,125,125);">
    <p class="text-truncate" id="sdate" style="background: #252525;margin-left: 3px;margin-top: -6px;color: rgb(160,159,159);">UPDATED_DATE</p>
    <!-- IS_REVIEW -->
    <p class="text-truncate" style="background: #252525;margin-left: 3px;">By UPLOADER_NAME</p>
    </div>
    </div>
    ';
    
    $row_html_code = '
    <div class="row" style="padding-left: 0px;padding-right: 0px;margin-left: 2px;height: 100%;width: 99.5%;margin-bottom: 10px;">
     <!-- ELEMENT_CODE -->
    </div>
    ';

    $elements = 0;
    
    $row_html_create = $row_html_code;
    $element_html_create = "";
    
    foreach ($script_data as $array_element)  
    {
        $element_html_new = str_replace("UPDATED_DATE", $array_element[0], $element_html);
        $element_html_new = str_replace("SCRIPT_TITLE", $array_element[5], $element_html_new);
        $element_html_new = str_replace("REDIRECT_LINK", "'https://sirhurt.net/scripthub/viewscript.php?id=" . $array_element[4] . "'", $element_html_new);

        if ($array_element[2] == "PENDING_REVIEW")
        {
            $element_html_new = str_replace("<!-- IS_REVIEW -->", $pending_review_html, $element_html_new);
        }
        else if ($array_element[2] == "APPROVED")
        {
            $element_html_new = str_replace("<!-- IS_REVIEW -->", $reviewed_html, $element_html_new);
        }
        
        if ($array_element[2] != "PENDING_REVIEW")
        {
            $element_html_new = str_replace("assets/img/WebHub_Unknown.png", $array_element[6], $element_html_new);
        }
        
        $element_html_new = str_replace("UPLOADER_NAME", $array_element[3], $element_html_new);
        
        if ($elements % 6 == 0 && $elements > 0) //multiple of 6
        {
            $row_html_create = str_replace("<!-- ELEMENT_CODE -->", $element_html_create, $row_html_create);
            $row_html_create = $row_html_create . $row_html_code;
            $element_html_create = "";
            $elements = 0;
        }
        
        $element_html_create = $element_html_create . $element_html_new;
    
        
        $elements++;
    }

    /* finish up extra rows */
    if ($elements % 6 != 0 || $elements > 0)
    {
        $row_html_create = str_replace("<!-- ELEMENT_CODE -->", $element_html_create, $row_html_create);
    }
    
    return $row_html_create;
}

$html_page = file_get_contents("homepage.html");

$script_data = array();

if (isset($_GET["search"]))
{
    $script_data = Fetch_Uploaded_Scripts_By_Search_Terms($_GET["search"]);
}
else
{
    $script_data = Fetch_Uploaded_Scripts();
}

$allscripts_html_code = Calculate_Row_Codes($script_data);

/* build script data for installed */
    $installed_data = Fetch_Install_Entry($username);
    $hasinstalled = true;
    
    if (sizeof($installed_data) == 0)
    {
        $hasinstalled = false;
    }
    
    if ($hasinstalled == false || $installed_data[1] == "NONE")
    {
        $hasinstalled = false;
    }
    
    
    if ($hasinstalled == true)
    {
        $installs_array = json_decode($installed_data[1]);
        
        $installed_scripts_array = Fetch_User_Installed_Scripts($installs_array);
        
        $allinstalledscripts_code = Calculate_Row_Codes($installed_scripts_array);
        
        $html_page = str_replace("<!-- INSERT_ROWS 2 -->", $allinstalledscripts_code, $html_page);
    }
/* end build for installed */

/* user uploads tab */
$user_uploads = Fetch_Users_Uploads($username);

if (sizeof($user_uploads) > 0)
{
    $alluploadedscripts_code = Calculate_Row_Codes($user_uploads);
    
    $html_page = str_replace("<!-- INSERT_ROWS 3 -->", $alluploadedscripts_code, $html_page);
}

/* end user uploads tab */

$html_page = str_replace("<!-- INSERT_ROWS -->", $allscripts_html_code, $html_page);
$html_page = str_replace("Username_Here", $username, $html_page);

echo $html_page;
?>