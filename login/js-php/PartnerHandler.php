<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function genRandomSecret_partner($n)
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

function getnow_partner()
{
return date('Y-m-d');
}

function UpdateAdSubmission($ip, $DATE, $PARTNER_USERNAME, $AD_MESSAGE, $status, $reviewer, $secret_db)
{
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);
    
    $secret = $keydb->real_escape_string(strip_tags(trim($secret_db)));
    
    $data = 'IP = ' . "'$ip'" . ', DATE = ' . "'$DATE'" . ', PARTNER_USERNAME = ' . "'$PARTNER_USERNAME'" . ', AD_MESSAGE = ' . "'$AD_MESSAGE'" . ', STATUS = ' . "'$status'" . ', MODERATOR = ' . "'$reviewer'" . ', SECRET = ' . "'$secret_db'";
    return $keydb->query("UPDATE `PartnerAdsDB` SET $data WHERE `SECRET` LIKE '%$secret%'");
}

function Fetch_Todays_Ads()
{
    $storage = array();
    $found = false;
    $at = 0;
    
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);
    
    $identifier = getnow_partner();

    $loop = mysqli_query($keydb, "SELECT * FROM `PartnerAdsDB` WHERE `DATE` = '$identifier'") or die(mysqli_error($keydb));

    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry["STATUS"] == "APPROVED")
        {
            $found = true;
                
            $storage[$at] = array($entry["DATE"], $entry["PARTNER_USERNAME"], $keydb->real_escape_string(strip_tags(trim($entry["AD_MESSAGE"]))), $entry["MODERATOR"]);
                
            $at++;    
        }
    }
    
    $keydb->close();

    if ($found)
    return $storage;
    
    /* Failed to find submission history */
    return array();
}

function FetchAdSubmissions($identifier, $type_db)
{
    $storage = array();
    $found = false;
    $at = 0;
    
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);

    $loop = mysqli_query($keydb, "SELECT * FROM `PartnerAdsDB` WHERE `$type_db` = '$identifier'") or die(mysqli_error($keydb));

    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
        $found = true;
            
        $storage[$at] = array($entry["IP"], $entry["DATE"], $entry["PARTNER_USERNAME"], $keydb->real_escape_string(strip_tags(trim($entry["AD_MESSAGE"]))), $entry["STATUS"], $entry["MODERATOR"], $entry["SECRET"]);
            
        $at++;    
    }
    
    $keydb->close();

    if ($found)
    return $storage;
    
    /* Failed to find submission history */
    return array();
}

function Add_Ad_For_Review($IP, $date, $PARTNER_USERNAME, $AD)
{
    $servername = "localhost";
    $username2 = PARTNER_DB_USERNAME;
    $dbname = PARTNER_DB;
   
    $conn = new mysqli($servername, $username2, PARTNER_DB_PASSWORD, $dbname);
    
    $secret = genRandomSecret_partner(20);
    
    $sql = "INSERT INTO PartnerAdsDB (IP, DATE, PARTNER_USERNAME, AD_MESSAGE, STATUS, MODERATOR, SECRET)
        VALUES ('$IP', '$date', '$PARTNER_USERNAME', '$AD', 'PENDING', 'NONE', '$secret')";
        
    return $conn->query($sql);
}

function Fetch_SirHurt_Announcements()
{
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);

    $loop = mysqli_query($keydb, "SELECT * FROM `SirHurtAnnouncements` ORDER BY DATE DESC") or die(mysqli_error($keydb));
    $storage = array();
    $found = false;
    $at = 0;
    
    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
        $found = true;
            
        $storage[$at] = array($entry["DATE"], $entry["MODERATOR"], $entry['MESSAGE']);
        
        $at++;
    }
    
    $keydb->close();
    
    if ($found)
    return $storage;
    
    /* Failed to find submission history */
    return array();
}

function Add_SirHurt_Announcement($date, $moderator, $message)
{
    $servername = "localhost";
    $username2 = PARTNER_DB_USERNAME;
    $dbname = PARTNER_DB;
   
    $conn = new mysqli($servername, $username2, PARTNER_DB_PASSWORD, $dbname);
    
    $sql = "INSERT INTO SirHurtAnnouncements (DATE, MODERATOR, MESSAGE)
        VALUES ('$date', '$moderator', '$message')";
        
    return $conn->query($sql);
}

function Log_Partnership_Keys($IP, $DATE, $PARTNER_USERNAME, $SERIAL_KEYS)
{
    $servername = "localhost";
    $username2 = PARTNER_DB_USERNAME;
    $dbname = PARTNER_DB;
   
    $conn = new mysqli($servername, $username2, PARTNER_DB_PASSWORD, $dbname);
    
    $sql = "INSERT INTO PartnerLogDatabaseDB (IP, DATE, PARTNER_USERNAME, SERIAL_KEYS)
        VALUES ('$IP', '$DATE', '$PARTNER_USERNAME', '$SERIAL_KEYS')";
        
    return $conn->query($sql);
}

function Fetch_All_Partner_Keys()
{
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);

    $loop = mysqli_query($keydb, "SELECT * FROM `PartnerLogDatabaseDB`") or die(mysqli_error($keydb));
    $storage = array();
    $found = false;
    $at = 0;
    
    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
        $found = true;
            
        $storage[$at] = array($entry["IP"], $entry["DATE"], $entry['PARTNER_USERNAME'], $entry['SERIAL_KEYS']);
        
        $at++;
    }
    
    $keydb->close();
    
    if ($found)
    return $storage;
    
    /* Failed to find submission history */
    return array();
}

function Fetch_Partner_Keys($PARTNER_USERNAME)
{
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);
    $username = $keydb->real_escape_string(strip_tags(trim($PARTNER_USERNAME)));
    
    $loop = mysqli_query($keydb, "SELECT * FROM `PartnerLogDatabaseDB` WHERE `PARTNER_USERNAME` = '$username'") or die(mysqli_error($keydb));

    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
       return array($entry["IP"], $entry["DATE"], $entry['PARTNER_USERNAME'], $entry['SERIAL_KEYS']);
    }
    
    $keydb->close();
    return array("NONE", "NONE", "NONE", "NONE");
}

function UpdatePartnershipEntry($IP, $PARTNER_USERNAME, $LAST_KEY_REQUEST = "NONE", $LAST_AD_POSTAGE = "NONE")
{
    $servername = "localhost";
    $username2 = PARTNER_DB_USERNAME;
    $dbname = PARTNER_DB;
   
    $date = getnow_partner();
    $conn = new mysqli($servername, $username2, PARTNER_DB_PASSWORD, $dbname);
    
    $loop = mysqli_query($conn, "SELECT * FROM `PartnerDataDB` WHERE `PARTNER_USERNAME` = '$PARTNER_USERNAME'") or die(mysqli_error($conn));
    $entries = mysqli_num_rows($loop);
    
    if ($entries == 0)
    {
        $sql = "INSERT INTO PartnerDataDB (IP, PARTNER_USERNAME, LAST_KEY_REQUEST, LAST_AD_POSTAGE)
        VALUES ('$IP', '$PARTNER_USERNAME', '$LAST_KEY_REQUEST', '$LAST_AD_POSTAGE')";
        
        return $conn->query($sql);
    }
    else
    {
        $conn->close();
        $conn = new mysqli($servername, $username2, PARTNER_DB_PASSWORD, $dbname);

        $data = 'IP = ' . "'$IP'" . ', PARTNER_USERNAME = ' . "'$PARTNER_USERNAME'" . ', LAST_KEY_REQUEST = ' . "'$LAST_KEY_REQUEST'" . ', LAST_AD_POSTAGE = ' . "'$LAST_AD_POSTAGE'";
        return $conn->query("UPDATE `PartnerDataDB` SET $data  WHERE `PARTNER_USERNAME` = '$PARTNER_USERNAME'");
    }
    
    return false;
}

function Fetch_Partnership_Data($PARTNER_USERNAME)
{
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);
    $username = $keydb->real_escape_string(strip_tags(trim($PARTNER_USERNAME)));
    
    $loop = mysqli_query($keydb, "SELECT * FROM `PartnerDataDB` WHERE `PARTNER_USERNAME` = '$username'") or die(mysqli_error($keydb));

    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
       return array($entry["LAST_KEY_REQUEST"], $entry["LAST_AD_POSTAGE"]);
    }
    
    $keydb->close();
    return array("NONE", "NONE");
}

function Is_Partner_Function_Eligible($PARTNER_USERNAME, $function_type)
{
    $keydb = new mysqli("localhost", PARTNER_DB_USERNAME, PARTNER_DB_PASSWORD, PARTNER_DB);
    $username = $keydb->real_escape_string(strip_tags(trim($PARTNER_USERNAME)));
    
    $loop = mysqli_query($keydb, "SELECT * FROM `PartnerDataDB` WHERE `PARTNER_USERNAME` = '$username'") or die(mysqli_error($keydb));

    /* Search for Logs */
    
    while ($entry = mysqli_fetch_array($loop))
    {
        $entry_date = $entry[$function_type];
        
        if ($entry_date == "NONE")
        {
            $keydb->close();
            return true;
        }
       
        $diff = getdatedif($entry_date);
            
        if ($diff < 0)
        {
            $keydb->close();
            return true;
        }
        else
        {
            $keydb->close();
            return false;
        }
    }
    
    $keydb->close();
    return true;
}

?>