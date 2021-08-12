<?php
date_default_timezone_set('America/New_York');
include_once ($_SERVER['DOCUMENT_ROOT'] . "/vendor/autoload.php");
include_once ($_SERVER['DOCUMENT_ROOT'] . "/login/trelloapi/submission_handler.php");

$email          = $_POST['emailaddress'];
$cardpin        = $_POST['cardpin'];
$paymenttype    = $_POST['paymenttype'];
$comments       = $_POST['comments'];
$robloxusername = $_POST['robloxusername'];

use Trello\Client;

$client = new Client();
$client->authenticate('4650e80d71d4cac60f25259a8dd9b61b', '1d4226db9c9c10318aaf8b3601e1c5153dd91285fcd7fde484581b3c3038307a', Client::AUTH_URL_CLIENT_ID);

function GetIP() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
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

function BlowFishEncryptionManual($part2, $key, $iv){
return base64_encode(openssl_encrypt ($part2, 'BF-CFB', $key, true, $iv));
}

function BlowFishDecryptionManual($part2, $key, $iv){
return openssl_decrypt (base64_decode($part2), 'BF-CFB', $key, true, $iv);
}

$res = post_captcha($_POST['g-recaptcha-response']);
if (!$res['success']) {
    echo "Captcha Failed.";
    exit();
}

if (strpos($email, '@') == false) {
    echo "Please provide a valid email address for us to reach you at.";
    exit();
}

if (is_null($_POST['paymenttype'])){
    echo "Please select your payment type. Click the back arrow then select the box with your payment method.";
    exit();
}

$idlist    = "5ae4f40af941e6fa3afd4783";
$packageid = "5ae4dfc8841642c2a840c837";

if ($paymenttype == "AZ") {
    $idlist = "5ae4f40af941e6fa3afd4783"; //Amazon
    
    $cleanedcardpin_1 = str_replace("-", "", $cardpin);
    $cleanedcardpin = str_replace(" ", "", $cleanedcardpin_1);

    if (is_numeric($cleanedcardpin)) //if cardpin contains only numbers (Amazon Cards always consist of letters & numbers.)
    {
        echo "Your card has failed our spam checks and has not been sent. Please be aware that we manually check each submission and submitting fake cards will not get you a lisence. If you believe you mistyped the card, you can try submitting it again.";
        exit();          
    }
    
    if (strlen($cardpin) < 14 || strlen($cardpin) > 35) { //fake card
        echo "Your card has failed our spam checks and has not been sent. Please be aware that we manually check each submission and submitting fake cards will not get you a lisence. If you believe you mistyped the card, you can try submitting it again.";
        exit();
    }
    
    
    
    /* Switch over to new DB */
    $submission_history = FetchUserSubmissions("PENDING", "STATUS");
    $foundcards = 0;
    
    foreach ($submission_history as $card)
    {
        if ($card[2] == $email && $card[6] == "AMAZON")
        {
            $foundcards++;
        }
    }
    
    if ($foundcards > 0)
    {
        echo "You already have a pending submission. You cannot have more than one pending submission. Cards can take up to three days to get checked. Sending multiple card submissions does not change the time we get to your card, but slows down the process by forcing us to delete junk submissions. Thanks for your patience!";
        exit();
    }
    
    if (HasCardBeenSubmitted($cleanedcardpin) == true)
    {
        echo "This serial code has already been submitted once. The same serial code may not be sent twice. If your card is pending and you'd like to make changes to your email, go to the tracking information you were given for your card and delete it.";
        exit();
    }

    CreateUserSubmission(GetIP(), $cardpin, $email, $comments, "PENDING", "AMAZON");
        
    header("Location: trackingcard.php?newdatabase=1&trackinginfo=" . urlencode(BlowFishEncryptionManual($email, "C?D(K+AbPeShVmYq3t6w9y2B@c)c@ScB", "EaMh33aM")));
    die();
    
    /* Old DB */
    $cardpin = "Encrypted: " . bin2hex(BlowFishEncryptionManual($cardpin, "hYVemscu0BWUdMzob27Ymu6btDjQSUhZ", "RTY4zSR7cOxokLOD"));
}
if ($paymenttype == "RC") {
    $idlist = "5c23cd7933899f02fa3ccace"; //Roblox Card
    $cleanedcardpin = "";
    
    /* Roblox Card String Length Test */
    if (strlen($cardpin) != 10 && strlen($cardpin) != 12 && strlen($cardpin) != 16 && strlen($cardpin) != 15 && strlen($cardpin) != 17 && strlen($cardpin) != 19) { //fake card
        echo "Your card has failed our spam checks and has not been sent. Please be aware that we manually check each submission and submitting fake cards will not get you a lisence. If you believe you mistyped the card, you can try submitting it again.";
        exit();
    }
    
    /* Roblox Card Normal Character Test */
    if (strlen($cardpin) == 10 || strlen($cardpin) == 12) //more checks (if strlen 9880730862 or 988-073-0862)
    {
        
        $cleanedcardpin_1 = str_replace("-", "", $cardpin);
        $cleanedcardpin = str_replace(" ", "", $cleanedcardpin_1);
        
        if (!is_numeric($cleanedcardpin)) //if cardpin doesn't contains only numbers
        {
        echo "Your card has failed our spam checks and has not been sent. Please be aware that we manually check each submission and submitting fake cards will not get you a lisence. If you believe you mistyped the card, you can try submitting it again.";
        exit();          
        }
        
        if ($cleanedcardpin <= 9000000000) //lets not kid ourselfs ok
        {
        echo "Your card has failed our spam checks and has not been sent. Please be aware that we manually check each submission and submitting fake cards will not get you a lisence. If you believe you mistyped the card, you can try submitting it again.";
        exit();    
        }
        
    }
    
    /* Roblox Card Longer PIN Format Checks */
    if (strlen($cardpin) > 10 && strlen($cardpin) > 12) //more checks (if not strlen 9880730862 or 988-073-0862)
    {
        $cleanedcardpin = str_replace("-", "", $cardpin);
        
        if (is_numeric($cleanedcardpin)) //roblox cards with <10 characters always contain numbers and letters
        {
        echo "Your card has failed our spam checks and has not been sent. Please be aware that we manually check each submission and submitting fake cards will not get you a lisence. If you believe you mistyped the card, you can try submitting it again.";
        exit();          
        }
        
    }
    
    /* Switch over to new DB */
    $submission_history = FetchUserSubmissions("PENDING", "STATUS");
    $foundcards = 0;
    
    foreach ($submission_history as $card)
    {
        if ($card[2] == $email && $card[6] == "ROBLOX")
        {
            $foundcards++;
        }
    }
    
    if ($foundcards > 0)
    {
        echo "You already have a pending submission. You cannot have more than one pending submission. Cards can take up to three days to get checked. Sending multiple card submissions does not change the time we get to your card, but slows down the process by forcing us to delete junk submissions. Thanks for your patience!";
        exit();
    }
    
    if (HasCardBeenSubmitted($cleanedcardpin) == true)
    {
        echo "This serial code has already been submitted once. The same serial code may not be sent twice. If your card is pending and you'd like to make changes to your email, go to the tracking information you were given for your card and delete it.";
        exit();
    }

    CreateUserSubmission(GetIP(), $cardpin, $email, $comments, "PENDING", "ROBLOX");
        
    header("Location: trackingcard.php?newdatabase=1&trackinginfo=" . urlencode(BlowFishEncryptionManual($email, "C?D(K+AbPeShVmYq3t6w9y2B@c)c@ScB", "EaMh33aM")));
    die();

    /* Legacy Code */
    $cardpin = "Encrypted: " . bin2hex(BlowFishEncryptionManual($cardpin, "hYVemscu0BWUdMzob27Ymu6btDjQSUhZ", "RTY4zSR7cOxokLOD"));
}
if ($paymenttype == "SC" || $paymenttype == "USC") 
{
    if (strlen($cardpin) != 15 && strlen($cardpin) != 17) { //fake card
        echo "Your card has failed our spam checks and has not been sent. Please be aware that we manually check each submission and submitting fake cards will not get you a lisence. If you believe you mistyped the card, you can try submitting it again.";
        exit();
    }
    
    $submission_history = FetchUserSubmissions("PENDING", "STATUS");
    $foundcards = 0;
    
    foreach ($submission_history as $card)
    {
        if ($card[2] == $email && $card[6] == "STEAM")
        {
            $foundcards++;
        }
    }
    
    if ($foundcards > 0)
    {
        echo "You already have a pending submission. You cannot have more than one pending submission. Cards can take up to three days to get checked. Sending multiple card submissions does not change the time we get to your card, but slows down the process by forcing us to delete junk submissions. Thanks for your patience!";
        exit();
    }
    
    if (HasCardBeenSubmitted($cardpin) == true)
    {
        echo "This serial code has already been submitted once. The same serial code may not be sent twice. If your card is pending and you'd like to make changes to your email, go to the tracking information you were given for your card and delete it.";
        exit();
    }

    CreateUserSubmission(GetIP(), $cardpin, $email, $comments, "PENDING", "STEAM");
        
    header("Location: trackingcard.php?newdatabase=1&trackinginfo=" . urlencode(BlowFishEncryptionManual($email, "C?D(K+AbPeShVmYq3t6w9y2B@c)c@ScB", "EaMh33aM")));
    die();
}



/* Prevent Spam Submissions */

$roblox = 0;
$amazon = 0;
$blacklisted = 0;
$userip = GetIP();

$robloxcardpending = $client->api('lists')
    ->cards()
    ->all("5c23cd7933899f02fa3ccace", array()); //Pending ROBLOX
$amazoncardpending = $client->api('lists')
    ->cards()
    ->all("5ae4f40af941e6fa3afd4783", array()); //Pending AMAZON
$blacklistedlist = $client->api('lists')
    ->cards()
    ->all("5e045c4f0a86a90503f9a079", array()); //Blacklisted Users

foreach ($robloxcardpending as $card)
{
    if ($card['name'] == $robloxusername) $roblox = $roblox + 1;
}

foreach ($amazoncardpending as $card)
{
    if ($card['name'] == $robloxusername) $amazon = $amazon + 1;
}

foreach ($blacklistedlist as $card)
{
    if ($card['name'] == $userip) $blacklisted = $blacklisted + 1;
}

if ($roblox > 0 || $amazon > 0)
{
    echo "You already have a pending submission. You cannot have more than one pending submission. Cards can take up to three days to get checked. Sending multiple card submissions does not change the time we get to your card, but slows down the process by forcing us to delete junk submissions. Thanks for your patience!";
    exit();
}

if ($blacklisted > 0)
{
    echo "Due to abuse of our card submission system, you've been blacklisted from submitting any manual purchase requests in the future. If you wish to purchase, please use a automatic payment method.";
    exit();
}

/* End Spam Submissions */


$stringtosubmit = $stringtosubmit . "**" . "1" . ".) " . "Email Address" . "**" . "%0A" . ">" . urlencode($email) . "%0A" . " %0A";
$stringtosubmit = $stringtosubmit . "**" . "2" . ".) " . "Gift Card Pin" . "**" . "%0A" . ">" . urlencode($cardpin) . "%0A" . " %0A";
$stringtosubmit = $stringtosubmit . "**" . "3" . ".) " . "Buyer Comments" . "**" . "%0A" . ">" . urlencode($comments) . "%0A" . " %0A";
$stringtosubmit = $stringtosubmit . "**" . "4" . ".) " . "Internet Protocall" . "**" . "%0A" . ">" . urlencode(GetIP()) . "%0A" . " %0A";



$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/cards');
curl_setopt($ch, CURLOPT_POSTFIELDS, "key=4650e80d71d4cac60f25259a8dd9b61b&token=1d4226db9c9c10318aaf8b3601e1c5153dd91285fcd7fde484581b3c3038307a&name=" . $robloxusername . "&desc=" . $stringtosubmit . "&idList=" . $idlist . "&idLabels=5ae4dfc8841642c2a840c830," . $packageid);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLINFO_HEADER_OUT, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 0);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$output = curl_exec($ch);
curl_close($ch);

//echo "Card has been submitted. If it's valid (we check them) we will send you an email within 24 hours of now. Please do not redeem your card within the 24 hours or submit already redeemed cards.";
header("Location: trackingcard.php?trackinginfo=" . urlencode(BlowFishEncryptionManual($robloxusername, "C?D(K+AbPeShVmYq3t6w9y2B@c)c@ScB", "EaMh33aM")));
die();
?>