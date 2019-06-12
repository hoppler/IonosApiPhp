<?php

#extension=php_openssl.dll

require('ionosApi.php');
require('ionosSettings.php');
use IonosApi\IonosUpdater;
use IonosApi\IonosSettings;

$settings = IonosSettings::FromFile("settings.json");
main();

function main()
{
    global $settings;

    $requestVars = $_GET;
    $getParametersExist = isset($requestVars['ip'], $requestVars['authKey'], $requestVars['domain']);
    if (!$getParametersExist) {
        http_response_code(400);
        return;
    }

    if ($requestVars['authKey'] !== $settings->AuthKey)
    {
        http_response_code(403);
        return;
    }

    $ip = $requestVars['ip'];
    $domain = $requestVars['domain'];

    if(!filter_var($ip, FILTER_VALIDATE_IP))
    {
        http_response_code(400);
        return;
    }

    if(!in_array($domain, $settings->AllowedDomains))
    {
        http_response_code(403);
        return;
    }

    $updateResult = updateDns($domain, $ip);
    $statusCode = 200;
    switch ($updateResult) {
        case 0:
            break;
        case null:
            $statusCode = 400;
            break;
        case -1:
            $statusCode = 401;
            break;
        default:
            $statusCode = 500;
            break;
    }

    http_response_code($statusCode);
}

function updateDns(&$domain, &$ip)
{
    global $settings;
    $ionosUpdater = new IonosUpdater();

    $loginRes = $ionosUpdater->Login($settings->Username, $settings->Password);
    if (!$loginRes) {
        echo "Login Failed";
        return -1;
    }

    $updateRes = $ionosUpdater->UpdateARecord($settings->RootDomain, $domain, $ip, $settings->Ttl, $settings->IsWwwRecord);
    $ionosUpdater->Logout();

    return $updateRes ? 0 : 1;
}

