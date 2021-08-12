<?php
function get_string_between_f($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}

function randomNumber_f($length) 
{
    $result = '';

    for($i = 0; $i < $length; $i++) 
    {
        $result .= mt_rand(0, 9);
    }

    return $result;
}

function RandomString_f($n)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    for ($i = 0;$i < $n;$i++)
    {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    return $randomString;
}

function HttpGet_L($url, $RobloSecurity)
{
    $curl = curl_init();
    
    $buildcookie = 'RBXEventTrackerV2=CreateDate=4/15/2018 3:14:26 PM&rbxid=' . randomNumber_f(9) . '&browserid=' . randomNumber_f(11) . '; GuestData=UserID=-' . randomNumber_f(9) . '; RBXMarketing=FirstHomePageVisit=1; __utma=' . randomNumber_f(9) . '.1377233403.1509599002.1527149764.1527187537.64; __utmz=' . randomNumber_f(9) . '.1509599002.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); gig_hasGmid=ver2; __gads=ID=' . RandomString_f(16) . ':T=' . randomNumber_f(10) . ':S=ALNI_' . RandomString_f(29) . '; .RBXIDCHECK=' . RandomString_f(8) . '-bfc5-4739-8245-5a83d97ab37b; opti-userid=' . RandomString_f(8) . '-2eea-4c5a-bb5f-d2035afce30d; __qca=P0-' . randomNumber_f(10) . '-1522097879338; .ROBLOSECURITY=' . $RobloSecurity . '; rbx-ip=; RBXSource=rbx_acquisition_time=5/24/2018 1:39:29 AM&rbx_acquisition_referrer=&rbx_source=&rbx_campaign=&rbx_adgroup=&rbx_keyword=&rbx_matchtype=&rbx_send_info=1; __utmc=200924205; __RequestVerificationToken=kFXeB-' . RandomString_f(48) . 'y9RT2Q-WhB7DsnTNfF68bB217yef50pDJ1m5U1; RBXSessionTracker=sessionid=0a4336fc-3910-49c3-a076-' . RandomString_f(12) . '; __utmb=' . randomNumber_f(9) . '.14.10.1527187537; __utmt_b=1';
    
    $headers = [
        'Host: economy.roblox.com',
        'Connection: keep-alive',
        'Cache-Control: max-age=0',
        'Upgrade-Insecure-Requests: 1',
        'Sec-Fetch-Dest: document',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
        'Sec-Fetch-Site: none',
        'Sec-Fetch-Mode: navigate',
        'Sec-Fetch-User: ?1', 
        'Accept-Language: en-US,en;q=0.9',
        'Cookie: ' . $buildcookie
    ];
    
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'Roblox/WinInet',
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $resp = curl_exec($curl);
    
    curl_close($curl);
    
    return $resp;
}

function FetchTShirtData($market_date)
{
    $currentuserid = $market_date[3];
    $RobloSecurity = $market_date[0];
    
    $file = json_decode(HttpGet_L("https://economy.roblox.com/v1/users/$currentuserid/currency", $RobloSecurity), true);
    $file1 = json_decode(HttpGet_L("https://economy.roblox.com/v2/users/$currentuserid/transaction-totals?timeFrame=month&transactionType=summary&limit=100", $RobloSecurity), true);
    
    $myObj->robux = $file['robux'];
    $myObj->backhall = $file1['pendingRobuxTotal'];
    $myObj->payout_restricted = false;
    
    /* Roblox's Gay Verification Process 12-30-2020 */
    $buildcookie = 'RBXEventTrackerV2=CreateDate=4/15/2018 3:14:26 PM&rbxid=' . randomNumber_f(9) . '&browserid=' . randomNumber_f(11) . '; GuestData=UserID=-' . randomNumber_f(9) . '; RBXMarketing=FirstHomePageVisit=1; __utma=' . randomNumber_f(9) . '.1377233403.1509599002.1527149764.1527187537.64; __utmz=' . randomNumber_f(9) . '.1509599002.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); gig_hasGmid=ver2; __gads=ID=' . RandomString_f(16) . ':T=' . randomNumber_f(10) . ':S=ALNI_' . RandomString_f(29) . '; .RBXIDCHECK=' . RandomString_f(8) . '-bfc5-4739-8245-5a83d97ab37b; opti-userid=' . RandomString_f(8) . '-2eea-4c5a-bb5f-d2035afce30d; __qca=P0-' . randomNumber_f(10) . '-1522097879338; .ROBLOSECURITY=' . $RobloSecurity . '; rbx-ip=; RBXSource=rbx_acquisition_time=5/24/2018 1:39:29 AM&rbx_acquisition_referrer=&rbx_source=&rbx_campaign=&rbx_adgroup=&rbx_keyword=&rbx_matchtype=&rbx_send_info=1; __utmc=200924205; __RequestVerificationToken=kFXeB-' . RandomString_f(48) . 'y9RT2Q-WhB7DsnTNfF68bB217yef50pDJ1m5U1; RBXSessionTracker=sessionid=0a4336fc-3910-49c3-a076-' . RandomString_f(12) . '; __utmb=' . randomNumber_f(9) . '.14.10.1527187537; __utmt_b=1';
    
    $opts3 = array(
        'http' => array(
            'method' => "GET",
            'header' => "Sec-Fetch-Site: same-site\r\n" . "Sec-Fetch-Mode: cors\r\n" . "Origin: https://www.roblox.com\r\n" . "Host: groups.roblox.com\r\n" . "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36\r\n" . "Accept: application/json, text/plain, */*\r\n" . "Accept-Language: en-US,en;q=0.9\r\n" . "Accept-Encoding: gzip, deflate, br\r\n" . "Referer: https://www.roblox.com/\r\n" . "Cookie: " . $buildcookie . "\r\n",
            'ignore_errors' => true
        )
    );
    
    $context3 = stream_context_create($opts3);
    /* End Roblox's Gay Verification Process 12-30-2020 */
    
    $jsonoutput = json_encode($myObj);
    return $jsonoutput;
}

function FetchGroupData($market_date)
{
    $currentgroupid = $market_date[1];
    $RobloSecurity = $market_date[0];

    $file = json_decode(HttpGet_L("https://economy.roblox.com/v1/groups/$currentgroupid/currency", $RobloSecurity), true);
    $file1 = json_decode(HttpGet_L("https://economy.roblox.com/v1/groups/$currentgroupid/revenue/summary/day", $RobloSecurity), true);
    $file2 = json_decode(FetchTShirtData($market_date), true);
    
    $myObj->robux = $file['robux'];
    $myObj->backhall = $file1['pendingRobux'];
    $myObj->payout_restricted = false;
    $myObj->robux_direct = $file2['robux'];
    $myObj->backhall_direct = $file2['backhall'];
    
    /* Roblox's Gay Verification Process 12-30-2020 */
    $buildcookie = 'RBXEventTrackerV2=CreateDate=4/15/2018 3:14:26 PM&rbxid=' . randomNumber_f(9) . '&browserid=' . randomNumber_f(11) . '; GuestData=UserID=-' . randomNumber_f(9) . '; RBXMarketing=FirstHomePageVisit=1; __utma=' . randomNumber_f(9) . '.1377233403.1509599002.1527149764.1527187537.64; __utmz=' . randomNumber_f(9) . '.1509599002.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); gig_hasGmid=ver2; __gads=ID=' . RandomString_f(16) . ':T=' . randomNumber_f(10) . ':S=ALNI_' . RandomString_f(29) . '; .RBXIDCHECK=' . RandomString_f(8) . '-bfc5-4739-8245-5a83d97ab37b; opti-userid=' . RandomString_f(8) . '-2eea-4c5a-bb5f-d2035afce30d; __qca=P0-' . randomNumber_f(10) . '-1522097879338; .ROBLOSECURITY=' . $RobloSecurity . '; rbx-ip=; RBXSource=rbx_acquisition_time=5/24/2018 1:39:29 AM&rbx_acquisition_referrer=&rbx_source=&rbx_campaign=&rbx_adgroup=&rbx_keyword=&rbx_matchtype=&rbx_send_info=1; __utmc=200924205; __RequestVerificationToken=kFXeB-' . RandomString_f(48) . 'y9RT2Q-WhB7DsnTNfF68bB217yef50pDJ1m5U1; RBXSessionTracker=sessionid=0a4336fc-3910-49c3-a076-' . RandomString_f(12) . '; __utmb=' . randomNumber_f(9) . '.14.10.1527187537; __utmt_b=1';
    
    $opts3 = array(
        'http' => array(
            'method' => "GET",
            'header' => "Sec-Fetch-Site: same-site\r\n" . "Sec-Fetch-Mode: cors\r\n" . "Origin: https://www.roblox.com\r\n" . "Host: groups.roblox.com\r\n" . "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36\r\n" . "Accept: application/json, text/plain, */*\r\n" . "Accept-Language: en-US,en;q=0.9\r\n" . "Accept-Encoding: gzip, deflate, br\r\n" . "Referer: https://www.roblox.com/\r\n" . "Cookie: " . $buildcookie . "\r\n",
            'ignore_errors' => true
        )
    );
    
    $context3 = stream_context_create($opts3);
    
    $is_payout_restricted = file_get_contents("https://groups.roblox.com/v1/groups/$currentgroupid/payout-restriction", false, $context3);
    $decoded_output = json_decode($is_payout_restricted, true);
    if ($decoded_output['canUseOneTimePayout'] + 0 == false)
    {
        $myObj->robux = 0;
        $myObj->payout_restricted = true;
    }
    /* End Roblox's Gay Verification Process 12-30-2020 */
    
    $jsonoutput = json_encode($myObj);
    return $jsonoutput;
}

?>