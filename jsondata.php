<?php
//include_once 'db.php';
//$json = new JSONData();
//$json->execQuery('SELECT * FROM `sync_change_logs`'); 
//echo $json->logsToJson();
//echo $json->jsonToQuery();
//echo $json->tableToJson('c_EN_course_description');
class JSONData{
  private $res;
  private $data;
  public $json;
  public $jsonData = array('insert' => array(), 'update' => array(), 'delete' => array());
  public $cr = array("\r\n", "\n", "\r");
  
  function __construct($json = null){      
      $this->json = $json;
  }
  
  function execQuery($q){
    $this->res = mysql_query($q) or die(mysql_error());
    //echo $q;
  }  
  
  function checkId($jsonRec){
      $sql = 'select '.$jsonRec['cols'][0].' from `'.$jsonRec['name'].'` where '.
              $jsonRec['cols'][0].' = "'.$jsonRec['vals'][0].'"';
      $this->execQuery($sql);
      return mysql_num_rows($this->res);
  }
  
  function generateId($jsonRec){
      $sql = 'select max('.$jsonRec['cols'][0].') from `'.$jsonRec['name'].'`';
      $this->execQuery($sql);
      $result = mysql_fetch_row($this->res);
      return intval($result[0])+1;
  }
  
  function jsonToQuery(){    
    $arrData = json_decode($this->json,true);
    //print_r($arrData);
    $sql = null;
    foreach($arrData['insert'] as $insData){
      $oldId = $insData['vals'][0];
      if($this->checkId($insData) > 0){
          $insData['vals'][0] = $this->generateId($insData);
      }
      $sql .= ' INSERT INTO '.$insData['name'].'(';
      foreach($insData['cols'] as $cols){
        $sql .= $cols.',';
      } $sql = substr($sql,0,-1).') values (';
      foreach($insData['vals'] as $vals){
        $sql .= '\''.$vals.'\',';
      } $sql = substr($sql,0,-1).'); ';
      mysql_query($sql) or die(mysql_error());
      // query to insert map 
      $sql = ' insert into sync_maps values '.
              '(null,"'.$_SESSION['uuid'].'","'.$insData['name'].'","'.$oldId.'","'.$insData['vals'][0].'");';
      mysql_query($sql) or die(mysql_error());
      mysql_query('call update_logs("from : '.$_SESSION['uuid'].'"); ') or die(mysql_error());
    } 
    
    foreach($arrData['update'] as $updData){              
      $i = 0;
      $sql .= 'UPDATE '.$updData['name'].' SET ';      
      foreach($updData['cols'] as $cols){
        $sql .= $cols.' = "'.$updData['vals'][$i].'",'; $i++;
      } $sql = substr($sql,0,-1).' WHERE '.
              $updData['cols'][0].' = "'.$this->findMapId($updData).'"; 
              call update_logs("from : '.$_SESSION['uuid'].'"); ';
    }
    
    foreach($arrData['delete'] as $delData){
      $sql .= 'DELETE FROM '.$delData['name'].' WHERE '.
              $delData['cols'][0].' = "'.$this->findMapId($delData).'"; 
              call update_logs("from : '.$_SESSION['uuid'].'"); ';
    }
    
    return $sql;
  }
  
  function findMapId($jsonRec){
      $sql = 'select guid from sync_maps where dev_id = "'.$_SESSION['uuid'].'" 
              and table_name = "'.$jsonRec['name'].'" and luid = "'.$jsonRec['cond'][0].'"';
      $this->execQuery($sql);
      if(mysql_numrows($this->res) > 0){
          $data = mysql_fetch_array($this->res);
          return $data[0];
      } else {
          return $jsonRec['vals'][0];
      }
  }
  
  function tableToJson($tableName, $query){
    //$this->json = array('insert' => array(), 'update' => array(), 'delete' => array());
    $this->res  = mysql_query($query) or die(mysql_error());
    while($this->data = mysql_fetch_assoc($this->res)){
      $cols = $vals = array();
      //if($tableName == 'c_en_course_description') print_r($this->data);
      foreach($this->data as $key => $val){        
        $cols[] = $key;
        $vals[] = preg_replace('/[^(\x20-\x7F)]*/','',$val);
      }
      $this->jsonData['insert'][] = array('name'=>$tableName, 'cols'=> $cols, 'vals' => $vals);
    }
    //print_r($this->jsonData);
    return json_encode($this->jsonData);
  }
  
  function logsToJson(){
    $arrJson = array('insert' => array(), 'update' => array(), 'delete' => array());
    if($this->res == null) return 'yes';
    while($this->data = mysql_fetch_array($this->res)){
      if($this->data['action'] == 'I'){
        $changed = json_decode(str_replace($this->cr,"",$this->data['changed_val']));
        $arrJson['insert'][] = array('name'=>$this->data['table_name'],
            'cols'=> $changed->cols, 
            'vals' => $changed->vals);
      } else if($this->data['action'] == 'U'){
          $changed = json_decode(str_replace($this->cr,"",$this->data['changed_val']));
          $arrJson['update'][] = array('name'=>$this->data['table_name'],
            'cond' => $this->data['table_id'].'="'.$this->data['row_id'].'"',
            'cols' => $changed->cols, 
            'vals' => $changed->vals);
      } else { // $data['action'] == 'D'
        $arrJson['delete'][] = array('name'=>$this->data['table_name'],
            'cond' => $this->data['table_id'].'='.$this->data['row_id']);
      }
    }
    return json_encode($arrJson);
  }
  
  function delAllMapId(){
      $sql = 'delete from sync_maps where dev_id = "'.$_SESSION['uuid'].'"';
      $this->execQuery($sql);
  }
  
  function getNewUpdates(){
      $sql = 'select * from sync_change_logs where change_time > "'.$anchorTime.'" 
                and metadata not like "%from:'.$_SESSION['uuid'].'%"';
      $this->execQuery($sql);
  }
}
