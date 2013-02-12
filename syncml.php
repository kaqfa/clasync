<?php
require('db.php');
require('header.php');
require('body.php');

//$syncml = new SyncML("sync.xml");
//$syncml->getMessage("sync.xml");
//$syncml->doProccess();

class SyncML {

    private $header;
    private $body;
    private $xml = null;

    function __construct($data,$from = 'url') {
        $this->getMessage($data,$from);
        $this->header = new Header($this->xml->SyncHdr);
        $this->body = new Body($this->xml->SyncBody);
    }
    
    function doProccess(){
        if($this->header->validateCred() == true){
           if($this->header->validateURI() == true) {
               if($this->body->validateAnchor($this->header->getSource()->deviceId) == false){
                  $this->body->setMode ('400'); // set to slow synchronization
               }  else { 
                   $this->body->setMode('200');                   
                   if($this->body->getCmd() == '2' && $this->body->getData() != null){ //
                       //echo 'ini dieksekusi';
                       echo $this->body->executeChange();
                   }
                }
           } else {
               echo "Target is not valid";
               exit();
           }
        } else {
            echo "user doesn't exist";
            exit();
        }        
        $this->sendReply();
    }
    
    function sendReply(){
        $xmlReply = simplexml_load_string('<SyncML></SyncML>');
        
        $domHeader  = dom_import_simplexml($this->header->generateHeader());
        $domBody    = dom_import_simplexml($this->body->generateInit());
        $domReply   = dom_import_simplexml($xmlReply);
        
        $domHeader  = $domReply->ownerDocument->importNode($domHeader,TRUE);
        $domBody    = $domReply->ownerDocument->importNode($domBody, TRUE);
        
        $domReply->appendChild($domHeader);
        $domReply->appendChild($domBody);
        
        echo $xmlReply->asXML();
        //print_r($_SESSION);
    }

    function getMessage($data,$from = 'url') {
        try{
            if($from === 'url'){
                $this->xml = simplexml_load_file($data);
            } else if($from === 'string'){
                $this->xml = simplexml_load_string($data);
            }
        } catch (Exception $e){
            echo 'don\'t worry: '.$e;
        }        
    }

    function setHeader($xmlHeader) {
        $this->header = $xmlHeader;
    }

    function setBody($xmlBody) {
        $this->body = $xmlBody;
    }
    
}

