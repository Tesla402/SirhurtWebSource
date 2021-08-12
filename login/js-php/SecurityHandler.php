<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function genRStri($n)
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

function adddaystodate_($date,$days)
{
    $date = strtotime("+".$days." days", strtotime($date));
    return  date("Y-m-d", $date);
}

function getnow_()
{
    return date('Y-m-d');
}

function getprovideddatedifs_($old, $new)
{
    $date1 = new DateTime($old);
    $date2 = new DateTime($new);
    $interval = $date2->diff($date1);
    return $interval->format("%r%a");
}

function getdatedif_($old)
{
    $date1 = new DateTime($old);
    $date2 = new DateTime("now");
    $interval = $date2->diff($date1);
    return $interval->format("%r%a");
}

function returndate_()
{
$cool = getdate();
return $cool["year"] . '-' . $cool["mon"] . '-' . $cool["mday"];
}

function UpdateStaffSecurityToken($username_entry)
{
    $userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $username = $userdbconn->real_escape_string(strip_tags(trim($username_entry)));
    
    $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB WHERE username = '$username'");

    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($username == $whitelist["username"])
        {
            $newcookie = genRStri(40);
            $expires_on = adddaystodate_(getnow_(), 30);
            
            /* Staff Cookies expire sooner */
            if ($whitelist["accesslevel"] >= 1)
            {
                $expires_on = adddaystodate_(getnow_(), 15);
            }
            
            $sql = 'UPDATE UserDB SET authkey = ' . "'$whitelist[0]'" . ', hwid = ' . "'$whitelist[1]'" . ', email = ' . "'$whitelist[2]'" . ", username = " . "'$whitelist[3]'" . ", pass = " . "'$whitelist[4]'" . ", whitelistkey = " . "'$whitelist[5]'" . ", ip = " . "'$whitelist[6]'" . ", discord = " . "'$whitelist[7]'" . ", date = " . "'$whitelist[8]'" . ", expire = " . "'$whitelist[9]'" . ", flagged = " . "'$whitelist[10]'" . ", hwidlock = " . "'$whitelist[11]'" . ", lastreset = " . "'$whitelist[12]'" . ", flagreason = " . "'$whitelist[13]'" . ", cookiesec = " . "'$newcookie'" . ", oldpwd = " . "'$whitelist[15]'" . ", accesslevel = " . "'$whitelist[16]'" . ", mac_hwid = " . "'$whitelist[17]'" . ", mac_whitelistkey = " . "'$whitelist[18]'" . ", mac_expire = " . "'$whitelist[19]'" . ", cookiesec_expires = " . "'$expires_on'" . ", securitypin = " . "'$whitelist[21]'" . " WHERE username = '$username'";

            $userdbconn->query($sql);
            
            return $newcookie;
        }
    }
    
    return "INVALID";
}

function UpdateSecurityToken($username_entry)
{
    $userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $username = $userdbconn->real_escape_string(strip_tags(trim($username_entry)));
    
    $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB WHERE username = '$username'");

    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($username == $whitelist["username"])
        {
            $newcookie = genRStri(20);
            $expires_on = adddaystodate_(getnow_(), 30);
            
            $sql = 'UPDATE UserDB SET authkey = ' . "'$whitelist[0]'" . ', hwid = ' . "'$whitelist[1]'" . ', email = ' . "'$whitelist[2]'" . ", username = " . "'$whitelist[3]'" . ", pass = " . "'$whitelist[4]'" . ", whitelistkey = " . "'$whitelist[5]'" . ", ip = " . "'$whitelist[6]'" . ", discord = " . "'$whitelist[7]'" . ", date = " . "'$whitelist[8]'" . ", expire = " . "'$whitelist[9]'" . ", flagged = " . "'$whitelist[10]'" . ", hwidlock = " . "'$whitelist[11]'" . ", lastreset = " . "'$whitelist[12]'" . ", flagreason = " . "'$whitelist[13]'" . ", cookiesec = " . "'$newcookie'" . ", oldpwd = " . "'$whitelist[15]'" . ", accesslevel = " . "'$whitelist[16]'" . ", mac_hwid = " . "'$whitelist[17]'" . ", mac_whitelistkey = " . "'$whitelist[18]'" . ", mac_expire = " . "'$whitelist[19]'" . ", cookiesec_expires = " . "'$expires_on'" . ", securitypin = " . "'$whitelist[21]'" . " WHERE username = '$username'";

            $userdbconn->query($sql);
            
            return $newcookie;
        }
    }
    
    return "INVALID";
}

function Is_Cookie_Expired($username_entry)
{
    $userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $username = $userdbconn->real_escape_string(strip_tags(trim($username_entry)));
    
    $loop = mysqli_query($userdbconn, "SELECT * FROM UserDB WHERE username = '$username'");

    while ($whitelist = mysqli_fetch_array($loop))
    {
        if ($username == $whitelist["username"])
        {
            $expires_on = $whitelist['cookiesec_expires'];

            if (strlen($expires_on) < 4)
            {
                $userdbconn->close();
                return "EXPIRED";
            }
            
            $diff = getdatedif_($expires_on);
            
            if ($diff <= 0)
            {
                $userdbconn->close();
                return "EXPIRED";
            }
            
            $userdbconn->close();
            return "NOT_EXPIRED";
        }
    }
    
    $userdbconn->close();
    return "NOT_EXPIRED";
}

?>