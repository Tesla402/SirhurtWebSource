<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function KSHDjkdhfkjsdhjk() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}

function HKJhfdkjdhkhsdkhk($n)
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

function Blowfish_Dec($part2, $key, $iv)
{
    return openssl_decrypt(base64_decode($part2) , 'BF-CFB', $key, true, $iv);
}

function Blowfish_Enc($part2, $key, $iv)
{
    return base64_encode(openssl_encrypt($part2, 'BF-CFB', $key, true, $iv));
}

function Create_Script_Path($source, $name)
{
    $filename = $_SERVER['DOCUMENT_ROOT'] . "/scripthub/scripts_sr/" . $name;

    $myfile = fopen($filename, "w");
    fwrite($myfile, $source);
    fclose($myfile);

    return true;
}
 
function Is_UserAgent_Excluded($User_Agent)
{
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
    
    return "FAIL";
}

function Fetch_User_Installed_Scripts($installed_scripts)
{
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;
    
    $buildquery = "SELECT * FROM `UploadsDB` WHERE ";
    foreach ($installed_scripts as $array_element)
    {
        $buildquery = $buildquery . "`ID` = '$array_element' OR ";
    }
    
    $buildquery = substr($buildquery, 0,-3);

    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, $buildquery) or die(mysqli_error($conn));
    
    $arrays = array();
    $num = 0;

    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if (in_array($entry['ID'], $installed_scripts))
        {
            $arrays[$num] = array($entry['DATE'], $entry['PARTNERED'], $entry['STATUS'], $entry['UPLOADER'], $entry['ID'], $entry['SCRIPT_NAME'], $entry['SCRIPT_IMAGE_PATH'], $entry['SCRIPT_DESCRIPTION']);
            $num++;
        }
    }
    
    return $arrays;
}

function Fetch_Users_Uploads($uploader)
{
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;

    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);

    $arrays = array();
    $num = 0;
    
    if($stmt = $conn->prepare("SELECT DATE, PARTNERED, STATUS, UPLOADER, ID, SCRIPT_NAME, SCRIPT_IMAGE_PATH FROM UploadsDB WHERE UPLOADER = ?")) 
    {
        $stmt->bind_param("s", $uploader); 
        $stmt->execute(); 
        $stmt->bind_result($DATE, $PARTNERED, $STATUS, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH);
    
        while ($stmt->fetch()) 
        {
            $arrays[$num] = array($DATE, $PARTNERED, $STATUS, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH);
            $num++;
        }
    }
    
    return $arrays;
}

function Fetch_Uploaded_Scripts()
{
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;

    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);
    
    $arrays = array();
    $num = 0;
    
    if($stmt = $conn->prepare("SELECT DATE, IP, PARTNERED, STATUS, MODERATOR, UPLOADER, ID, SCRIPT_NAME, SCRIPT_IMAGE_PATH, SCRIPT_TAGS, SCRIPT_DESCRIPTION, SCRIPT_SOURCE_PATH, SCRIPT_ENC_KEY FROM UploadsDB")) 
    {
        $stmt->execute(); 
        $stmt->bind_result($DATE, $IP, $PARTNERED, $STATUS, $MODERATOR, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH, $SCRIPT_TAGS, $SCRIPT_DESCRIPTION, $SCRIPT_SOURCE_PATH, $SCRIPT_ENC_KEY);
    
        while ($stmt->fetch()) 
        {
            $arrays[$num] = array($DATE, $PARTNERED, $STATUS, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH);
            $num++;
        }
    }
    
    return $arrays;
}

function Fetch_Uploaded_Scripts_By_Search_Terms($search_term)
{
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;

    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);
    
    $search_term = strip_tags($conn->real_escape_string($search_term));
    $search_term = "%$search_term%";
    
    $arrays = array();
    $num = 0;
    
    if($stmt = $conn->prepare("SELECT DATE, PARTNERED, STATUS, UPLOADER, ID, SCRIPT_NAME, SCRIPT_IMAGE_PATH FROM UploadsDB WHERE `SCRIPT_NAME` LIKE ? OR `SCRIPT_TAGS` LIKE ?")) 
    {
        $stmt->bind_param("ss", $search_term, $search_term); 
        $stmt->execute(); 
        $stmt->bind_result($DATE, $PARTNERED, $STATUS, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH);
    
        while ($stmt->fetch()) 
        {
            $arrays[$num] = array($DATE, $PARTNERED, $STATUS, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH);
            $num++;
        }
    }
    
    return $arrays;
}

function Fetch_Script_Info_By_ID($scriptID)
{
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;

    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);
    
    if($stmt = $conn->prepare("SELECT DATE, IP, PARTNERED, STATUS, MODERATOR, UPLOADER, ID, SCRIPT_NAME, SCRIPT_IMAGE_PATH, SCRIPT_TAGS, SCRIPT_DESCRIPTION, SCRIPT_SOURCE_PATH, SCRIPT_ENC_KEY FROM UploadsDB WHERE ID = ?")) 
    {
        $stmt->bind_param("s", $scriptID); 
        $stmt->execute(); 
        $stmt->bind_result($DATE, $IP, $PARTNERED, $STATUS, $MODERATOR, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH, $SCRIPT_TAGS, $SCRIPT_DESCRIPTION, $SCRIPT_SOURCE_PATH, $SCRIPT_ENC_KEY);
    
        while ($stmt->fetch()) 
        {
            return array($DATE, $IP, $PARTNERED, $STATUS, $MODERATOR, $UPLOADER, $ID, $SCRIPT_NAME, $SCRIPT_IMAGE_PATH, $SCRIPT_TAGS, $SCRIPT_DESCRIPTION, $SCRIPT_SOURCE_PATH, $SCRIPT_ENC_KEY);
        }
    }

    return array();
}

function Delete_Script($scriptid, $script_path)
{
    $filename = $_SERVER['DOCUMENT_ROOT'] . "/scripthub/scripts_sr/" . $script_path;
    unlink($filename);
    
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;

    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);
    
    $sql = "DELETE FROM UploadsDB WHERE ID = '$scriptid'";
    return $conn->query($sql);
}

function Fetch_Install_Entry($customer)
{
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;
    
    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);
    
    $customer = strip_tags($conn->real_escape_string($customer));
    
    $loop = mysqli_query($conn, "SELECT * FROM `InstallsUserDB` WHERE `USERNAME` = '$customer'") or die(mysqli_error($conn));

    $arrays = array();
    $found = true;
    
    while ($entry = mysqli_fetch_array($loop))
    {
        return array($entry['CREATED_JSON'], $entry['INSTALLS_JSON']);
    }
    
    return array();
}

function Fetch_Scripts_Installs($scriptid)
{
    $servername = "localhost";
    $username = SCRIPTHUB_DB_USERNAME;
    $dbname = SCRIPTHUB_DB;

    $conn = new mysqli($servername, $username, SCRIPTHUB_DB_PASSWORD, $dbname);
    $loop = mysqli_query($conn, "SELECT * FROM `InstallsUserDB` WHERE `INSTALLS_JSON` LIKE '%$scriptid%'") or die(mysqli_error($conn));

    return mysqli_num_rows($loop);
}

function Does_User_Have_Installed($customer, $scriptid)
{
    $data = Fetch_Install_Entry($customer);
    
    if (sizeof($data) == 0)
    {
        return false;
    }
    
    if ($data[1] == "NONE")
    {
        return false;
    }
    
    
    $installs_array = json_decode($data[1]);
    
    return in_array($scriptid, $installs_array);
}

function Update_Installs_Entries($customer, $type, $addition)
{
    $data = Fetch_Install_Entry($customer);
    
    if (sizeof($data) == 0)
    {
        $keydb = new mysqli("localhost", SCRIPTHUB_DB_USERNAME, SCRIPTHUB_DB_PASSWORD, SCRIPTHUB_DB);
        $created_json = "NONE";
        $installs_json = "NONE";
        
        if ($type == "CREATED")
        {
            $newdata = array($addition);
            $created_json = json_encode($newdata);
        }
        
        if ($type == "INSTALL")
        {
            $newdata = array($addition);
            $installs_json = json_encode($newdata);
        }
    
        $sql = "INSERT INTO InstallsUserDB (`USERNAME`, `CREATED_JSON`, `INSTALLS_JSON`)
        VALUES ('$customer', '$created_json', '$installs_json')";
        
        return $keydb->query($sql);
    }
    else
    {
        $keydb = new mysqli("localhost", SCRIPTHUB_DB_USERNAME, SCRIPTHUB_DB_PASSWORD, SCRIPTHUB_DB);
        $created_json = $data[0];
        $installs_json = $data[1];
        
        if ($type == "CREATED")
        {
            $decoded_query = array();
            
            if ($created_json != "NONE")
            {
                $decoded_query = json_decode($created_json);
            }

            array_unshift($decoded_query, $addition);
            $created_json = json_encode($decoded_query);
        }
        
        if ($type == "INSTALL")
        {
            $decoded_query = array();
            
            if ($installs_json != "NONE")
            {
                $decoded_query = json_decode($installs_json);
            }

            array_unshift($decoded_query, $addition);
            $installs_json = json_encode($decoded_query);
        }
        
        if ($type == "UNINSTALL")
        {
            $decoded_query = array();
            
            if ($installs_json != "NONE")
            {
                $decoded_query = json_decode($installs_json);
            }
            
            /*$index = array_search($addition, $decoded_query);
            unset($decoded_query[$index]);*/
            
            $new_array = array();
            $num = 0;
            
            foreach ($decoded_query as $array_element)
            {
                if ($array_element != $addition)
                {
                    $new_array[$num] = $array_element;
                    $num++;
                }
            }
            
            $decoded_query = $new_array;
            
            $installs_json = json_encode($decoded_query);
            
            if (sizeof($decoded_query) == 0)
            {
                $installs_json = "NONE";
            }
        }
        
        $data = 'USERNAME = ' . "'$customer'" . ', CREATED_JSON = ' . "'$created_json'" . ', INSTALLS_JSON = ' . "'$installs_json'";
        return $keydb->query("UPDATE `InstallsUserDB` SET $data WHERE `USERNAME` = '$customer'");
    }
}

function Change_Script_Partner_Status($scriptID, $PARTNERED)
{
    $old_data = Fetch_Script_Info_By_ID($scriptID);
    
    $customer_ip = $old_data[1];
    $date = date('m-d-Y');
    //$PARTNERED = $old_data[2];
    $customer_username = $old_data[5];
    $script_src_path = $old_data[11];
    $script_enc_key = $old_data[12];
    $script_name = $old_data[7];
    $script_image_path = $old_data[8];
    $script_tags = $old_data[9];
    $script_description = $old_data[10];
    $STATUS = $old_data[3];
    $MODERATOR = $old_data[4];

    $keydb = new mysqli("localhost", SCRIPTHUB_DB_USERNAME, SCRIPTHUB_DB_PASSWORD, SCRIPTHUB_DB);
    
    $data = 'DATE = ' . "'$date'" . ', IP = ' . "'$customer_ip'" . ', PARTNERED = ' . "'$PARTNERED'" . ', STATUS = ' . "'$STATUS'" . ', MODERATOR = ' . "'$MODERATOR'" . ', UPLOADER = ' . "'$customer_username'" . ', ID = ' . "'$scriptID'" . ', SCRIPT_NAME = ' . "'$script_name'" . ', SCRIPT_IMAGE_PATH = ' . "'$script_image_path'" . ', SCRIPT_TAGS = ' . "'$script_tags'" . ', SCRIPT_DESCRIPTION = ' . "'$script_description'" . ', SCRIPT_SOURCE_PATH = ' . "'$script_src_path'" . ', SCRIPT_ENC_KEY = ' . "'$script_enc_key'";
    $ret_data = $keydb->query("UPDATE `UploadsDB` SET $data WHERE `ID` = '$scriptID'");

    return $scriptID;
}

function Moderation_Update_Script($scriptID, $MODERATOR, $STATUS)
{
    $old_data = Fetch_Script_Info_By_ID($scriptID);
    
    $customer_ip = $old_data[1];
    $date = date('m-d-Y');
    $PARTNERED = $old_data[2];
    $customer_username = $old_data[5];
    $script_src_path = $old_data[11];
    $script_enc_key = $old_data[12];
    $script_name = $old_data[7];
    $script_image_path = $old_data[8];
    $script_tags = $old_data[9];
    $script_description = $old_data[10];

    $keydb = new mysqli("localhost", SCRIPTHUB_DB_USERNAME, SCRIPTHUB_DB_PASSWORD, SCRIPTHUB_DB);
    
    $data = 'DATE = ' . "'$date'" . ', IP = ' . "'$customer_ip'" . ', PARTNERED = ' . "'$PARTNERED'" . ', STATUS = ' . "'$STATUS'" . ', MODERATOR = ' . "'$MODERATOR'" . ', UPLOADER = ' . "'$customer_username'" . ', ID = ' . "'$scriptID'" . ', SCRIPT_NAME = ' . "'$script_name'" . ', SCRIPT_IMAGE_PATH = ' . "'$script_image_path'" . ', SCRIPT_TAGS = ' . "'$script_tags'" . ', SCRIPT_DESCRIPTION = ' . "'$script_description'" . ', SCRIPT_SOURCE_PATH = ' . "'$script_src_path'" . ', SCRIPT_ENC_KEY = ' . "'$script_enc_key'";
    $ret_data = $keydb->query("UPDATE `UploadsDB` SET $data WHERE `ID` = '$scriptID'");

    return $scriptID;
}

function Update_Existing_Script($scriptID, $script_name, $script_image_path, $script_tags, $script_description, $script_src)
{
    if (strlen($script_name) < 3 || strlen($script_name) > 40)
    {
        return "ERROR_INVALID_NAME";
    }
    
    if (strlen($script_description) < 3 || strlen($script_description) > 700)
    {
        return "ERROR_INVALID_DESCRIPTION";
    }
    
    if (strlen($script_tags) > 300)
    {
        return "ERROR_INVALID_SCRIPT_TAGS";
    }
    
    $update_source = false;
    
    if (strlen($script_src) > 3)
    {
        $update_source = true;
    }
    
    $old_data = Fetch_Script_Info_By_ID($scriptID);
    
    /* encode data entries */
    $script_description = base64_encode($script_description);
    
    $customer_ip = KSHDjkdhfkjsdhjk();
    $date = date('m-d-Y');
    $PARTNERED = $old_data[2];
    $STATUS = "PENDING_REVIEW"; //$old_data[3];
    $MODERATOR = "N/A"; //$old_data[4];
    $customer_username = $old_data[5];
    $script_src_path = $old_data[11];
    $script_enc_key = $old_data[12];
    $PARTNERED = $old_data[2];
    
    if ($update_source == true)
    {
        $script_src = base64_decode($script_src);
        if (strlen($script_src) >= 1000000)
        {
            return "ERROR_INVALID_SCRIPT_LENGTH";
        }
        
        $protected_script_src = Blowfish_Enc($script_src, $script_enc_key, "G+KbPdSgVkYp3s6v");
        Create_Script_Path($protected_script_src, $script_src_path);
    }

    $keydb = new mysqli("localhost", SCRIPTHUB_DB_USERNAME, SCRIPTHUB_DB_PASSWORD, SCRIPTHUB_DB);
    
    $data = 'DATE = ' . "'$date'" . ', IP = ' . "'$customer_ip'" . ', PARTNERED = ' . "'$PARTNERED'" . ', STATUS = ' . "'$STATUS'" . ', MODERATOR = ' . "'$MODERATOR'" . ', UPLOADER = ' . "'$customer_username'" . ', ID = ' . "'$scriptID'" . ', SCRIPT_NAME = ' . "'$script_name'" . ', SCRIPT_IMAGE_PATH = ' . "'$script_image_path'" . ', SCRIPT_TAGS = ' . "'$script_tags'" . ', SCRIPT_DESCRIPTION = ' . "'$script_description'" . ', SCRIPT_SOURCE_PATH = ' . "'$script_src_path'" . ', SCRIPT_ENC_KEY = ' . "'$script_enc_key'";
    $ret_data = $keydb->query("UPDATE `UploadsDB` SET $data WHERE `ID` = '$scriptID'");

    return $scriptID;
}

function Upload_New_Script($customer_username, $script_name, $script_image_path, $script_tags, $script_description, $script_src_path, $script_enc_key)
{
    if (strlen($script_name) < 3 || strlen($script_name) > 40)
    {
        return "ERROR_INVALID_NAME";
    }
    
    if (strlen($script_description) < 3 || strlen($script_description) > 700)
    {
        return "ERROR_INVALID_DESCRIPTION";
    }
    
    if (strlen($script_tags) > 300)
    {
        return "ERROR_INVALID_SCRIPT_TAGS";
    }
    
    /* encode data entries */
    $script_description = base64_encode($script_description);
    
    $customer_ip = KSHDjkdhfkjsdhjk();
    $date = date('m-d-Y');
    $script_id = HKJhfdkjdhkhsdkhk(10);
    
    $keydb = new mysqli("localhost", SCRIPTHUB_DB_USERNAME, SCRIPTHUB_DB_PASSWORD, SCRIPTHUB_DB);
    
    $sql = "INSERT INTO UploadsDB (DATE, IP, PARTNERED, STATUS, MODERATOR, UPLOADER, ID, SCRIPT_NAME, SCRIPT_IMAGE_PATH, SCRIPT_TAGS, SCRIPT_DESCRIPTION, SCRIPT_SOURCE_PATH, SCRIPT_ENC_KEY)
    VALUES ('$date', '$customer_ip', 'false', 'PENDING_REVIEW', 'N/A', '$customer_username', '$script_id', '$script_name', '$script_image_path', '$script_tags', '$script_description', '$script_src_path', '$script_enc_key')";
    
    $ret_data = $keydb->query($sql);
    
    return $script_id;
}

?>