<?php
require_once('config.php');
require_once('Db.php');
$db = new \My\Simpledb(array('db' => $db_name, 'host' => $db_host, 'user' => $db_user, 'pass' => $db_pass));

require_once(__DIR__ . '/Dtimer.php');

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);


$res = msgpack_unpack($redis->get($argv[1]));
print_r($res);
