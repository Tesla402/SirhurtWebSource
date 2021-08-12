<?php

unset($_COOKIE['_ASSHURTSECURITY']);
setcookie("_ASSHURTSECURITY", "", time() - 3600, "/", "sirhurt.net");

unset($_COOKIE['_ASSHURTSECTOKEN']);
setcookie("_ASSHURTSECTOKEN", "", time() - 3600, "/", "sirhurt.net");
setcookie("_ASSHURTSECTOKEN", "", time() - 3600, "/");

if (isset($_COOKIE["_ASSHURTSTAFFTOKEN"]))
{
    unset($_COOKIE['_ASSHURTSTAFFTOKEN']);
    setcookie("_ASSHURTSTAFFTOKEN", "", time() - 3600, "/", "sirhurt.net");
}

header("Location: login.html");
die();

?>