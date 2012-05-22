<?php

ini_set("memory_limit", "256M");

include 'RowClassesGenerator.php';

define('DB_HOST', 'localhost');
define('DB_USER', 'user_prod');
define('DB_PASS', 'mipass');
define('DB_NAME', 'bd_principal');

$rcg = new RowClassesGenerator();
$rcg->setDbConfig(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$rcg->addSchemas(array(DB_HOST => 'Web', 'other_db' => 'OtherDB'));

$rcg->run();

