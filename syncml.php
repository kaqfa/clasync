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

  function __construct($data, $from = 'url') {
    $this->getMessage($data, $from);
    $this->header = new Header($this->xml->SyncHdr);
    $this->body = new Body($this->xml->SyncBody);
  }

  function doProccess() {
    if ($this->header->validateCred() == true) {
      if ($this->header->validateURI() == true) {
        if ($this->body->getCmd() == 1) {
          $uuid = $this->header->getSource()->deviceId;
          if ($this->body->validateAnchor($uuid) == false) {
            $this->body->setMode('400'); // set to slow synchronization
            // delete all map
          } else {
            $this->body->setMode('200');
          }
        } else if ($this->body->getCmd() == '2' && $this->body->getData() != null) {          
          $this->body->executeChange();         
        }
        $this->sendReply();
      } else {
        $this->sendReply(3);
        exit();
      }
    } else {
      $this->sendReply(2);
      exit();
    }
  }

  function sendReply($type = 1) { // 1 = success; 2 = invaliduser; 3 = invalidlocation
    $xmlReply = simplexml_load_string('<SyncML></SyncML>');

    $domHeader = dom_import_simplexml($this->header->generateHeader($type));
    $domBody = dom_import_simplexml($this->body->generateReply());
    $domReply = dom_import_simplexml($xmlReply);

    $domHeader = $domReply->ownerDocument->importNode($domHeader, TRUE);
    $domBody = $domReply->ownerDocument->importNode($domBody, TRUE);

    $domReply->appendChild($domHeader);
    $domReply->appendChild($domBody);

    echo $xmlReply->asXML();
    //print_r($_SESSION);
  }

  function getMessage($data, $from = 'url') {
    try {
      if ($from === 'url') {
        $this->xml = simplexml_load_file($data);
        //print_r($this->xml);
      } else if ($from === 'string') {
        //echo $data;
        $this->xml = simplexml_load_string($data);
      }
    } catch (Exception $e) {
      echo 'don\'t worry: ' . $e;
    }
  }

  function setHeader($xmlHeader) {
    $this->header = $xmlHeader;
  }

  function setBody($xmlBody) {
    $this->body = $xmlBody;
  }

}

