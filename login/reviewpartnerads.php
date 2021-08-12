<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");
require_once ($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/TicketHandler.php");
require_once ('js-php/InfoHandler.php');

$ex_html = '
<ul class="list-item-group">
<li class="list-group-item">
<div class="btn-group float-right">
  <button type="button" class="btn btn-danger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    Actions
  </button>
  <div class="dropdown-menu dropdown-menu-right">
    <a class="dropdown-item" href="#" onclick="window.open(' . "'" . 'js-php/admoderation.php?reviewer=USERNAME&cardid=CARD_ID&decision=APPROVED' . "'" . ');">Approve Ad</a>
    <a class="dropdown-item" href="#" onclick="window.open(' . "'" . 'js-php/admoderation.php?reviewer=USERNAME&cardid=CARD_ID&decision=DENIED' . "'" . ')">Deny Ad</a>
  </div>
</div>
    <p class="term "><strong>Partner:</strong> PARTNER_USRN</p>
    <hr>
    <p><strong>Ad Message:</strong> ADMSGHERE</p>
</li>
</ul>
';


date_default_timezone_set('America/New_York');
include_once($_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php");
include_once("js-php/PartnerHandler.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/SecurityHandler.php");
require_once ('js-php/RankTitles.php');

/* Functions */
function BlowFishDecryptionManual($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2) , 'BF-CFB', $key, true, $iv);
}

function BlowFishEncryptionManual($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
}

function GetIP() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
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

/* Security */
$clearance_level = 0;
$secusername = "N/A";

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
    $secusername = strip_tags($userdbconn->real_escape_string($secusername));
    $cookiesec_staff_cookie = strip_tags($userdbconn->real_escape_string($cookiesec_staff_cookie));

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
            
            if (Is_Cookie_Expired($secusername) == "EXPIRED")
            {
               header("Location: https://www.sirhurt.net/login/logout.php");
               die();
            }

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

/* Handle Pending Orders */    
$pending_cards = FetchAdSubmissions('PENDING', 'STATUS');

/* HTML */
$replace = file_get_contents($_SERVER['DOCUMENT_ROOT']."/login/trelloapi/example_html.html");
$generate_html = "";

$replace = str_replace("Roblox Card Submissions", "Partnership Ad Submissions", $replace);
$replace = str_replace("USERNAME_HERE", $secusername, $replace);

foreach ($pending_cards as $card) 
{
        $usernamme = $card[2]; //PARTNER_USERNAME
        $cardid = $card[6]; //SECRET
        
        $cardpin = $card[3]; //ad message
        
        $cardpin = str_replace('\n', "<br>", $cardpin);

        $protectedusername = BlowFishEncryptionManual($usernamme, "C?D(A+KbPeShCQYq3t6w9ycL&E)H@McQ", "D%C*F-Ca3dRfUjcA");
        $protectedcardid = BlowFishEncryptionManual($cardid, "C?D(A+KbPeShCQYq3t6w9ycL&E)H@McQ", "D%C*F-Ca3dRfUjcA");
    
        $edit = str_replace("USERNAME", urlencode($protectedusername), $ex_html);
        $edit = str_replace("CARD_ID", urlencode($protectedcardid), $edit);
        $edit = str_replace("PARTNER_USRN", urlencode($usernamme), $edit);
        $edit = str_replace("ADMSGHERE", urldecode($cardpin), $edit); //CARD_PIN
        
        $generate_html = $generate_html . $edit . "<br>";
    }


$replace = str_replace("<!--replacehere-->", $generate_html, $replace);


if ($clearance_level >= 0.1)
{
    $replace = str_replace("<!--ADMIN-->", '<a href="admin.php"><div class="material-icons">admin_panel_settings</div> Admin Panel</a>', $replace);
    $replace = str_replace("<!--PARTNERREVIEW-->", '<a href="reviewpartnerads.php"><div class="material-icons">admin_panel_settings</div> Partner Administration</a>', $replace);
}
            
if ($clearance_level == 0.2 || $clearance_level >= 7)
{
    $replace = str_replace("<!--PARTNERSHIP-->", '<a href="partner.php"><div class="material-icons">admin_panel_settings</div> Partner Panel</a>', $replace);
}
             
$replace = str_replace('<div class="material-icons">verified_user</div> Corporate Member', GetRankFromAccessLevel($clearance_level) , $replace);
echo $replace;
?>