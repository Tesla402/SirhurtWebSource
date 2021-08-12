<?php

function Fetch_User_Agent()
{
    return $_SERVER["HTTP_USER_AGENT"];
}

function Fetch_URL()
{
    return $_SERVER['SCRIPT_URI'];
}

function Block_IP_From_Website($IP, $notes)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudflare.com/client/v4/user/firewall/access_rules/rules");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

    $headers = [ 
    'X-Auth-Email: ashpokeman@gmail.com',
    'X-Auth-Key: 6b6f02f30af7edf6b0735a2d4dd54b9f890e8',
    'Content-Type: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $dnsData = array();
    $ipData = array();
    
    $ipData["target"] = "ip";
    $ipData["value"] = $IP;
    
    $dnsData["mode"] = "block";
    $dnsData["configuration"] = $ipData;
    $dnsData["notes"] = $notes;

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dnsData));

    $result = curl_exec($ch);
    
    //the firewall rule ID is returned in reply. json decoded. result->id
    return true;
}

function Fetch_Connection_History($IP)
{
    $file = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . md5($IP) . ".txt";

    if (!file_exists($file))
    {
       return array();
    }
    else
    {
        $entry = json_decode(file_get_contents($file), true);
        return array($entry["IP"], $entry["UserAgent"], $entry["Connections"], $entry["Timestamp"], $entry['URL']);
    }
    
    /*$servername = "localhost";
    $username2 = "sirhhfai_logs";
    $dbname = "sirhhfai_logdb";

    $conn = new mysqli($servername, $username2, "N&qqzKSTV#v^", $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `DownShield` WHERE `IP` = '$IP' LIMIT 1") or die(mysqli_error($conn));
    
    $history = array();
    while ($entry = mysqli_fetch_array($loop))
    {
        $history = array($entry["IP"], $entry["UserAgent"], $entry["Connections"], $entry["Timestamp"]);
    }
    
    $loop->close();
    $conn->close();

    return $history;*/
}

function Add_Block_Record($IP)
{
    $filename = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . md5($IP) . "_BLOCK.txt";
    $data->blocked = 1;
    
    $encoded_data = json_encode($data);

    $myfile = fopen($filename, "w");
    fwrite($myfile, $encoded_data);
    fclose($myfile);

    return true;
}

function Is_User_Blocked_Quick($IP)
{
    $file = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . $IP . "_BLOCK.txt";
    
    return file_exists($file);
}

/* End User Agent Addition */

function Add_Connection_Record($IP, $User_Agent)
{
    $servername = "localhost";
    $username2 = "sirhhfai_logs";
    $dbname = "sirhhfai_logdb";
    
    $User_Agent = base64_encode($User_Agent);
   
    
    $time = new DateTime();
    $current_time = $time->format('U');
    
    $time->modify('+1 minutes');
    $future_time = $time->format('U');
    
    /*$conn = new mysqli($servername, $username2, "N&qqzKSTV#v^", $dbname);

    $sql = "INSERT INTO `DownShield` (IP, UserAgent, Connections, Timestamp)
    VALUES ('$IP', '$User_Agent', 1, '$future_time')";
    
    $result = $conn->query($sql);
    
    $conn->close();
    
    return $result;*/
    
    
    $filename = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . md5($IP) . ".txt";
    $data->IP = $IP;
    $data->UserAgent = $User_Agent;
    $data->Connections = 1;
    $data->Timestamp = $future_time;
    $data->URL = Fetch_URL();
    
    $encoded_data = json_encode($data);

    $myfile = fopen($filename, "w");
    fwrite($myfile, $encoded_data);
    fclose($myfile);

    return true;
}

function Add_UserAgent_Connection_Record($IP, $User_Agent)
{
    $servername = "localhost";
    $username2 = "sirhhfai_logs";
    $dbname = "sirhhfai_logdb";
    
    $User_Agent_Encode = base64_encode($User_Agent);
   
    $time = new DateTime();
    $current_time = $time->format('U');
    
    $time->modify('+2 minutes');
    $future_time = $time->format('U');
    
    $filename = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . md5($User_Agent) . ".txt";
    $data->IP = $IP;
    $data->UserAgent = $User_Agent_Encode;
    $data->Connections = 1;
    $data->Timestamp = $future_time;
    $data->URL = Fetch_URL();
    
    $encoded_data = json_encode($data);

    $myfile = fopen($filename, "w");
    fwrite($myfile, $encoded_data);
    fclose($myfile);

    return true;
}

function Update_UserAgent_Record($IP, $User_Agent, $Connections, $Date)
{
    $filename = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . md5($User_Agent) . ".txt";

    $data->IP = $IP;
    $data->UserAgent = base64_encode($User_Agent);
    $data->Connections = $Connections;
    $data->Timestamp = $Date;
    $data->URL = Fetch_URL();
    
    $encoded_data = json_encode($data);

    $myfile = fopen($filename, "w");
    fwrite($myfile, $encoded_data);
    fclose($myfile);
    
    return true;
}

function UpdateEntry($IP, $User_Agent, $Connections, $Date)
{
    /*$servername = "localhost";
    $username2 = "sirhhfai_logs";
    $dbname = "sirhhfai_logdb";
    
    $User_Agent = base64_encode($User_Agent);
    
    $todays_date = date('m-d-Y');
    $keydb = new mysqli($servername, $username2, "N&qqzKSTV#v^", $dbname);    
    $data = 'IP = ' . "'$IP'" . ', UserAgent = ' . "'$User_Agent'" . ', Connections = ' . "'$Connections'" . ', Timestamp = ' . "'$Date'";
    
    $result = $keydb->query("UPDATE `DownShield` SET $data WHERE `IP` = '$IP'");
    
    $keydb->close();
    return $result;*/
    
    $filename = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . md5($IP) . ".txt";

    $data->IP = $IP;
    $data->UserAgent = base64_encode($User_Agent);
    $data->Connections = $Connections;
    $data->Timestamp = $Date;
    $data->URL = Fetch_URL();
    
    $encoded_data = json_encode($data);

    $myfile = fopen($filename, "w");
    fwrite($myfile, $encoded_data);
    fclose($myfile);
    
    return true;
}

function Update_Connection_Record($IP, $User_Agent)
{
    if (Is_User_Blocked_Quick($IP) == true)
    {
        return "BLOCK"; //prevent spam reading file by doing a file_exists check instead. hopefully :/
    }
    
    $todays_date = date('Y-m-d');
    $hour = date('H:i');

    
    $file = $_SERVER['DOCUMENT_ROOT'] . "/blocked/" . $todays_date . ".txt";
    
    /* Clear Cache */
    if (!file_exists($file))
    {
       array_map( 'unlink', array_filter((array) glob($_SERVER['DOCUMENT_ROOT'] . "/blocked/*") ) );
       
       $myfile = fopen($file, "w");
       fwrite($myfile, "");
       fclose($myfile);
       
       $myfile = fopen($_SERVER['DOCUMENT_ROOT'] . "/blocked/index.php", "w");
       fwrite($myfile, "");
       fclose($myfile);
    }
    
    if (preg_match('/\bGooglebot\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bDiscordbot\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bBingbot\b/', $User_Agent) || preg_match('/\bbingbot\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (strpos($User_Agent, 'Bingbot') !== false || strpos($User_Agent, 'bingbot') !== false) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bBaiduspider\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bYandexBot\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bia_archiver\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bfacebot\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bfacebookexternalhit\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bSlurp\b/', $User_Agent) || preg_match('/\bYahoo\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bDuckDuckBot\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bSogou\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    if (preg_match('/\bExabot\b/', $User_Agent)) 
    {
       return "SUCCESS";
    }
    
    $history = Fetch_Connection_History($IP);
    $user_agent_history = Fetch_Connection_History($User_Agent);


    /* TODO: Add a user agent version of this too for bots that use spammy user agents. */
    
    if (sizeof($user_agent_history) >= 1)
    {
        $times = $history[2] + 1;
        $timestamp = $history[3];
        $usr_agnt = $history[1];
        $requesting_url = $history[4];

        $time = new DateTime();
        $current_time = $time->format('U');
        
        $time = new DateTime();
        $time->modify('+2 minutes');
        $future_time = $time->format('U');
        
        if ($times > 10 && $current_time < $timestamp)
        {
            Block_IP_From_Website($IP, "Automatic Entry for: (User-Agent) SirHurt DDoS - " . $day . " blocked for " . $times . " connections on " . $todays_date . ". ($requesting_url) at $hour");
            
            Add_Block_Record($IP);
            return "BLOCK";
        }
        
        if ($current_time >= $timestamp)
        {
            $times = 1;
        }
        
        Update_UserAgent_Record($IP, $User_Agent, $times, $future_time);
    }
    else
    {
        Add_UserAgent_Connection_Record($IP, $User_Agent);
    }
    
    if (sizeof($history) >= 1)
    {
        $times = $history[2] + 1;
        $timestamp = $history[3];
        $usr_agnt = $history[1];
        $requesting_url = $history[4];

        $time = new DateTime();
        $current_time = $time->format('U');
        
        $time = new DateTime();
        $time->modify('+1 minutes');
        $future_time = $time->format('U');
        
        if ($timestamp == 0)
        {
            return "BLOCK"; //assume blocked
        }
        
        if ($times > 9 && $current_time < $timestamp)
        {
            Block_IP_From_Website($IP, "Automatic Entry for: (IP) SirHurt DDoS - " . $day . " blocked for " . $times . " connections on " . $todays_date . ". ($requesting_url) at $hour");
            
            UpdateEntry($IP, $User_Agent, $times, 0);
            Add_Block_Record($IP);
            return "BLOCK";
        }
        
        if ($current_time >= $timestamp)
        {
            $times = 1;
        }
        
        UpdateEntry($IP, $User_Agent, $times, $future_time);
        return "SUCCESS";
    }
    else
    {
        Add_Connection_Record($IP, $User_Agent);
        return "SUCCESS";
    }
    
    return "SUCCESS";
}

/* Anti DDos 4-11-2021 */
$connecting_user_agent = Fetch_User_Agent();
if (Update_Connection_Record(GetIP(), $connecting_user_agent) == "BLOCK")
{
    echo "You've been IP banned from sirhurt.net due to being flagged as a bot. You can appeal at our discord server if you believe this was done by mistake.";
    exit();
    die();
}
/* End Anti DDos 4-11-2021 */

//var_dump($_SERVER);
?>