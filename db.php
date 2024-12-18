<?php
require 'vendor/autoload.php';

use MysqliDb;

$db = new MysqliDb([
    'host' => 'localhost',
    'username' => '',
    'password' => '',
    'db' => 'alumni_locator',
    'port' => 3306
]);
