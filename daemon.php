<?php
$pimple = include './init.php';

$name = $argv[1];
$service = $pimple[$name];

if($service instanceof \TekBooth\Daemon\ClosureDaemon){
    $service->start();
} else {
    throw new \RuntimeException('not valid daemon: ' . $name);
}

