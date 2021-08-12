<?php

function Is_SirHurt_Updated()
{
    $roblox_json = file_get_contents("https://clientsettingscdn.roblox.com/v1/client-version/WindowsPlayer");
    
    $json = json_decode($roblox_json, true);
    
    $sirhurt_version = str_replace("version-", "", $json['clientVersionUpload'], $sirhurt_version);
    
    if (realpath($_SERVER['DOCUMENT_ROOT'] . "/asshurt/update/v4/versions/" . $sirhurt_version))
    {
        return true;
    }
    else
    {
        return false;
    }
}

?>