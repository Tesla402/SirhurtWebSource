<?php
require_once('js-php/TrialHandler.php');
require_once('js-php/RankTitles.php');
require_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/Sales_Handler.php");
require_once('js-php/SecurityHandler.php');
require_once ($_SERVER['DOCUMENT_ROOT'] . "/asshurt/update/v4/SirHurtVersionHandler.php");
require_once ($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/TicketHandler.php");
require_once ('js-php/InfoHandler.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function post_captcha($user_response) {
        $fields_string = '';
        $fields = array(
            'secret' => '6Lcy0-EUAAAAAGlOWxzIk-pXJu986WnEqMm-tFf5',
            'response' => $user_response
        );
        foreach($fields as $key=>$value)
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

function HttpGet($url){
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $url,
    CURLOPT_USERAGENT => 'Roblox/WinInet'
]);

$resp = curl_exec($curl);

curl_close($curl);

return $resp;
}

function GetIP() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}

function BlowFishEncryptionManual($part2, $key, $iv){
return base64_encode(openssl_encrypt ($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv){
return openssl_decrypt (base64_decode($part2), 'BF-CFB', $key, true, $iv);
}

if(isset($_COOKIE["_ASSHURTSECURITY"])) 
{
    $username = $_COOKIE["_ASSHURTSECURITY"];
}
else
{
    header("Location: login.html");
    die();
}

if (!isset($_COOKIE["_ASSHURTSTAFFTOKEN"]))
{
    header("Location: logout.php");
    die();
}


$username = BlowFishDecryptionManual($username, COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get username
$cookiesec_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSECTOKEN"], COOKIE_ENC_KEY, COOKIE_ENC_IV); //decrypt cookie to get cookiesec
$cookiesec_staff_cookie = BlowFishDecryptionManual($_COOKIE["_ASSHURTSTAFFTOKEN"], STAFF_COOKIE_ENC_KEY, STAFF_COOKIE_ENC_IV); //decrypt cookie to get cookiesec

$conn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

$cookiesec_cookie = strip_tags($conn->real_escape_string($cookiesec_cookie));
$username = strip_tags($conn->real_escape_string($username));
$cookiesec_staff_cookie = strip_tags($conn->real_escape_string($cookiesec_cookie));

$passs = strip_tags($conn->real_escape_string($username));

if($stmt = $conn->prepare("SELECT authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel, mac_hwid, mac_whitelistkey, mac_expire, cookiesec_expires, securitypin FROM UserDB WHERE username = ?")) {

   $stmt->bind_param("s", $passs); 
   $stmt->execute(); 
   $stmt->bind_result($authkey, $hwid, $email, $usern, $pass, $whitelistkey, $ip, $discord, $date, $expire, $flagged, $hwidlock, $lastreset, $flagreason, $cookiesec, $oldpwd, $accesslevel, $mac_hwid, $mac_whitelistkey, $mac_expire, $cookiesec_expires, $securitypin);

   while ($stmt->fetch()) 
   {
      
    if ($cookiesec != $cookiesec_cookie || $cookiesec != $cookiesec_staff_cookie)
    {
        header("Location: logout.php");
        die();
    }
    
    if (Is_Cookie_Expired($usern) == "EXPIRED")
    {
       header("Location: logout.php");
       die();
    }
    
    if ($usern == $username){
    $replace = file_get_contents("admin.html");
    $replace = str_replace("USERNAMEHERE", $usern, $replace);
    $replace = str_replace("SETREGLATER", RegisteredUsers(), $replace);
    $replace = str_replace("VERSIONINFO", CheckBuildVersion(), $replace);

    
    /* Sales Record Addition 4/21/2020 */
    $sales_date = Record_Sales_Amount();
    
    /*$replace = str_replace("DATEHERE", $sales_date[0], $replace);
    $replace = str_replace("RECORDDATA", "$" . $sales_date[1], $replace);*/
    
    $replace = str_replace("- DATEHERE", "", $replace);
    $replace = str_replace("RECORDDATA", $sales_date[0], $replace);
    
    if (Is_SirHurt_Updated() == false)
    {
        $replace = str_replace("<h2>UPDATED</h2>", "<h2>NOT UPDATED</h2>", $replace);
    }
    
    if (IsTrialActive())
    {
        $replace = str_replace("<!--TRIAL-->", '<a href="trial.php"><div class="material-icons">get_app</div> Trial</a>', $replace);
    }
    
    if ($accesslevel == 0.2 || $accesslevel >= 7)
    {
        $replace = str_replace("<!--PARTNERSHIP-->", '<a href="partner.php"><div class="material-icons">admin_panel_settings</div> Partner Panel</a>', $replace);
    }
    
    $new_ticket_reply_count = Does_Tickets_Need_User_Reply($usern);
    if ($new_ticket_reply_count > 0) 
    {
        $replace = str_replace("Ticket Center", "Ticket Center <b>($new_ticket_reply_count New Messages)</b>", $replace);
    }
    
    if ($accesslevel >= 1){
        $replace = str_replace("<!--ADMIN-->", '<li><a href="admin.php"><i class="fas fa-flag"></i> <span class="nav-label">Admin Panel</span></a></li>', $replace);
        $replace = str_replace("PACKAGETYPEHERE", GetRankFromAccessLevel($accesslevel), $replace);
        $replace = str_replace("<!--PARTNERREVIEW-->", '<a href="reviewpartnerads.php"><div class="material-icons">admin_panel_settings</div> Partner Administration</a>', $replace);
        $replace = str_replace("<!--STAFF DISCORD-->", '<a href="https://www.discord.gg/TdkDDNnrVn"><div class="material-icons">admin_panel_settings</div> Staff Discord Server</a>', $replace);

        $open_tickets_number = Open_Ticket_Count();
        $replace = str_replace("Ticket Center", "Ticket Center <b>($open_tickets_number)</b>", $replace);
    }
    else
    {
        header("Location: login.html");
        exit();
    }
    
    if ($whitelistkey == "NONE" && ($mac_whitelistkey == "NONE" || strlen($mac_whitelistkey) < 3))
    {
        $replace = str_replace("PACKAGETYPEHERE","Free Member",$replace);
    }
    else
    {
        if (($mac_whitelistkey != "NONE" && strlen($mac_whitelistkey) > 2) && ($whitelistkey != "NONE" && strlen($whitelistkey) > 2))
            $replace = str_replace("PACKAGETYPEHERE","Mac & Windows Member",$replace);

            if (($expire == "NONE" || strlen($expire) < 2) && ($mac_whitelistkey == "NONE" || strlen($mac_whitelistkey) < 3))
                $replace = str_replace("PACKAGETYPEHERE","Windows Lifetime",$replace);

            if (($mac_expire == "NONE" || strlen($mac_expire) < 2) && ($whitelistkey == "NONE" || strlen($whitelistkey) < 3))
                $replace = str_replace("PACKAGETYPEHERE","macOS Lifetime",$replace);
                    
            if ($expire != "NONE" && ($mac_whitelistkey == "NONE" || strlen($mac_whitelistkey) < 3))
                $replace = str_replace("PACKAGETYPEHERE","Windows Subscription",$replace);
                    
            if (($mac_expire != "NONE" && strlen($mac_whitelistkey) > 3) && ($whitelistkey == "NONE" || strlen($whitelistkey) < 3))
                $replace = str_replace("PACKAGETYPEHERE","macOS Subscription",$replace);
    }

    echo $replace;
    exit();
    }
   }
   
    /* Invalid Cookie */
    header("Location: logout.php");
    die();
}

?>