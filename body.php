<?php

require('jsondata.php');

class Body {

    private $cmd;
    private $mode;
    private $anchor;
    private $jsondata;
    private $data;
    private $q;
    private $xml;
    private $element;

    function __construct($body) {
        $this->cmd = $body->CmdID;
        $this->mode = $body->Mode;
        $this->anchor = $body->Anchor;
        $this->data = $body->Data;
        $this->xml = simplexml_load_string('<SyncBody></SyncBody>');

        $this->setElement();
    }

    function getCmd() {
        return $this->cmd;
    }

    function getData() {
        return $this->data;
    }

    function setMode($mode) {
        $this->mode = $mode;
    }

    function validateAnchor($devId) {
        $this->q = 'SELECT * from sync_anchors where dev_id = "' . $devId . '" order by id desc limit 1';
        $res = mysql_query($this->q);
        if (mysql_num_rows($res) > 0) {
            $data = mysql_fetch_array($res);
            if ($data['server_next'] == $this->anchor->Last) {
                return true;
            }
        }

        return false;
    }

    function setElement() {
        $this->element = array(
            'cmdid' => 'CmdID',
            'anchor' => 'Anchor',
            'mode' => 'Mode',
            'data' => 'Data'
        );
    }    

    function executeChange() {
        $this->jsondata = new JSONData($this->data);
        return $this->jsondata->jsonToQuery();
    }
    
    function generateReply(){
        if($this->cmd == 1){
            return $this->generateInit();
        } else {
            return $this->generateSync();
        }
    }
    
    function generateInit() {
        $this->xml->addChild($this->element['cmdid'], $this->cmd);
        $this->xml->addChild($this->element['mode'], $this->mode);
        $anchor = $this->xml->addChild($this->element['anchor']);
        $anchor->addChild('Last', $this->anchor->Last);
        $anchor->addChild('Next', $this->anchor->Next);
        return $this->xml;
    }

    function generateSync() {
        $result = '';
        $this->xml->addChild($this->element['cmdid'], $this->cmd);
        $this->xml->addChild($this->element['mode'], $this->mode);
        $anchor = $this->xml->addChild($this->element['anchor']);
        $anchor->addChild('Last', $this->anchor->Last);
        $anchor->addChild('Next', $this->anchor->Next);
        
        if($this->mode == '400'){
            $this->jsondata->tableToJson('cl_user','SELECT * from cl_user');
            $this->jsondata->tableToJson('cl_cours','SELECT * from cl_cours');
            $this->jsondata->tableToJson('c_en_course_description','SELECT * from c_en_course_description');
            $this->jsondata->tableToJson('c_en_announcement','SELECT * from c_en_announcement');
            $this->jsondata->tableToJson('c_en_wrk_assignment','SELECT * from c_en_wrk_assignment');
            $this->jsondata->tableToJson('c_en_wrk_submission','SELECT * from c_en_wrk_submission');  
            $result = json_encode($this->jsondata->jsonData);
        } else {
          $this->jsondata->execQuery('SELECT * FROM `sync_change_logs` where 
                                      metadata not like "%from : '.$_SESSION['uuid'].'%" 
                                      and change_time > "'.$this->anchor->Last.'"');
          $result = $this->jsondata->logsToJson();          
        }
        
        // insert the anchor
        mysql_query("insert into sync_anchors values 
            (null,'".$_SESSION['uuid']."', '".$this->anchor->Last."', 0,'".$this->anchor->Next."', 0)");
        
        $this->xml->addChild($this->element['data'], $result);
        return $this->xml;
    }

}
