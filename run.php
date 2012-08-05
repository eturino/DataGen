<?php

ini_set("memory_limit", "256M");

include_once './RowClassesGenerator.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'pass');
define('DB_NAME', 'database');
define('DB_COLLATE', '');

$rcg = new EtuDev_DataGen_RowClassesGenerator();
$rcg->setDbConfig(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$rcg->addSchemas(array(DB_NAME => 'Web', 'other_db' => 'OtherDB'));

$rcg->run();

