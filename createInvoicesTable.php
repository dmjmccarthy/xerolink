<?php
require __DIR__ . '/vendor/autoload.php';
require 'config.php';

use Medoo\Medoo;

$database = new Medoo($databaseconfig);

$createtablesql = "
CREATE TABLE XeroInvoices (
	XeroID varchar(36),
	JobNo varchar(8),
	InvoiceNumber varchar(255),
	ContactName varchar(255),
	InvoiceDate date,
	SubTotal smallmoney,
	InvoiceStatus varchar(17),
	PRIMARY KEY (XeroID),
);
";

/*FOREIGN KEY (JobNo) REFERENCES Jobs(JobNo)*/

$database->query($createtablesql)->fetchAll()

?>