<?php
include 'syncml.php';

if(isset($_POST['message'])){
    $syncml = new SyncML($_POST['message'],'string');
    $syncml->doProccess();       
} else {    
    $syncml = new SyncML("init.xml");
    $syncml->doProccess();
}