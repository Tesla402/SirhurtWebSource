<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function testecho()
{
    return "lol";
}

function CheckKeyValid($okey)
{
    $key = trim($okey);
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM `SerialDB` WHERE `serial` LIKE '%$key%'") or die(mysqli_error($keydb));

    /* Key Verify */
    if (strlen($key) <= 7)
    {
        return false;
    }

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        return true;
    }
    
    /* Failed to find Serial Key*/
    return false;
}

function FetchMarketInformation()
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM `MarketInfoDB` WHERE `dummy` = 'DEFAULT_VALUE'") or die(mysqli_error($keydb));

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        return array($entry['RobloSecurity'], $entry['GroupID'], $entry['system_message'], $entry['userid'], $entry['dummy']);
    }
    
    /* Failed to find Information */
    return array("FAILED", 0, "", "");
}


function UpdateMarketInformation($roblosec, $groupid, $userid, $system_message = "NONE")
{
    if (strlen($roblosec) < 6)
    {
        return false;
    }
    
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    $data = 'RobloSecurity = ' . "'$roblosec'" . ', GroupID = ' . "'$groupid'" . ', system_message = ' . "'$system_message'" . ', userid = ' . "'$userid'" .  ', dummy = ' . "'DEFAULT_VALUE'";
    return $keydb->query("UPDATE `MarketInfoDB` SET $data WHERE `dummy` = 'DEFAULT_VALUE'");
}

function FetchKeyInformation($okey)
{
    $key = trim($okey);
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM `SerialDB` WHERE `serial` LIKE '%$key%'") or die(mysqli_error($keydb));
    
    /* Key Verify */
    if (strlen($key) <= 7)
    {
        return array("FAILED", 0);
    }

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        return array($entry['serial'], $entry['amount'], $entry['flagged'], $entry['reason'], $entry['moderator']);
    }
    
    /* Failed to find Serial Key*/
    return array("FAILED", 0);
}

function CheckPreviouslyRedeemed($okey)
{
    /* Patch for Double Key Issue found at 1/6/2020 */
    $key = trim($okey);
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM `LogDB` WHERE `serialkey` LIKE '%$key%'") or die(mysqli_error($keydb));
    
    /* Key Verify */
    if (strlen($key) <= 7)
    {
        return true;
    }

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry['type'] != "LOWER_PAYOUT" && $entry['type'] != "CUSTOM_PAYOUT")
        return true;
    }
    
    /* Failed to find Serial Key */
    return false;
}

function DeleteSerialKey($okey)
{
    /* Key Verify */
    $key = trim($okey);

    if (strlen($key) <= 7)
    {
        return false;
    }
    
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    return $keydb->query("DELETE FROM `SerialDB` WHERE `serial` LIKE '%$key%'");
}

function Is_Identifier_Payout_Blacklisted($IP_ADDRESS, $ROBLOX_USERNAME)
{
    $key = trim($identifier);
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    /* Identifer Verify */
    if (strlen($key) <= 4)
    {
        return array(false, "NONE", "NONE");
    }
    
    $loop = mysqli_query($keydb, "SELECT * FROM `RobuxPayoutBlacklistDB`") or die(mysqli_error($keydb));

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry['IP_ADDRESS'] == $IP_ADDRESS || $entry['ROBLOX_USERNAME'] == $ROBLOX_USERNAME)
        {
            return array(true, $entry['ORDER_ID'], $entry['MODERATOR']);
        }
    }
    
    return array(false, "NONE", "NONE");
}

function DeletePayoutBlacklist($identifier, $type)
{
    /* Key Verify */
    $key = trim($identifier);

    if (strlen($key) <= 4)
    {
        return false;
    }
    
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    return $keydb->query("DELETE FROM `RobuxPayoutBlacklistDB` WHERE `$type` LIKE '%$key%'");
}


function CreatePayoutBlacklist($email, $ip, $username, $order_id, $MODERATOR)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    $sql = "INSERT INTO RobuxPayoutBlacklistDB (EMAIL_ADDRESS, IP_ADDRESS, ROBLOX_USERNAME, ORDER_ID, MODERATOR)
    VALUES ('$email', '$ip', '$username', '$order_id', '$MODERATOR')";

    return $keydb->query($sql);
}

function CreateSerialKey($okey, $amt)
{
    $key = trim($okey);
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    $sql = "INSERT INTO SerialDB (serial, amount, flagged, reason, moderator)
    VALUES ('$key', '$amt', 'FALSE', 'NONE', 'NONE')";

    return $keydb->query($sql);
}

function CreateAccessCode($hwid, $ip, $accesscode)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    $sql = "INSERT INTO ROBUXAccessCodeDB (hwid, ip, accesscode)
    VALUES ('$hwid', '$ip', '$accesscode')";

    return $keydb->query($sql);
}

function FetchAccessCodeInformation($accesscode, $identifier = "accesscode")
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM `ROBUXAccessCodeDB` WHERE `$identifier` LIKE '%$accesscode%'") or die(mysqli_error($keydb));

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        return array($entry['hwid'], $entry['ip'], $entry['accesscode']);
    }
    
    /* Failed to find Access Code */
    return array("NOT_FOUND", "NOT_FOUND", "NOT_FOUND");
}

function CreateLogEntry($ip, $date, $type, $username, $serialkey)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    $sql = "INSERT INTO LogDB (ip, date, type, username, serialkey)
    VALUES ('$ip', '$date', '$type', '$username', '$serialkey')";

    return $keydb->query($sql);
}

function FetchLogEntry($type, $identifier)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM `LogDB` WHERE `$type` = '$identifier'") or die(mysqli_error($keydb));

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
       return array($entry['ip'], $entry['date'], $entry['type'], $entry['username'], $entry['serialkey']);
    }
    
    return array('NONE', 'NONE', 'NONE', 'NONE', 'NONE');
}

function FetchLogEntries($type, $identifier)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM `LogDB` WHERE `$type` = '$identifier'") or die(mysqli_error($keydb));
    
    $storage = array();
    $i = 0;

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
       $storage[$i] = array($entry['ip'], $entry['date'], $entry['type'], $entry['username'], $entry['serialkey']);
       $i++;
    }
    
    $keydb->close();
    
    return $storage;
}

function FetchROBUXEntry($type, $package, $identifier)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM LogDB") or die(mysqli_error($keydb));

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry[$type] == $package && strtoupper($entry['username']) == strtoupper($identifier))
        {
            return array($entry['ip'], $entry['date'], $entry['type'], $entry['username'], $entry['serialkey']);
        }
    }
    
    return array('NONE', 'NONE', 'NONE', 'NONE', 'NONE');
}

function FetchROBUXEntries($type, $package, $identifier)
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    $loop = mysqli_query($keydb, "SELECT * FROM LogDB") or die(mysqli_error($keydb));
    $entries = array();
    $entries_i = 0;
    
    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry[$type] == $package && strtoupper($entry['username']) == strtoupper($identifier))
        {
            $entries[$entries_i] = array($entry['ip'], $entry['date'], $entry['type'], $entry['username'], $entry['serialkey']);
            $entries_i++;
        }
    }
    
    if ($entries_i > 0)
        return $entries;
    
    return array(array('NONE', 'NONE', 'NONE', 'NONE', 'NONE'));
}

function UpdateSerialKey($key, $amt, $flagged = "FALSE", $reason = "NONE", $moderator = "NONE")
{
    if (strlen($key) < 6)
    {
        return false;
    }
    
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);
    
    $data = 'serial = ' . "'$key'" . ', amount = ' . "'$amt'" . ', flagged = ' . "'$flagged'" . ', reason = ' . "'$reason'" . ', moderator = ' . "'$moderator'";
    return $keydb->query("UPDATE `SerialDB` SET $data WHERE `serial` LIKE '%$key%'");
}

function CalculateIfEligbleForEarlyPayout($fundsingroup, $fundsusercanredeem)
{
    //Pretend User has 5K funds. 1234 funds in group.
    
    if ($fundsingroup >=  $fundsusercanredeem)
    {
        return array("EQUAL_OR_MORE_FUNDS", $fundsusercanredeem, 0);
    }
    
    //If reached, funds in group are less than funds user can redeem (his keys balance.) therefor, to calculate what balance to update the serial to, subtract the funds the user can redeem by the funds in the group to get the remaining amt to pay out to the user.
    $updatefundsto = $fundsusercanredeem - $fundsingroup;
    
    if ($updatefundsto <= 0)
    {
        /* This should never ever happen */
        return array("UNKNOWN_ERROR", 0, 0);
    }
    
    return array("LESS_GROUP_FUNDS_THAN_USER_BALANCE", $fundsingroup, $updatefundsto);
}

function ClearStaffGeneratedRobuxKeys()
{
    $keydb = new mysqli("localhost", ROBUX_DB_USERNAME, ROBUX_DB_PASSWORD, ROBUX_DB);

    return $keydb->query("DELETE FROM SerialDB where LENGTH(serial) > 30");
}

/*
function ClearAllROBUXGeneratedKeys()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    return $keydb->query("DELETE FROM KeyDB where LENGTH(serialkey) < 32");
}

function ClearSkiddyRobuxRedeemWhitelistsAccounts()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    
    $keydb2 = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $loop = mysqli_query($keydb2, "SELECT * FROM UserDB") or die(mysqli_error($keydb));
    $deletions = 0;
    
     while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry['whitelistkey'] != "NONE" && $entry['oldpwd'] == "NONE")
        {
            if (strlen($entry['whitelistkey']) < 16 && strpos($entry['date'], '2019-12-') !== false)
            {
                $deletions = $deletions + 1;
                $keydb->query("DELETE FROM UserDB where username = '" . $entry['username'] . "'");
            }
        }
    }
    
    return $deletions;
} 
*/
?>