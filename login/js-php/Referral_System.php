<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

//fetch the results
function grS_ZHJHJKSD($n)
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

function BlowFishDecryptionManual_REF($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2) , 'BF-CFB', $key, true, $iv);
}

function BlowFishEncryptionManual_REF($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
}

function CreateSerialKey_($okey, $amt)
{
    $key = trim($okey);
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    $sql = "INSERT INTO SerialDB (serial, amount)
    VALUES ('$key', '$amt')";

    return $keydb->query($sql);
}

function Fetch_Number_Of_Referrals($username_entry)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $username = $keydb->real_escape_string(strip_tags(trim($username_entry)));
    
    $loop = mysqli_query($keydb, "SELECT * FROM `ReferralLogDB` WHERE `referral` = '$username'") or die(mysqli_error($keydb));

    /* Search for Logs */
    
    $i = 0;
    while ($entry = mysqli_fetch_array($loop))
    {
       if ($entry['username'] != "UNKNOWN_DATA")
       {
            $i++;
       }
    }
    
    return $i;
}

function Is_User_Blacklisted($identifier_entry, $type_entry)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $type = $keydb->real_escape_string(strip_tags(trim($type_entry)));
    $identifier = $keydb->real_escape_string(strip_tags(trim($identifier_entry)));
    
    $loop = mysqli_query($keydb, "SELECT * FROM `ReferralBlacklistDB` WHERE `$type` = '$identifier'") or die(mysqli_error($keydb));

    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
       return true;
    }
    
    return false;
}

function User_Referral_Authority($username)
{
    $conn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $loop = mysqli_query($conn, "SELECT * FROM UserDB") or die(mysqli_error($conn));

    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($username == $whitelist["username"])
        {
            if ($whitelist["accesslevel"] >= 1)
            {
                return "STAFF_MEMBER";
            }
            
            if ($whitelist["accesslevel"] == 0.2)
            {
                return "SIRHURT_PARTNER";
            }
            
            if ($whitelist['whitelistkey'] != "NONE")
            {
                return "BUYER";
            }
            
            return "REGULAR";
        }
    }
    
    return "NONE";
}

/* New Referral System 10-17-2020 */
function Fetch_Pending_Balance($identifier_entry)
{
    $conn = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $identifier = $conn->real_escape_string(strip_tags(trim($identifier_entry)));

    $loop = mysqli_query($conn, "SELECT * FROM ReferralPayoutDB WHERE `username` = '$identifier'") or die(mysqli_error($conn));

    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($identifier == $whitelist["username"])
        {
            return array(true, $whitelist['payout'], $whitelist['referrals']);
        }
    }
    
    return array(false, 0, 0); //user not in referral db
}

function Add_Referral_Entry($username, $payout)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);

    $sql = "INSERT INTO ReferralPayoutDB (username, payout, referrals)
            VALUES ('$username', '$payout', '0')";
        
    $result = $keydb->query($sql);
    
    $keydb->close();
    
    return $result;
}

function Update_Referral_Entry($username, $payout, $referrals)
{
    
    /* Check if Referral exists in DB. If not, create with data instead of updating */
    if (Fetch_Pending_Balance($username)[0] == false)
    {
        return Add_Referral_Entry($username, $payout);
    }
    
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);

    $data = 'username = ' . "'$username'" . ', payout = ' . "'$payout'" . ', referrals = ' . "'$referrals'";
    $sql = "UPDATE ReferralPayoutDB SET $data WHERE username = '$username'";

    $result = $keydb->query($sql);
    
    $keydb->close();
    
    return $result;
}

function GetEyePea() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}

function CreatePaypalPayoutSubmission($ip, $username, $email, $PREVIOUS_ROBUX_BALANCE, $status)
{
    $keydb = new mysqli("localhost", SUBMISSIONS_DB_USERNAME, SUBMISSIONS_DB_PASSWORD, SUBMISSIONS_DB);
    
    $secret = grS_ZHJHJKSD(20);
    
    $sql = "INSERT INTO PaypalSubmissionDB (IP, STATUS, PAYPAL_EMAIL, USERNAME, PREVIOUS_ROBUX_BALANCE, TRANSACTION_ID, REVIEWER, SECRET)
    VALUES ('$ip', '$status', '$email', '$username', $PREVIOUS_ROBUX_BALANCE, 'N/A', 'NONE', '$secret')";

    return $keydb->query($sql);
}

function Paypal_Withdrawl_Balance($username, $paypal_email)
{
    $information = Fetch_Pending_Balance($username);
    
    if ($information[0] == false)
    {
        return "NOT_ACTIVE"; //????
    }
    
    $amount_to_be_paidout = $information[1];
    $referrals = $information[2];
    
    if ($amount_to_be_paidout <= 0)
    {
        return "EMPTY_BALANCE";
    }
    
    if (Update_Referral_Entry($username, 0, $referrals))
    {
        CreatePaypalPayoutSubmission(GetEyePea(), $username, $paypal_email, $amount_to_be_paidout, "PENDING");

        return "SUCCESS";
    }
    
    return "UPDATE_FAILED";
}

function Refund_Referral_Balance($username, $refund)
{
    $information = Fetch_Pending_Balance($username);
    
    if ($information[0] == false)
    {
        return "NOT_ACTIVE"; //????
    }
    
    $amount_to_be_paidout = $information[1] + $refund;
    $referrals = $information[2];

    if (Update_Referral_Entry($username, $amount_to_be_paidout, $referrals))
    {
        return "SUCCESS";
    }
    
    return "UPDATE_FAILED";
}

function Withdrawl_Balance($username)
{
    $information = Fetch_Pending_Balance($username);
    
    if ($information[0] == false)
    {
        return "NOT_ACTIVE"; //????
    }
    
    $amount_to_be_paidout = $information[1];
    $referrals = $information[2];
    
    if ($amount_to_be_paidout <= 0)
    {
        return "EMPTY_BALANCE";
    }
    
    $key = grS_ZHJHJKSD(8);
    CreateSerialKey_($key, $amount_to_be_paidout);
    
    if (Update_Referral_Entry($username, 0, $referrals))
    {
        /* Log Entry */
        $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
        $sql = "INSERT INTO ReferralLogDB (username, referral, serial, value)
        VALUES ('UNKNOWN_DATA', '$username', '$key', $amount_to_be_paidout)";
        
        $keydb->query($sql);
        /* End Log Entry */
            
        return $key;
    }
    
    return "UPDATE_FAILED";
}

function Fetch_User_Identifiers($username)
{
    $servername = "localhost";
    $username_ = AERO_DB;
    $dbname = AERO_DB;
    
    $conn = new mysqli($servername, $username_, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM UserDB WHERE `username` = '$username'") or die(mysqli_error($conn));
    $found = false;
    $data = array("N/A", "N/A");
    
    while ($whitelist = mysqli_fetch_array($loop))
    {
        $found = true;
        $data = array($whitelist['email'], md5($whitelist['pass']));
    }
    
    $conn->close();
    $loop->close();
    
    return $data;
}

function Find_And_Send_Email_V2($username, $buyers_username, $ip, $valueofcard)
{
    $servername = "localhost";
    $username_ = AERO_DB;
    $dbname = AERO_DB;
    
    $conn = new mysqli($servername, $username_, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM UserDB") or die(mysqli_error($conn));

    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($username == $whitelist["username"])
        {
            
            /* Attempt to prevent people from creating alts for self-claimed referrals */
            if ($username != "IcePools") //exclude IcePools to allow for testing
            {
                $data = Fetch_User_Identifiers($buyers_username);
                if ($whitelist["ip"] == $ip || $data[0] == $whitelist["email"] || $data[1] == md5($whitelist['pass']))
                {
                    return false;
                }
            }
            
            if (Is_User_Blacklisted($username, "username") == true || Is_User_Blacklisted($ip, "ip") == true)
            {
                return false;
            }
            
            /* Log Entry */
            $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
            $sql = "INSERT INTO ReferralLogDB (username, referral, serial, value)
            VALUES ('$buyers_username', '$username', 'NO_KEY_ADDITION', $valueofcard)";
        
            $keydb->query($sql);
            /* End Log Entry */
            
            $information = Fetch_Pending_Balance($username);
            $new_balance = $valueofcard;
            $referrals = 1;
    
            if ($information[0] == true)
            {
                $new_balance = $information[1] + $valueofcard;
                $referrals = $information[2] + 1;
                
                Update_Referral_Entry($username, $new_balance, $referrals);
            }
            else
            {
                Add_Referral_Entry($username, $new_balance);
            }
            
            if ($new_balance <= 0)
            {
                return false; //????
            }
            
            /* Send Email */
            $email = $whitelist["email"];
            $resp = file_get_contents("https://www.sirhurt.net/login/emailapi/sendreferralkey2.php?sec=SDHKJSHDKJSGDJKS&betken=" . urlencode(BlowFishEncryptionManual_REF($email, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")) . "&usrntk=" . urlencode(BlowFishEncryptionManual_REF($email, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")) . "&cardvalue=" . $new_balance);
            return true;
        }
    }
    
    return false;
}

function Key_Eligible_And_Create_V2($key, $referral)
{
    
    $keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

    if ($stmt = $keydbconn->prepare("SELECT serialkey, type, days, os FROM KeyDB WHERE serialkey = ?"))
    {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $stmt->bind_result($serialk, $keytype, $days, $os);
        while ($stmt->fetch())
        {
            if ($serialk == $key)
            {
                if (strlen($key) >= 32 && $referral != "IcePools")
                {
                    /* Not Eligible */
                    return array(0);
                }
                
                if ($keytype == "HWID")
                {
                    $value = 100 * 3; //triple for celebration of paypal addition 1-2-2020

                    
                    /*
                    if (Fetch_Number_Of_Referrals($referral) == 10)
                    {
                        $value = 1000;
                    }
                    */
                    
                    $referrals_count = Fetch_Number_Of_Referrals($referral);
                    if (($referrals_count % 10) == 0 && $referrals_count != 0) /* Every 10 Refs = 1000 Bonus */
                    {
                        $value = $value + 1000;
                    }
                    
                    $referral_authority = User_Referral_Authority($referral);
                    if ($referral_authority == "STAFF_MEMBER" || $referral_authority == "SIRHURT_PARTNER")
                    {
                        $value = $value + 100;
                    }
                    
                    if ($referral_authority == "BUYER")
                    {
                        $value = $value + 50;
                    }
                    
                    return array($value);
                }
                
                if ($keytype == "SUBSCRIPTION")
                {
                    if ($days == 30)
                    {
                        $value = 30 * 3; //triple for celebration of paypal addition 1-2-2020

                        /*if (Fetch_Number_Of_Referrals($referral) == 10)
                        {
                            $value = 1000;
                        }*/
                        
                        $referrals_count = Fetch_Number_Of_Referrals($referral);
                        if (($referrals_count % 10) == 0 && $referrals_count != 0) /* Every 10 Refs = 1000 Bonus */
                        {
                            $value = $value + 1000;
                        }
                        
                        $referral_authority = User_Referral_Authority($referral);
                        if ($referral_authority == "STAFF_MEMBER" || $referral_authority == "SIRHURT_PARTNER")
                        {
                            $value = $value + 30;
                        }
                        
                        if ($referral_authority == "BUYER")
                        {
                            $value = $value + 15;
                        }
                        
                        return array($value);
                    }
                    else if ($days >= 15)
                    {
                        $value = $days * 3; //30 days = 30 robux. at this same logic the flat rate is equal to the amount of days on the subscription. & triple for celebration of paypal addition 1-2-2020
                        
                        $referrals_count = Fetch_Number_Of_Referrals($referral);
                        if (($referrals_count % 10) == 0 && $referrals_count != 0) /* Every 10 Refs = 1000 Bonus */
                        {
                            $value = $value + 1000;
                        }
                        
                        $referral_authority = User_Referral_Authority($referral);
                        if ($referral_authority == "STAFF_MEMBER" || $referral_authority == "SIRHURT_PARTNER")
                        {
                            $value = $value + $days; //30 day subscription staff member bonus = 30. flat rate is 30. at the same logic the staff bonus is days added twice.
                        }
                        
                        if ($referral_authority == "BUYER")
                        {
                            $value = $value + round($days / 2); //30 day subscription buyer member bonus = 15. flat rate is 30. at the same logic half the days value is the bonus for buyers.
                        }
                        
                        return array($value);
                    }
                }
                
                
                /* Not Eligible */
                return array(0);
            }
        }
    }
    
    
    /* Not Eligible */
    return array(0);
}
/* End New Referral System 10-17-2020 */

function Find_And_Send_Email($username, $buyers_username, $ip, $key, $valueofcard)
{
    $servername = "localhost";
    $username_ = AERO_DB;
    $dbname = AERO_DB;
    
    $conn = new mysqli($servername, $username_, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM UserDB") or die(mysqli_error($conn));

    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($username == $whitelist["username"])
        {
            
            /* Attempt to prevent people from creating alts for self-claimed referrals */
            if ($whitelist["ip"] == $ip)
            {
                return false;
            }
            
            if (Is_User_Blacklisted($username, "username") == true || Is_User_Blacklisted($ip, "ip") == true)
            {
                return false;
            }
            
            /* Log Entry */
            $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
            $sql = "INSERT INTO ReferralLogDB (username, referral, serial, value)
            VALUES ('$buyers_username', '$username', '$key', $valueofcard)";
        
            $keydb->query($sql);
            
            /* Send Email */
            $email = $whitelist["email"];
            $resp = file_get_contents("https://www.sirhurt.net/login/emailapi/sendreferralkey.php?sec=SDHKJSHDKJSGDJKS&betken=" . urlencode(BlowFishEncryptionManual_REF($email, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")) . "&usrntk=" . urlencode(BlowFishEncryptionManual_REF($email, "C?B(G+KbPcAhV2Yq3t6B9ycB&E)H@McQ", "z%C*F-JaCdREAjXn")) . "&whitelistkey=" . urlencode($key) . "&cardvalue=" . $valueofcard);
            return true;
        }
    }
    
    return false;
}

/* SKETCH IDEA: Pass Unredeemed key to Key_Eligible_And_Create($unredeemedkey), grab the key via return array, send out email to refferal, */
function Key_Eligible_And_Create($key, $referral)
{
    
    $keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

    if ($stmt = $keydbconn->prepare("SELECT serialkey, type, days, os FROM KeyDB WHERE serialkey = ?"))
    {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $stmt->bind_result($serialk, $keytype, $days, $os);
        while ($stmt->fetch())
        {
            if ($serialk == $key)
            {
                if ($keytype == "HWID")
                {
                    $value = 100;
                    $k = grS_ZHJHJKSD(8);
                    
                    
                    /*
                    if (Fetch_Number_Of_Referrals($referral) == 10)
                    {
                        $value = 1000;
                    }
                    */
                    
                    $referrals_count = Fetch_Number_Of_Referrals($referral);
                    if (($referrals_count % 10) == 0 && $referrals_count != 0) /* Every 10 Refs = 1000 Bonus */
                    {
                        $value = 1000;
                    }
                    
                    $referral_authority = User_Referral_Authority($referral);
                    if ($referral_authority == "STAFF_MEMBER" || $referral_authority == "SIRHURT_PARTNER")
                    {
                        $value = $value + 100;
                    }
                    
                    if ($referral_authority == "BUYER")
                    {
                        $value = $value + 50;
                    }
                    
                    CreateSerialKey_($k, $value);
                        
                    return array($value, $k);
                }
                
                if ($keytype == "SUBSCRIPTION")
                {
                    if ($days == 30)
                    {
                        $value = 30;
                        $k = grS_ZHJHJKSD(8);
                        
                        /*if (Fetch_Number_Of_Referrals($referral) == 10)
                        {
                            $value = 1000;
                        }*/
                        
                        $referrals_count = Fetch_Number_Of_Referrals($referral);
                        if (($referrals_count % 10) == 0 && $referrals_count != 0) /* Every 10 Refs = 1000 Bonus */
                        {
                            $value = 1000;
                        }
                        
                        $referral_authority = User_Referral_Authority($referral);
                        if ($referral_authority == "STAFF_MEMBER" || $referral_authority == "SIRHURT_PARTNER")
                        {
                            $value = $value + 30;
                        }
                        
                        if ($referral_authority == "BUYER")
                        {
                            $value = $value + 15;
                        }
                        
                        CreateSerialKey_($k, $value);
                        
                        return array($value, $k);
                    }
                }
                
                
                /* Not Eligible */
                return array(0, "NONE");
            }
        }
    }
    
    
    /* Not Eligible */
    return array(0, "NONE");
}

?>