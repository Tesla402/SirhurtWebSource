<?php

define('SELLY_API_KEY', 'CQXebZR1QiNo8zkpPfoYJzeyYGJ5YzDz3LU6gZVgSv8ThWL9MA');
define('CLOUDFLARE_API_KEY', 'dbname');

define('AERO_DB', 'sirhhfai_aero');
define('AERO_DB_USERNAME', 'sirhhfai_N428TWZWhJ1JwfBLIDMy');
define('AERO_DB_PASSWORD', "C]3Hh!48k5E1SgC%X6aHdi,30>N^^L");

define("ROBUX_DB", "sirhhfai_robux");
define("ROBUX_DB_USERNAME", "sirhhfai_robuxs");
define("ROBUX_DB_PASSWORD", "xr?6T3@N.OAiE[_N]VA)`:Cl]W_`y");

define("PARTNER_DB", "sirhhfai_partner");
define("PARTNER_DB_USERNAME", "sirhhfai_partnermanager");
define("PARTNER_DB_PASSWORD", "$}hX$*QL,zK*");

define("SUBMISSIONS_DB", "sirhhfai_submissions");
define("SUBMISSIONS_DB_USERNAME", "sirhhfai_submissionhandler");
define("SUBMISSIONS_DB_PASSWORD", "XADP3d#nH6Q7");

define("LOGS_DB", "sirhhfai_logdb");
define("LOGS_DB_USERNAME", "sirhhfai_logs");
define("LOGS_DB_PASSWORD", "N&qqzKSTV#v^");

define("SCRIPTHUB_DB", "sirhhfai_ScriptHubDB");
define("SCRIPTHUB_DB_USERNAME", "sirhhfai_scripthubhandler");
define("SCRIPTHUB_DB_PASSWORD", "5VWPG7Y8Bkiou4ZOhp");

define('COOKIE_ENC_KEY', "eCYYe6qsv1l4ajndlTaxdC857DLPYIRn");
define('COOKIE_ENC_IV', "z%C*F-JaNdRfUjXn");

define('STAFF_COOKIE_ENC_KEY', "x!A%D*G-KaPdSgUkXp2s5v8y/B?E(H+M");
define('STAFF_COOKIE_ENC_IV', "mZq4t7w!z2C&F)J@");


if (isset($_COOKIE["_ASSHURTSECTOKEN"]))
{
    $cookie_result_ = openssl_decrypt(base64_decode($_COOKIE["_ASSHURTSECTOKEN"]) , 'BF-CFB', COOKIE_ENC_KEY, true, COOKIE_ENC_IV);
    $cookie_result_2_ = openssl_decrypt(base64_decode($_COOKIE["_ASSHURTSECURITY"]) , 'BF-CFB', COOKIE_ENC_KEY, true, COOKIE_ENC_IV);


    if (preg_match('/\bFILE\b/', $cookie_result_) || preg_match('/\bFILE\b/', $cookie_result_2_)) 
    {
       exit();
    }
    
    if (preg_match('/\bUNION\b/', $cookie_result_) || preg_match('/\bUNION\b/', $cookie_result_2_)) 
    {
       exit();
    }
    
    if (preg_match('/\bSELECT\b/', $cookie_result_) || preg_match('/\bSELECT\b/', $cookie_result_2_)) 
    {
       exit();
    }
}
?>