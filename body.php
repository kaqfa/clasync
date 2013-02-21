<?php 
require('jsondata.php');

class Body{
  
  private $cmd;
  private $mode;
  private $anchor;
  private $jsondata; 
  private $data;
  
  private $q;
  private $xml;
  private $element;


  function __construct($body){
    $this->cmd = $body->CmdID;
    $this->mode = $body->Mode;
    $this->anchor = $body->Anchor;
    $this->data = $body->Data;
    $this->xml = simplexml_load_string('<SyncBody></SyncBody>');
    
    $this->setElement();
  }
  
  function getCmd(){
      return $this->cmd;
  }
  
  function getData(){
      return $this->data;
  }
  
  function setMode($mode){
      $this->mode = $mode;
  }
  
  function validateAnchor($devId){
      $this->q = 'SELECT * from sync_anchors where dev_id = "'.$devId.'" order by id desc limit 1';
      $res = mysql_query($this->q);
      if(mysql_num_rows($res) > 0){
          $data = mysql_fetch_array($res);
          if($data['server_next'] == $this->anchor->Last){
              return true;
          }
      }
      
      return false;
  }
  
  function setElement(){
      $this->element = array(
          'cmdid' => 'CmdID',
          'anchor' => 'Anchor',
          'mode' => 'Mode',
          'data' => 'Data'
      );
  }
  
  function generateInit(){
      $this->xml->addChild($this->element['cmdid'], $this->cmd);
      $this->xml->addChild($this->element['mode'], $this->mode);
      $anchor = $this->xml->addChild($this->element['anchor']);
        $anchor->addChild('Last', $this->anchor->Last);
        $anchor->addChild('Next', $this->anchor->Next);
      return $this->xml;
  }
  
  function executeChange(){
      $this->jsondata = new JSONData($this->data);
      return $this->jsondata->jsonToQuery();
  }
  
  function generateSync(){
      
  }
  
}
