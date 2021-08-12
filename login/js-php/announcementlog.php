<?php
header('Content-Type:text/plain');


$examplehtml = '<li class="info-element ui-sortable-handle">
MSGGGGGGGGG
<div class="agile-detail">
 <i class="fa fa-clock-o"></i>
</div>
</li>';

$json = json_decode(file_get_contents("dashboard_data.json"), true);

//var_dump($json);

$generatehtml = "";

foreach($json['announcements'] as $message)
{
//echo $message;
$generatehtml = $generatehtml . str_replace("MSGGGGGGGGG", $message, $examplehtml) . "\n";
}

echo $generatehtml;

?>