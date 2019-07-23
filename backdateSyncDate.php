<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';

use Medoo\Medoo;
$database = new Medoo($databaseconfig);


$database->update("options",["option_value"=>"2010-01-01"],["option_name"=>"lastSyncDate"])->rowCount();


?>
