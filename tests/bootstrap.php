<?php require_once __DIR__ . '/bootstrap.php';

$kirby = new Kirby();

$time = microtime(true);
echo $kirby->render();
echo microtime(true) - $time;
