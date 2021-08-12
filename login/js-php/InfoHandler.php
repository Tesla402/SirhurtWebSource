<?php
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function CheckBuildVersion()
{
    return "4A";
}

function CheckLogins()
{
    return "6 Million+";
}

function RegisteredUsers()
{
    $userdb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, "sirhhfai_aero");
    $loop = mysqli_query($userdb, "SELECT 'flagged' FROM UserDB") or die (mysqli_error($userdb));
    
    return mysqli_num_rows($loop);
}

function AnnouncementLog()
{
    $examplehtml = '<li class="info-element ui-sortable-handle">
    MSGGGGGGGGG
    <div class="agile-detail">
     <i class="fa fa-clock-o"></i>
    </div>
    </li>';
    
    $json = json_decode(file_get_contents("js-php/dashboard_data.json"), true);
    
    //var_dump($json);
    
    $generatehtml = "";
    
    foreach($json['announcements'] as $message)
    {
        $generatehtml = $generatehtml . str_replace("MSGGGGGGGGG", $message, $examplehtml) . "\n";
    }
    
    return $generatehtml;
}

function DeveloperLog()
{
    $examplehtml = '<li class="primary-element ui-sortable-handle">
    MSGGGGGGGGG
    <div class="agile-detail">
    <i class="fa fa-clock-o"></i>
    </div>
    </li>';
    
    $json = json_decode(file_get_contents("js-php/dashboard_data.json"), true);
    
    //var_dump($json);
    
    $generatehtml = "";
    
    foreach($json['developerlogs'] as $message)
    {
        $generatehtml = $generatehtml . str_replace("MSGGGGGGGGG", $message, $examplehtml) . "\n";
    }
    
    return $generatehtml;
}

function Downloads_Developer_Log()
{
    /*$examplehtml = '<div class="panel panel-primary">
    <div class="panel-heading">
    TITLEEEEEEE
    <div class="pull-right">
    </div>
    </div>
    <div class="panel-body">MSGGGGGGGGG
    </div>
    </div>';*/
    
    $examplehtml = '
    <li class="list-group-item"><span class="text-muted">TITLEEEEEEE</span>
    MSGGGGGGGGG
    </li>
    ';
    
    $json = json_decode(file_get_contents("js-php/dashboard_data.json"), true);
    
    //var_dump($json);
    //exit();
    
    $generatehtml = "";
    
    foreach($json['updatelog'] as $message)
    {
        //echo $message;
        
        $changes = "";
        $generate = $examplehtml;
        $generate = str_replace("TITLEEEEEEE", $message['title'], $generate);
        
        foreach($message['updates'] as $changelist){
            //$changes = $changes . "<li>" . $changelist . "</li>";
            $changes = $changes . "<p>" . $changelist . "</p>";
        }
        
        $generate = str_replace("MSGGGGGGGGG", $changes, $generate);
        
        $generatehtml = $generatehtml . $generate . "\n";
    }
    
    return $generatehtml;
}

?>