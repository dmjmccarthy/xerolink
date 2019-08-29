<?php
$xeroconfig = [
    'oauth' => [
        'callback' => 'http://localhost/',
        'consumer_key' => 'k',
        'consumer_secret' => 's',
        'rsa_private_key' => 'file://privatekey.pem',
    ],
];

$databaseconfig = [
    'database_type' => 'mssql',
    'database_name' => 'DATABASENAME',
    'server' => 'SERVERNAME',
 
    // [optional] The application name
    'appname' => 'Integration'/*,
 
    // [optional] If you want to force Medoo to use dblib driver for connecting MSSQL database
    'driver' => 'dblib'*/
];
?>