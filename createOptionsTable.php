<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';

use Medoo\Medoo;

$database = new Medoo($databaseconfig);

$createtablesql = "
CREATE TABLE options (
	option_id int IDENTITY(1,1) PRIMARY KEY,
	option_name varchar(255) NOT NULL,
	option_value varchar(1024)
);
";

$database->query($createtablesql)

?>