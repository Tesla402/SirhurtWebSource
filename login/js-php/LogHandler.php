<?php
require_once('subscription.php');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function getRedemptionHistory($identifier, $type = "serial")
{
    $servername = "localhost";
    $username = AERO_DB_USERNAME;
    $dbname = AERO_DB;

    $conn = new mysqli($servername, $username, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `RedeemHistoryDB` WHERE `$type` = '$identifier'") or die(mysqli_error($conn));

    $arrays = array();
    $found = 0;
    
    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        $arrays[$found] = array($entry['date'], $entry['serial'], $entry['os'], $entry['type'], $entry['username'], $entry['validfor']);
        
        $found++;
    }
    
    if ($found == 0)
        $arrays[0] = array("NONE", "NONE", "NONE", "NONE", "NONE", "NONE");
    
    return $arrays;
}


function addRedemptionRecord($serial, $os, $type, $username, $validfor)
{
    $servername = "localhost";
    $username2 = AERO_DB;
    $dbname = AERO_DB;
   
    $date = getnow();
    $conn = new mysqli($servername, $username2, AERO_DB_PASSWORD, $dbname);

    $sql = "INSERT INTO RedeemHistoryDB (date, serial, os, type, username, validfor)
    VALUES ('$date', '$serial', '$os', '$type', '$username', '$validfor')";
    
    return $conn->query($sql);
}

function LogWhitelistChange($date, $ip, $username, $oldhwid, $newhwid, $timezone, $pcname)
{
    $servername = "localhost";
    $username2 = AERO_DB;
    $dbname = AERO_DB;
   
    $date = getnow();
    $conn = new mysqli($servername, $username2, AERO_DB_PASSWORD, $dbname);

    $sql = "INSERT INTO WhitelistChangeLogDB (date, ip, username, oldhwid, newhwid, timezone, pcname)
    VALUES ('$date', '$ip', '$username', '$oldhwid', '$newhwid', '$timezone', '$pcname')";
    
    return $conn->query($sql);
}

function getLogWhitelistChangeHistory($identifier, $type = "username")
{
    $servername = "localhost";
    $username = AERO_DB_USERNAME;
    $dbname = AERO_DB;

    $conn = new mysqli($servername, $username, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `WhitelistChangeLogDB` WHERE `$type` = '$identifier'") or die(mysqli_error($conn));

    $arrays = array();
    $found = 0;
    
    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        $arrays[$found] = array($entry['date'], $entry['ip'], $entry['username'], $entry['oldhwid'], $entry['newhwid'], $entry['timezone'], $entry['pcname']);
        
        $found++;
    }
    
    if ($found == 0)
        $arrays[0] = array("NONE", "NONE", "NONE", "NONE", "NONE", "NONE", "NONE");
    
    return $arrays;
}

function fetchDifferentIdentifiers($identifier, $type = "USERNAME")
{
    $servername = "localhost";
    $username = AERO_DB_USERNAME;
    $dbname = AERO_DB;

    $conn = new mysqli($servername, $username, AERO_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `LogDB` WHERE `$type` = '$identifier'") or die(mysqli_error($conn));

    $arrays = array();
    $arrays2 = array();
    $found = 0;
    $found2 = 0;
    
    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if (!in_array($entry['COMPUTERNAME'], $arrays))
        {
            if ($entry['COMPUTERNAME'] != "N/A" && $entry['COMPUTERNAME'] != "REDACTED" && strlen($entry['COMPUTERNAME']) >= 2)
            {
                $arrays[$found] = $entry['COMPUTERNAME'];
                $found++;
            }
        }
        
        if (!in_array($entry['TIMEZONE'], $arrays2))
        {
            if ($entry['TIMEZONE'] != "N/A" && $entry['TIMEZONE'] != "REDACTED"  && strlen($entry['TIMEZONE']) >= 2)
            {
                $arrays2[$found2] = $entry['TIMEZONE'];
                $found2++;
            }
        }
    }
    
    if ($found == 0 && $found2 == 0)
        return array(array("NONE"), array("NONE"));
    
    return array($arrays, $arrays2);
}

function HasUserTriggeredDetection($identifier)
{
    $servername = "localhost";
    $username = LOGS_DB_USERNAME;
    $dbname = LOGS_DB;

    $conn = new mysqli($servername, $username, LOGS_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `SuspiciousAccsDB` WHERE `username` = '$identifier'") or die(mysqli_error($conn));

    $entries = mysqli_num_rows($loop);
   
    if ($entries >= 1)
        return true;
    
    return false;
}

function AddDetectionEntry($username)
{
    $servername = "localhost";
    $username2 = LOGS_DB_USERNAME;
    $dbname = LOGS_DB;
   
    $date = getnow();
    $conn = new mysqli($servername, $username2, LOGS_DB_PASSWORD, $dbname);

    $sql = "INSERT INTO SuspiciousAccsDB (date, username)
    VALUES ('$date', '$username')";
    
    return $conn->query($sql);
}

function DeleteDetectionEntry($username)
{
    /* Key Verify */
    $username = trim($username);

    if (strlen($username) <= 2)
    {
        return false;
    }
    
    $servername = "localhost";
    $username2 = LOGS_DB_USERNAME;
    $dbname = LOGS_DB;
   
    $conn = new mysqli($servername, $username2, LOGS_DB_PASSWORD, $dbname);
    
    $ret = $conn->query("DELETE FROM `SuspiciousAccsDB` WHERE `username` = '$username'");
    
    $conn->close();
    
    return $ret;
}

function IsUserCaseExcluded($username, $pcname_array)
{
    $servername = "localhost";
    $username2 = LOGS_DB_USERNAME;
    $dbname = LOGS_DB;

    $conn = new mysqli($servername, $username2, LOGS_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `ExclusionDB` WHERE `username` = '$username'") or die(mysqli_error($conn));
    
    $excluded = false;

    while ($entry = mysqli_fetch_array($loop))
    {
        for ($x = 0; $x < sizeof($pcname_array); $x++) 
        {
           $pcname = $pcname_array[$x];
           
            if (strpos($entry['pcnames'], $pcname) !== false) 
            {
                $excluded = true;
            }
        }
    }
    
    $conn->close();

    return $excluded;
}

function UpdateExclusionInformation($username, $pcnames)
{
    $servername = "localhost";
    $username2 = LOGS_DB_USERNAME;
    $dbname = LOGS_DB;
   
    $date = getnow();
    $conn = new mysqli($servername, $username2, LOGS_DB_PASSWORD, $dbname);
    
    $loop = mysqli_query($conn, "SELECT * FROM `ExclusionDB` WHERE `username` = '$username'") or die(mysqli_error($conn));
    $entries = mysqli_num_rows($loop);
    
    if ($entries == 0)
    {
        $sql = "INSERT INTO ExclusionDB (date, username, pcnames)
        VALUES ('$date', '$username', '$pcnames')";
        
        return $conn->query($sql);
    }
    else
    {
        $conn->close();
        $conn = new mysqli($servername, $username2, LOGS_DB_PASSWORD, $dbname);

        $data = 'date = ' . "'$date'" . ', username = ' . "'$username'" . ', pcnames = ' . "'$pcnames'";
        return $conn->query("UPDATE `ExclusionDB` SET $data WHERE `username` = '$username'");
    }
    
    return false;
}
?>