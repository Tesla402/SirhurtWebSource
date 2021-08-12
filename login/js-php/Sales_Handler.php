<?php
date_default_timezone_set('America/Denver');
include_once($_SERVER['DOCUMENT_ROOT'] . "/login/js-php/core.php");

function Record_Sales_Amount()
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `RecordSalesDB`") or die(mysqli_error($keydb));

    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        return array($entry['date'], $entry['amount']);
    }
    
    return array("NONE", 0.00);
}

function Update_Record_Sales($value)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $key = date('Y-m-d');
    
    $data = 'date = ' . "'$key'" . ', amount = ' . "'$value'" . ', constant = ' . "'ALWAYS_THIS'";
    return $keydb->query("UPDATE `RecordSalesDB` SET $data WHERE `constant` = 'ALWAYS_THIS'");
}

function Fetch_Sales_History($date)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $loop = mysqli_query($keydb, "SELECT * FROM `SalesHistoryDB` WHERE `date` = '$date'") or die(mysqli_error($keydb));

    /* Should only ever be one entry */
    while ($entry = mysqli_fetch_array($loop))
    {
        return array($entry['date'], $entry['estimate'], $entry['paypal_estimate']);
    }
    
    return array("NONE", 0.00, 0.00);
}

function Log_Sale_History($date, $value, $paypal_value)
{
    $key = trim($okey);
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $sql = "INSERT INTO SalesHistoryDB (date, estimate, paypal_estimate)
    VALUES ('$date', '$value', '$paypal_value')";
    
    return $keydb->query($sql);
}

function Update_Sales_History($value, $paypal_value)
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    $key = date('Y-m-d');
    
    $history = Fetch_Sales_History($key);
    if ($history[0] == "NONE")
    {
        return Log_Sale_History($key, $value, $paypal_value);
    }
    
    $data = 'date = ' . "'$key'" . ', estimate = ' . "'$value'" . ', paypal_estimate = ' . "'$paypal_value'";
    return $keydb->query("UPDATE `SalesHistoryDB` SET $data WHERE `date` = '$key'");
}

function Calculate_Sales_History($key = "NONE")
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    if ($key == "NONE")
        $key = date('Y-m-d');
        
    if ($key == "MONTH")
        $key = date('Y-m');
        
    if ($key == "YEAR")
        $key = date('Y');
    
    $loop = mysqli_query($keydb, "SELECT * FROM `SalesHistoryDB` WHERE `date` LIKE '%$key%'") or die(mysqli_error($keydb));
    $usd_value = 0.0;
    
    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        if ($entry['estimate'] > $entry['paypal_estimate'])
            $usd_value = $usd_value + $entry['estimate'];
        else
            $usd_value = $usd_value + $entry['estimate'] + $entry['paypal_estimate'];
    }
    
    return $usd_value;
}

function Calculate_Sales($key = "NONE")
{
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    if ($key == "NONE")
        $key = date('Y-m-d');
    
    $loop = mysqli_query($keydb, "SELECT * FROM `SalesDB` WHERE `date` = '$key'") or die(mysqli_error($keydb));
    $usd_value = 0.0;
    
    /* Search for Key */
    while ($entry = mysqli_fetch_array($loop))
    {
        $usd_value = $usd_value + $entry['usd_value'];
    }
    
    /* Add Paypal Sales */
    $paypal_sales = (float)file_get_contents("https://sirhurt.net/login/js-php/calculate_sales.php?date=" . urlencode($key));
    $history = Fetch_Sales_History($key);
    
    if ($history[0] == "NONE" || $history[1] < $usd_value + $paypal_sales)
    {
        Update_Sales_History($usd_value, $paypal_sales); //removed paypal calculation from total sales, as information is currently unreliable.
    }
    
    if (Record_Sales_Amount()[1] < $usd_value + $paypal_sales)
    {
        Update_Record_Sales($usd_value + $paypal_sales);
        Update_Sales_History($usd_value, $paypal_sales); //removed paypal calculation from total sales, as information is currently unreliable.
    }
    
    return $usd_value; //+ $paypal_sales
}

function Log_Sale($value, $payment_method)
{
    $key = trim($okey);
    $keydb = new mysqli("localhost", AERO_DB_USERNAME, AERO_DB_PASSWORD, AERO_DB);
    
    $date = date('Y-m-d');
    $sql = "INSERT INTO SalesDB (date, usd_value, payment_method)
    VALUES ('$date', '$value', '$payment_method')";
    
    /* Keep up to Record 4/21/2020 */
    Calculate_Sales();

    return $keydb->query($sql);
}
?>