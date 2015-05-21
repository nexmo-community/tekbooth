<?php
$pimple = include __DIR__ . '/../init.php';

/* @var $assistant TekBooth\Controller\Assistant */
$assistant = $pimple['assistant'];

if(empty($_GET['action'])){
    $_GET['action'] = 'default';
}

session_start();

error_log(json_encode($_SESSION));

switch($_GET['action']){
    //take a photo
    case 'photo':
        $assistant->takePhoto($_SESSION['number'], $_SESSION['callid'], $_GET);
        echo "<?xml version='1.0' encoding='UTF-8' ?><demo></demo>";
        break;

    //render an IVR
    default:
        $_SESSION['number'] = $_GET['nexmo_caller_id'];
        $_SESSION['callid'] = $_GET['nexmo_call_id'];
        echo $assistant->getVxml($_GET['nexmo_caller_id'], $_GET['nexmo_call_id']);
}