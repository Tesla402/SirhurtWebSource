<?php
/* New Card DB System Handler */
include_once ($_SERVER['DOCUMENT_ROOT'] . "/login/trelloapi/submission_handler.php");

/* Robux Handler */
include_once ($_SERVER['DOCUMENT_ROOT'] . "/autobuy/RobuxHandler.php");
include_once ($_SERVER['DOCUMENT_ROOT'] . "/autobuy/FundsHandler.php");

function post_captcha($user_response)
{
    $fields_string = '';
    $fields = array(
        'secret' => '6Lcy0-EUAAAAAGlOWxzIk-pXJu986WnEqMm-tFf5',
        'response' => $user_response
    );
    foreach ($fields as $key => $value) $fields_string .= $key . '=' . $value . '&';
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

function GetIP() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}

$res = post_captcha($_POST['g-recaptcha-response']);
if (!$res['success'])
{
    $replace = file_get_contents("failed.html");
    $replace = str_replace("Attempt Fail", "Failed to validate captcha. Please go back and try again.", $replace);

    echo $replace;
    exit();
}

$robux_selling = trim($_POST['robux']);
$robux_selling = preg_replace('/[^0-9]/', '', $robux_selling); /* remove any non-numerical character */

$username = trim($_POST['username']);
$paypal_email = trim($_POST['paypalemail']);

if (strpos($paypal_email, '@') == false || strpos($paypal_email, '.') == false) 
{
    echo "Invalid email address. Please check your email and try again.";
    exit();
}


/* Prevent overstocking on funds, risking huge losses */
$pending_limit = 350000;
$funds_information = json_decode(FetchGroupData(FetchMarketInformation()), true);
$account_funds_information = json_decode(FetchTShirtData(FetchMarketInformation()), true);

$pending_funds = $funds_information['backhall'] + $account_funds_information['backhall'];
$current_funds = $funds_information['robux'] + $account_funds_information['robux'];
$total_robux = $pending_funds + $current_funds;

if ($total_robux > $pending_limit)
{
    echo "We are currently overstocked on funds and as such are not currently purchasing anymore at this time. Please wait for us to have time to run lower on stock, as this is to minimize the losses that occur from a group deletion by purchasing more than we can sell. We currently have $total_robux in backstock funds. Our cap is $pending_limit in backstock at any given time. We're sorry for the inconvience and thank you for understanding.";
    exit();
}

if ($robux_selling < 2500 || $robux_selling > 214286)
{
    echo "Robux amount does not meet our requirements. We will not purchase more than 214286 ROBUX in one transaction, and we will not purchase less than 4,286 ROBUX.";
    exit();
}

$upload_dev_product = preg_replace('/[^0-9]/', '', file_get_contents("https://www.sirhurt.net/autobuy/producthandler/upload_dev_product.php?securitykey=KJSDHJKSDKJSHD&price=" . $robux_selling . "&usern=" . $username));

$access_code = CreateRobuxPaymentSubmission(GetIP(), "PENDING", $username, $paypal_email, $robux_selling, $upload_dev_product); //$IP, $STATUS, $ROBLOX_USERNAME, $PAYPAL_EMAIL, $ROBUX, $DEV_PRODUCT_ID
if ($access_code != "NONE")
{
    $payout_would_be = Calculate_Robux_Sold_After_Tax($robux_selling);
    
    echo "Success! Your access code is $access_code. To continue with the payment, please visit <a style='color:black;text-decoration:underline;' href='https://www.roblox.com/games/6659494100/Data-Center' target='_blank'>https://www.roblox.com/games/6659494100/Data-Center</a> and when prompted provide your access code $access_code. Purchase the developer product that prompts to finish the transaction. If all goes according to plan, you will be paid $payout_would_be.";
    exit();
}

echo "An error occured while submitting your request.";
exit();
?>