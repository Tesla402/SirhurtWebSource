<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function Make_SirHurt_Key($key, $package_type, $days, $os)
{
    if ($package_type != "HWID" && $package_type != "SUBSCRIPTION")
    {
        return false;
    }
    
    $keyDB = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $sql = "INSERT INTO KeyDB (serialkey, type, days, os, flagged, reason, moderator)
    VALUES ('$key', '$package_type', $days, '$os', 'FALSE', 'NONE', 'NONE')";
        
    return $keyDB->query($sql);
}

function Delete_SirHurt_Key($identifier)
{
    if (strlen($identifier) < 3)
    {
        return false;
    }
    
    $userdbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $conn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $keyDB = mysqli_query($userdbconn, "SELECT * FROM KeyDB");
    while ($whitelist = mysqli_fetch_array($keyDB))
    {
        if ($identifier == $whitelist["serialkey"])
        {
            $serialk = $whitelist["serialkey"];
            $sql = "DELETE FROM KeyDB WHERE serialkey = '$serialk'";
            return $conn->query($sql);
        }
    }
    
    return false;
}

function Update_SirHurt_Key($key, $type, $days, $os, $flagged, $reason, $moderator)
{
    if (strlen($key) < 6)
    {
        return false;
    }
    
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $data = 'serialkey = ' . "'$key'" . ', type = ' . "'$type'" . ', days = ' . "'$days'" . ', os = ' . "'$os'" . ', flagged = ' . "'$flagged'" . ', reason = ' . "'$reason'" . ', moderator = ' . "'$moderator'";
    return $keydb->query("UPDATE `KeyDB` SET $data WHERE `serialkey` LIKE '%$key%'");
}

function Fetch_SirHurt_Key_Information($identifier)
{
    $keydbconn = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

    if ($stmt = $keydbconn->prepare("SELECT serialkey, type, days, os, flagged, reason, moderator FROM KeyDB WHERE serialkey = ?"))
    {
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $stmt->bind_result($serialk, $keytype, $days, $os, $flagged, $reason, $moderator);
        while ($stmt->fetch())
        {
            if ($serialk == $identifier)
            {
                return array($serialk, $keytype, $days, $os, $flagged, $reason, $moderator);
            }
        }
    }
    
    return array("NONE");
}

function ClearStaffGeneratedSirHurtKeys()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);

    return $keydb->query("DELETE FROM KeyDB where LENGTH(serialkey) > 25");
}

?>