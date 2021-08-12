<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function SHdskjfhksjdhf() 
{
    if(getenv('HTTP_CF_CONNECTING_IP'))
         $_SERVER['REMOTE_ADDR'] = preg_replace('/^([^,]*).*$/', '$1', getenv('HTTP_CF_CONNECTING_IP'));

    return $_SERVER['REMOTE_ADDR'];
}

function Fetch_Ticket_By_ID($ticketid)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `TICKET_ID` = '$ticketid'") or die(mysqli_error($keydb));

    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        return array($entry['CUST_IP'], $entry['QUERY_TYPE'], $entry['CUST_USERNAME'], $entry['TICKET_STATUS'], $entry['QUERY_JSON'], $entry['ASSISTING_STAFF_JSON']);
    }
    
    return array("NONE", "NONE", "NONE", "NONE", "NONE");
}

function Fetch_Tickets_Resolved_By_Staff_Member($username)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `ASSISTING_STAFF_JSON` LIKE '%$username%'") or die(mysqli_error($keydb));
    $tickets = array();
    $ticketnums = 0;
    
    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        $tickets[$ticketnums] = array($entry['TICKET_ID'], $entry['TICKET_STATUS'], $entry['QUERY_JSON'], $entry['ASSISTING_STAFF_JSON']);
        $ticketnums++;
    }
    
    return $tickets;
}

function Fetch_Tickets_By_Username($username)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `CUST_USERNAME` = '$username'") or die(mysqli_error($keydb));
    $tickets = array();
    $ticketnums = 0;
    
    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        $tickets[$ticketnums] = array($entry['TICKET_ID'], $entry['TICKET_STATUS'], $entry['QUERY_JSON']);
        $ticketnums++;
    }
    
    return $tickets;
}

function Fetch_All_Open_Tickets()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `TICKET_STATUS` = 'OPEN'") or die(mysqli_error($keydb));
    $tickets = array();
    $ticketnums = 0;
    
    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        $tickets[$ticketnums] = array($entry['TICKET_ID'], $entry['TICKET_STATUS'], $entry['QUERY_JSON']);
        $ticketnums++;
    }
    
    return $tickets;
}

function Fetch_Closed_Tickets()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `TICKET_STATUS` != 'OPEN'") or die(mysqli_error($keydb));
    $tickets = array();
    $ticketnums = 0;
    
    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        $tickets[$ticketnums] = array($entry['TICKET_ID'], $entry['TICKET_STATUS'], $entry['QUERY_JSON'], $entry['ASSISTING_STAFF_JSON']);
        $ticketnums++;
    }
    
    return $tickets;
}

function Fetch_Open_Tickets_Needing_Staff_Replies()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `TICKET_STATUS` = 'OPEN'") or die(mysqli_error($keydb));
    $tickets = array();
    $ticketnums = 0;
    
    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        $decoded_data = json_decode($entry['QUERY_JSON'])->Queries[0];
        
        /* If latest ticket reply is not a staff member, ticket needs to be answered. */
        if ($decoded_data->IsStaff == false)
        {
            $tickets[$ticketnums] = array($entry['TICKET_ID'], $entry['TICKET_STATUS'], $entry['QUERY_JSON']);
            $ticketnums++;
        }
    }
    
    return $tickets;
}

function Open_Ticket_Count()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `TICKET_STATUS` = 'OPEN'") or die(mysqli_error($keydb));
    $ticketnums = 0;
    
    while ($entry = mysqli_fetch_array($loop))
    {
        $decoded_data = json_decode($entry['QUERY_JSON'])->Queries[0];
        
        /* If latest ticket reply is not a staff member, ticket needs to be answered. */
        if ($decoded_data->IsStaff == false)
        {
            $ticketnums++;
        }
    }
    
    return $ticketnums;
}

function Does_Tickets_Need_User_Reply($username)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `CUST_USERNAME` = '$username'") or die(mysqli_error($keydb));
    $ticketnums = 0;
    
    while ($entry = mysqli_fetch_array($loop))
    {
        $decoded_data = json_decode($entry['QUERY_JSON'])->Queries[0];
        
        /* If latest ticket reply is not a staff member, ticket needs to be answered. */
        if ($decoded_data->IsStaff == true && $entry['TICKET_STATUS'] == "OPEN")
        {
            $ticketnums++;
        }
    }
    
    return $ticketnums;
}

/*
function Open_Ticket_Count()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `TICKET_STATUS` = 'OPEN'") or die(mysqli_error($keydb));
    $tickets = array();
    $ticketnums = 0;
    
    while ($entry = mysqli_fetch_array($loop))
    {
        $ticketnums++;
    }
    
    return $ticketnums;
}
*/

function Does_User_Have_Open_Ticket($username)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `TicketsDB` WHERE `CUST_USERNAME` = '$username'") or die(mysqli_error($keydb));
    $answer = false;
    
    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry['TICKET_STATUS'] == "OPEN")
        {
            $answer = true;
        }
    }
    
    return $answer;
}

function Update_Ticket_By_ID($ticketid, $status, $query)
{
    $ticket_info = Fetch_Ticket_By_ID($ticketid);
    
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $customer_ip = $ticket_info[0];
    $query_type = $ticket_info[1];
    $customer_username = $ticket_info[2];
    $ASSISTING_STAFF = $ticket_info[5];
    
    $data = 'TICKET_ID = ' . "'$ticketid'" . ', QUERY_TYPE = ' . "'$query_type'" . ', CUST_IP = ' . "'$customer_ip'" . ', CUST_USERNAME = ' . "'$customer_username'" . ', TICKET_STATUS = ' . "'$status'" . ', ASSISTING_STAFF_JSON = ' . "'$ASSISTING_STAFF'" . ', QUERY_JSON = ' . "'$query'";
    return $keydb->query("UPDATE `TicketsDB` SET $data WHERE `TICKET_ID` = '$ticketid'");
}

function Fetch_Ticket_Replies_By_ID($ticketid)
{
    $ticket_information = Fetch_Ticket_By_ID($ticketid);
    $replies = json_decode($ticket_information[4])->Queries;
    
    return $replies;
}

function Add_Ticket_Reply($ticketid, $reply, $Member, $IsStaff, $Rank)
{
    $ticket_info = Fetch_Ticket_By_ID($ticketid);
    $date = date('Y-m-d');
    
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $customer_ip = $ticket_info[0];
    $query_type = $ticket_info[1];
    $customer_username = $ticket_info[2];
    $status = $ticket_info[3];
    $query = $ticket_info[4];
    $ASSISTING_STAFF = $ticket_info[5];
    
    $myObj = 
    array(
         "Query" => strip_tags($reply),
         "Member" => $Member,
         "IsStaff" => $IsStaff,
         "Rank" => $Rank,
         "Query_Date" => $date
    );  
    
    if (($ASSISTING_STAFF == "NONE" || $ASSISTING_STAFF == "null" || strlen($ASSISTING_STAFF) <= 4) && $IsStaff == true)
    {
        $ASSISTING_STAFF = array($Member);
        $ASSISTING_STAFF = json_encode($ASSISTING_STAFF);
    }
    else if ($ASSISTING_STAFF != "NONE" && $IsStaff == true)
    {
        $ASSISTING_STAFF = json_decode($ASSISTING_STAFF);
        
        if (!in_array($Member, $ASSISTING_STAFF))
        {
            array_unshift($ASSISTING_STAFF, $Member);
        }
        
        $ASSISTING_STAFF = json_encode($ASSISTING_STAFF);
    }
    
    $decoded_query = json_decode($query);
    array_unshift($decoded_query->Queries, $myObj);
    $encoded_query = json_encode($decoded_query);

    $data = 'TICKET_ID = ' . "'$ticketid'" . ', QUERY_TYPE = ' . "'$query_type'" . ', CUST_IP = ' . "'$customer_ip'" . ', CUST_USERNAME = ' . "'$customer_username'" . ', TICKET_STATUS = ' . "'$status'" . ', ASSISTING_STAFF_JSON = ' . "'$ASSISTING_STAFF'" . ', QUERY_JSON = ' . "'$encoded_query'";
    return $keydb->query("UPDATE `TicketsDB` SET $data WHERE `TICKET_ID` = '$ticketid'");
}

function Close_Ticket_By_ID($ticketid)
{
    $ticket_info = Fetch_Ticket_By_ID($ticketid);
    
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $customer_ip = $ticket_info[0];
    $query_type = $ticket_info[1];
    $customer_username = $ticket_info[2];
    $query = $ticket_info[4];
    $ASSISTING_STAFF = $ticket_info[5];

    $data = 'TICKET_ID = ' . "'$ticketid'" . ', QUERY_TYPE = ' . "'$query_type'" . ', CUST_IP = ' . "'$customer_ip'" . ', CUST_USERNAME = ' . "'$customer_username'" . ', TICKET_STATUS = ' . "'CLOSED'" . ', ASSISTING_STAFF_JSON = ' . "'$ASSISTING_STAFF'" . ', QUERY_JSON = ' . "'$query'";
    return $keydb->query("UPDATE `TicketsDB` SET $data WHERE `TICKET_ID` = '$ticketid'");
}

function Reopen_Ticket_By_ID($ticketid)
{
    $ticket_info = Fetch_Ticket_By_ID($ticketid);
    $query = $ticket_info[4];

    Update_Ticket_By_ID($ticketid, "OPEN", $query);
}

function Mark_Resolved_Ticket_By_ID($ticketid)
{
    $ticket_info = Fetch_Ticket_By_ID($ticketid);
    
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $customer_ip = $ticket_info[0];
    $query_type = $ticket_info[1];
    $customer_username = $ticket_info[2];
    $query = $ticket_info[4];
    $ASSISTING_STAFF = $ticket_info[5];

    $data = 'TICKET_ID = ' . "'$ticketid'" . ', QUERY_TYPE = ' . "'$query_type'" . ', CUST_IP = ' . "'$customer_ip'" . ', CUST_USERNAME = ' . "'$customer_username'" . ', TICKET_STATUS = ' . "'SOLVED'" . ', ASSISTING_STAFF_JSON = ' . "'$ASSISTING_STAFF'" . ', QUERY_JSON = ' . "'$query'";
    return $keydb->query("UPDATE `TicketsDB` SET $data WHERE `TICKET_ID` = '$ticketid'");
}

function Create_New_Ticket($TICKET_ID, $QUERY_TYPE, $CUST_USERNAM, $QUERY_JSON)
{
    $customer_ip = SHdskjfhksjdhf();
    $key = trim($okey);
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $sql = "INSERT INTO TicketsDB (TICKET_ID, QUERY_TYPE, CUST_IP, CUST_USERNAME, TICKET_STATUS, ASSISTING_STAFF_JSON, QUERY_JSON)
    VALUES ('$TICKET_ID', '$QUERY_TYPE', '$customer_ip', '$CUST_USERNAM', 'OPEN', 'NONE', '$QUERY_JSON')";
    
    return $keydb->query($sql);
}

?>