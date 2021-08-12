<?php
date_default_timezone_set('America/New_York');

function getdatedif($old){
/*
$date1 = new DateTime($old);
$date2 = new DateTime("now");
$interval = $date1->diff($date2);
return $interval->format('%a');
*/

$date1 = new DateTime($old);
$date2 = new DateTime("now");
$interval = $date2->diff($date1);
return $interval->format("%r%a");
}

function getprovideddatedifs($old, $new)
{
    /*
    $date1 = new DateTime($old);
    $date2 = new DateTime("now");
    $interval = $date1->diff($date2);
    return $interval->format('%a');
    */
    
    $date1 = new DateTime($old);
    $date2 = new DateTime($new);
    $interval = $date2->diff($date1);
    return $interval->format("%r%a");
}

function returndate(){
$cool = getdate();
return $cool["year"] . '-' . $cool["mon"] . '-' . $cool["mday"];
}


function adddaystodate($date,$days){
    $date = strtotime("+".$days." days", strtotime($date));
    return  date("Y-m-d", $date);
}

function subtractdaystodate($date,$days){
    $date = strtotime("-".$days." days", strtotime($date));
    return  date("Y-m-d", $date);
}

function getnow(){
return date('Y-m-d');
}

function getnowtrial(){
return date('Y-m-d h:i:sa');
}

function gatetrialdate($old){
$date1 = new DateTime($old);
$date2 = new DateTime("now");
$interval = $date1->diff($date2);

$compiledate = $interval->d . " days " . $interval->h . " hours " . $interval->i . " minutes";

return $compiledate;
}

?>