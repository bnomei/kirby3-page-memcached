<?php
require 'kirby/bootstrap.php';

$time = microtime(true);
echo (new Kirby)->render();
echo microtime(true) - $time;
