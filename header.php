<?php

require_once 'db.php';

class Header {

  private $sessionid;
  private $msgid;
  private $target;
  private $source;
  private $cred;
  private $q;
  private $element;

  function __construct($header) {
    $this->source = $_SERVER['DOCUMENT_ROOT'];
    $this->setSessionId($header->SessionID);
    $this->setMsgId($header->MsgID);
    $this->setTarget($header->Target);
    $this->setSource($header->Source);
    $this->setCred($header->Cred);
    $this->cred->valid = false;

    $this->setElement();
  }

  function setSessionId($sessionid) {
    $this->sessionid = $sessionid;
    $_SESSION['sessionid'] = $sessionid;
  }

  function setMsgId($msgid) {
    $this->msgid = $msgid;
  }

  function setTarget($target) {
    $this->target = $target;
  }

  function setSource($source) {
    $this->source = json_decode($source->LocURI);
    $_SESSION['uuid'] = $this->source->uuid;
  }

  function getSource() {
    return $this->source;
  }

  function setCred($cred) {
    $this->cred = json_decode($cred);
  }

  function validateCred() {
    $this->q = "select user_id,password from cl_user where username = '" . $this->cred->username . "'";
    $res = mysql_query($this->q) or die(mysql_error());
    if (mysql_num_rows($res) > 0) {
      $data = mysql_fetch_row($res);
      if ($data[1] == $this->cred->password) {
        $this->cred->valid = true;
        $this->cred->userId = $data[0];
        return true;
      }
      return false;
    }
    return false;
  }

  function validateURI() {
    if ($this->target->LocURI == 'http://sync.claroline.com') {
      $this->cred->URI = true;
    } else {
      $this->cred->URI = false;
      return false;
    }

    $device = $this->source;

    $this->q = "Select sync_devices.id,password,userid,uuid from sync_devices 
          join cl_user on (cl_user.user_id = sync_devices.userid) where 
          userid = '" . $this->cred->userId . "' and uuid = '" . $device->uuid . "'";
    //echo $this->q;
    $res = mysql_query($this->q) or die(mysql_error());
    if (mysql_num_rows($res) > 0) {
      $data = mysql_fetch_row($res);
      if ($data[1] == $this->cred->password) {
        $this->cred->valid = true;
        $this->cred->userId = $data[0];
      }      
      $this->source->deviceId = $data[0];
    } else {
      $this->q = "insert into sync_devices values (null,
                        '" . $this->cred->userId . "',
                        '" . $device->uuid . "','" . $device->platform . "',
                        '" . $device->model . "','" . $device->os_version . "',
                        '" . $device->utc_time . "')";
      mysql_query($this->q) or die(mysql_error());
      $this->source->deviceId = mysql_insert_id();
    }

    $_SESSION['uuid'] = $this->source->deviceId;

    return true;
  }

  function setElement() {
    $this->element = array(
        'sessionId' => 'SessionID',
        'messageId' => 'MsgID',
        'target' => 'Target',
        'source' => 'Source',
        'credential' => 'Cred',
        'location' => 'LocURI'
    );
  }

  function generateHeader($type = 1) { // 1 = success; 2 = invaliduser; 3 = invalidlocation
    $header = '<?xml version="1.0"?><SyncHdr></SyncHdr>';
    $xml = simplexml_load_string($header);

    $xml->addChild($this->element['sessionId'], $this->sessionid);
    $xml->addChild($this->element['messageId'], $this->msgid);
    $target = $xml->addChild($this->element['target']);
    $target->addChild($this->element['location'], json_encode($this->source));
    $source = $xml->addChild($this->element['source']);
    if ($type == 1) {
      $source->addChild($this->element['location'], $this->target->LocURI);
    } else if ($type == 3) {
      $source->addChild($this->element['location'], 'invalid');
    }
    $cred = $xml->addChild($this->element['credential'], json_encode($this->cred));
//      if($type == 1){
//        $cred->addChild('uservalid',  'valid');
//      } else if($type == 2){
//        $cred->addChild('uservalid',  'invalid');
//      }

    return $xml;
  }

}
