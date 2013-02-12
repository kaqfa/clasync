<?php
include 'syncml.php';

if(isset($_POST['message'])){
    $syncml = new SyncML('init.xml');
    //$syncml->getMessage($_POST['message'],'string');    
    $syncml->doProccess();
} else {
    echo 'Direct Access is Prohibited ... !!!';
}