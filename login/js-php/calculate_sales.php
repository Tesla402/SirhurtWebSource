<?php
include_once($_SERVER['DOCUMENT_ROOT']."/vendor/autoload.php");
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

use \Selly as Selly;
Selly\Client::authenticate("ashpokeman@gmail.com", SELLY_API_KEY);

date_default_timezone_set('America/Denver');

$calculated_price = 0;
$stop = false;

$lookup_date = date('Y-m-d');
$found_date = false;
$debug = 0;

if (isset($_GET['date']))
{
    $lookup_date = urldecode($_GET['date']);
}


$prevent_duplicates = array();


for ($x = 0; $x <= 13; $x++) 
{
    if ($stop == true)
        break;
    
    $orders = Selly\Orders::list($x);
    
    foreach ($orders as $value) 
    {
        $order = (array)$value;
        
        if ($order['status'] == 100)
        {
            //echo var_dump($order); exit();
            
            $price = $order['usd_value'];
            $date = $order['updated_at'];

            $quantity = $order['quantity'];
            
            if (substr($date, 0, 10) != $lookup_date)
            {
                continue;
            }
            
            if ($found_date == false && substr($date, 0, 10) == $lookup_date)
            {
                $found_date = true;
            }
            
            if (substr($date, 0, 10) != $lookup_date && $found_date == true)  //2020-04-21
            {
                $stop = true;
                break;
            }
            
            if (substr($date, 0, 10) == $lookup_date)
            {
                if (!$prevent_duplicates[$order['id']])
                {
                    $debug++;
                    
                    /*echo var_dump($order);
                    
                    echo $debug;
                    echo "<br>";
                    echo "<br>";*/
                    
                    $prevent_duplicates[$order['id']] = true;
                    $calculated_price = (float)$calculated_price + (float)$price;
                }
            }
        }
    }
}

echo $calculated_price;
?>