<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';

use Medoo\Medoo;

$database = new Medoo($databaseconfig);

$jobs = $database->select("Jobs",["JobNo", "Project"]);

foreach($jobs as $job) {
    echo($job["JobNo"] . ", " . $job["Project"] . "\r\n");
}

?>