<?php
header('Content-Type:text/plain');


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

$json = json_decode(file_get_contents("dashboard_data.json"), true);

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

echo $generatehtml;

?>