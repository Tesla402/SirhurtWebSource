<?php
require_once('subscription.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function getSuspensionHistory($identifier, $type)
{
    $servername = "localhost";
    $username = AERO_DB_USERNAME;
    $dbname = AERO_DB;
    
    /* if no hwid is set, and we perform a hwid search for the default NONE and we have a global blacklist with no set HWID, big oopsie. */
    if ($type == "hwid" && $identifier == "NONE")
    {
        return array("NONE");
    }

    $conn = new mysqli($servername, $username, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `BadCookies` WHERE `$type` = '$identifier'") or die(mysqli_error($conn));

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($type == "hwid" && $entry["hwid"] == "NONE")
        {
            continue;
        }
        
        if ($entry['global'] == 1)
            return array("GLOBAL_BLACKLIST", "NEVER", "NEVER", $entry['suspensions'], $entry['issuer'], $entry['username']);
            
        if ($entry['expires'] == "NEVER")
            return array("ACTIVE", "NEVER", "NEVER", $entry['suspensions'], $entry['issuer'], $entry['username']);

        if (getdatedif($entry['expires']) > 0)
            return array("ACTIVE", getdatedif($entry['expires']), $entry['expires'], $entry['suspensions'], $entry['issuer'], $entry['username']);
            
        return array("EXPIRED", getdatedif($entry['expires']), $entry['expires'], $entry['suspensions'], $entry['issuer'], $entry['username']);
    }
    
    return array("NONE");
}

function getGlobalSuspensionIdentifiers()
{
    $servername = "localhost";
    $username = AERO_DB_USERNAME;
    $dbname = AERO_DB;

    $conn = new mysqli($servername, $username, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `BadCookies`") or die(mysqli_error($conn));

    $global_usernames = array();
    $global_identifiers = array();
    
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry['global'] == 1)
        {
            array_push($global_usernames, $entry['username']);
        }
    }
    
    if (sizeof($global_usernames) == 0)
    {
        return array("NONE");   
    }
    
    $conn->close();
    $loop->close();
    
    $i = 0;
    $keydbconn = 0;
    while ($i < sizeof($global_usernames))
    {
        $keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
        if($stmt = $keydbconn->prepare("SELECT authkey, hwid, email, username, pass, whitelistkey, ip, discord, date, expire, flagged, hwidlock, lastreset, flagreason, cookiesec, oldpwd, accesslevel, mac_hwid, mac_whitelistkey, mac_expire FROM UserDB WHERE username = ?")) 
        {
            $stmt->bind_param("s", $global_usernames[$i]); 
            $stmt->execute(); 
            $stmt->bind_result($authkey_db, $hwid_db, $email, $usern, $pass_db, $whitelistkey, $ip_db, $discord, $date, $expire, $flagged, $hwidlock, $lastreset, $flagreason, $cookiesec, $oldpwd, $accesslevel, $mac_hwid, $mac_whitelistkey, $mac_expire);
            while ($stmt->fetch()) 
            {
                if ($global_usernames[$i] == $usern)
                {
                    array_push($global_identifiers, $usern, $hwid_db, $email, $ip_db);
                }
            }
        }
        
        $keydbconn->close();
        $stmt->close();
        
        $i++;
    }
    
    
    if (sizeof($global_identifiers) == 0)
    {
        return array("NONE");
    }
    
    return $global_identifiers;
}

function addSuspension($username_real, $hwid, $days, $carry_over, $issuer = "UNKNOWN")
{
    $servername = "localhost";
    $username = AERO_DB_USERNAME;
    $dbname = AERO_DB;
   
    $conn = new mysqli($servername, $username, AERO_DB_PASSWORD, $dbname);
    $information = getSuspensionHistory($username_real, "username");
    
    $expires_on = "NEVER";
    $suspensions = 1;
    
    
    /* check in log DB for HWID, maybe user has used the trial? */
    if ($hwid == "NONE")
    {
        $logdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
        
        if($clientlogstmt = $logdbconn->prepare("SELECT USERNAME, DATE, IP, HWID, COMPUTERNAME, WINDOWTITLE, TIMEZONE FROM LogDB WHERE USERNAME = ?")) 
        {
            $clientlogstmt->bind_param("s", $username_real); 
            $clientlogstmt->execute(); 
            $clientlogstmt->bind_result($usern_cl, $date_cl, $ip_cl, $hwid_cl, $computername_cl, $windowtitle_cl, $timezone_cl);
            while ($clientlogstmt->fetch()) 
            {
                $hwid = $hwid_cl;
            }
        }
        
        $logdbconn->close();
    }
    
    
    if ($information[0] == "NONE")
    {
        if ($days != 0)
            $expires_on = adddaystodate(getnow(), $days);
        
        $sql = "INSERT INTO BadCookies (username, expires, suspensions, hwid, issuer, global)
        VALUES ('$username_real', '$expires_on', '1', '$hwid', '$issuer', '$carry_over')";
    
        return $conn->query($sql);
    }
    else
    {
        if ($days != 0)
            $expires_on = adddaystodate(getnow(), $days);
            
        $suspensions = $information[3] + 1;
            
        $data = 'username = ' . "'$username_real'" . ', expires = ' . "'$expires_on'" . ', suspensions = ' . "'$suspensions'" . ', hwid = ' . "'$hwid'" . ', issuer = ' . "'$issuer'" . ', global = ' . "'$carry_over'";
        return $conn->query("UPDATE `BadCookies` SET $data WHERE `username` = '$username_real'");
    }
    
    return false;
}

function removeSuspension($username_real, $hwid)
{
    $servername = "localhost";
    $username = AERO_DB_USERNAME;
    $dbname = AERO_DB;
   
    $conn = new mysqli($servername, $username, AERO_DB_PASSWORD, $dbname);
    $information = getSuspensionHistory($username_real, "username");
    
     if ($information[0] != "NONE")
     {
         $suspensions = $information[3] - 1;
         $issuer = $information[4];
         
         if ($suspensions <= 0)
         {
            return $conn->query("DELETE FROM `BadCookies` WHERE `username` = '$username_real'");
         }
            
        $data = 'username = ' . "'$username_real'" . ', expires = ' . "'2020-02-14'" . ', suspensions = ' . "'$suspensions'" . ', hwid = ' . "'$hwid'" . ', issuer = ' . "'$issuer'" . ', global = ' . "'0'";
        return $conn->query("UPDATE `BadCookies` SET $data WHERE `username` = '$username_real'");
     }
     
     return true;
}

?>