<?php
/**
 * MySQL database access class
 * @author Stepan Anchugov <kix@kixlive.ru>
 */
class DBO_MySQL {
  
  protected $_link;
  
  function d($msg){
    if ($this->app) {
      $this->app->debug[] = array('object'=>'DBO', 'message'=>$msg);
    }
  }

  /**
   * Connects to a database
   * @return boolean True on success
   */
  public function __construct($app = false){
    if ($app) {
      $this->app = $app;
    }
    return $this->connect();
  }

  /**
   * Connects to a database using pre-defined constants from config
   * TODO: scary code
   * @return boolean True on successful connection
   */
  function connect(){
    if ($this->app) {
      $this->d('Starting DBO from app config');
      
      $this->_link = mysql_connect($this->app->config['db_host'], 
                                   $this->app->config['db_user'], 
                                   $this->app->config['db_pass']);
      if (!$this->_link) {
        $this->d('Could not connect to DB');
      } else {
        $this->d('Connected to DB '.$this->app->config['db_user'].'@'.$this->app->config['db_host']);
      }
      if (mysql_select_db($this->app->config['db_base'], $this->_link)) {
        $this->d('Connected and selected DB');
        return True;
      } else {
        $this->d('Could not select DB: '.$this->app->config['db_base']);
        return False;        
      }
    } else { // TODO: dirty code
      $this->_link = mysql_connect(HOST, USER, PASS);
      if (mysql_select_db(BASE)){
        $this->d('Connected and selected DB');
        return True;
      } else {
        $this->d('Could not select DB!');
        return False;
      }
     }
  }
  
  /**
   * Runs a query, returns insert ID on insert or data on select
   * @param string $query
   * @return int|resource Insert ID or MySQL resource
   */
  public function query($query){
    if (!$this->_link) {
      $this->connect();
    }
    if ( substr($query,0,3) == 'INSE'
      || substr($query,0,3) == 'UPDA') {
      $this->d('Insert/update query:' . $query);
      mysql_query($query, $this->_link);
      return mysql_insert_id();
      }
    $this->d('Select query: ' . $query);
    $data = mysql_query($query, $this->_link);

    return $data;
  }
  
  /**
   * Fetches MySQL resource into an array
   * @param resource $data
   * @return array 
   */ 
  private function object($data){
    $ret = array();
    if ($data){
      $this->d('Got data');
      while ($row = mysql_fetch_assoc($data)){
        $ret[] = $row;
      }
      return $ret;
    } else {
      // throw ...
      $this->d('No data!');
    }
  }
  
  /**
   * Inserts a database row
   * @param string $tableName
   * @param array $fields
   * @return int|resource
   */
  public function insert($tableName, $fields){
    if (is_array($fields)){
      $c = count($fields);
      $qfields = '';
      $qvalues = '';
      $i = 1;
      foreach ($fields as $field=>$value){
        $qfields .= '`'.$field.'`';
        $qvalues .= "'".$value."'";
        if ($i < $c) {
          $qfields .= ','; $qvalues .= ',';
        }
        $i++;
      }
      $query = "INSERT INTO $tableName ($qfields) VALUES ($qvalues)";
      return $this->query($query);
    } else {
      return False;
    }
  }
  
  /**
   * Updates a database row
   * @param string $tableName
   * @param array $fields
   * @return int|resource 
   */
  private function update($tableName, $fields){
    if (is_array($fields)){
      $qfields = '';
      $qvalues = '';
      $i = 2;
      $c = count($fields);
      foreach ($fields as $field=>$value){
        if ($field != 'id') {
          $qfields .= "`$field` = '$value'";
          if ($i < $c) {$qfields .= ',';}
          $i++;
        } else {
          $qwhere = '`id` = '.$value;
        }        
      }
      $query = "UPDATE $tableName SET $qfields WHERE $qwhere";
      return $this->query($query);      
    }  
  }
  
  /**
   * Inserts or updates a database row depending on id field
   * 
   * YUP, THIS IS STUPID
   * 
   * @param type $tableName
   * @param type $fields
   * @return type 
   */
  public function put($tableName, $fields){
    if (array_key_exists('id', $fields)){
      $this->update($tableName, $fields);
      return $fields['id'];
    } else {
      return $this->insert($tableName, $fields);
    }
  }
  
  /**
   * Deprecated
   * 
   * @param type $tableName
   * @param type $fields
   * @param type $count
   * @return type 
   */
  private function _select($tableName, $fields = False, $count = False){
    $query = '';
    if ($count){
      $query = "SELECT COUNT(*) FROM $tableName";
    } else {
      $query = "SELECT * FROM $tableName";
    }
    
    if (is_array($fields)){
      $query .= " WHERE ";
      $c = count($fields);
      $i = 0;
      foreach ($fields as $field=>$value){
        $query .= "`$field` = $value";
        if ($i != $c-1) {$query .= " AND ";}
      }
    }  
    $this->d('_select: '. $query);
    
    $data = $this->object($this->query($query));
    $this->d('Data: '.json_encode($data));
    return $data;    
  }
  
  public function get($tableName, $fields = False){
    $ret = $this->_select($tableName, $fields, False);
    if (count($ret) > 1) {
      return $ret;
    } else {
      return $ret[0];
    }
  }
  
  public function drop($tableName, $fields){
    $qfields = '';
    $qwhere = '';
    $i = 1;
    $c = count($fields);
    foreach($fields as $field=>$value)  {
      if ($field != 'id') {
        $qwhere .= "`$field` = '$value'";
        if ($i < $c) {$qwhere .= ' AND ';}
        $i++;
      } else {
        $qwhere = '`id` = '.$value;
      }
    }
    $q = "DELETE FROM $tableName WHERE $qwhere";
    return $this->query($q);
  }
  
  public function count($tableName, $fields = False){
    $ret = $this->_select($tableName, $fields, True);
    return $ret['COUNT(*)'];
  }
  
  public function join($tableObj, $tableAdd, $id=-1){
	  /*
	  tableObj - подчиненная таблица
	  tableAdd - основная
	  */
	  $tableLink = $tableObj.'_'.$tableAdd;
	  $thisName = trim($tableAdd, 's');
	  $modelName = trim($tableObj, 's');
	  $query = "SELECT obj.*, lnk.".$modelName."_id "
            ."FROM $tableLink lnk JOIN $tableAdd obj "
              ."ON (obj.id = lnk.".$thisName."_id)";
	  if ($id >= 0){
	    $query .= " WHERE $modelName"."_id = $id";
	  }
	  return $this->object($this->query($query));
	}
  
  public function describe($tableName){
    $query = "DESCRIBE $tableName";
    $fields = $this->object($this->query($query));
    $ret = array();
    foreach ($fields as $field) {
      if ($field['Type'] == 'date') {$ret[$field['Field']]['Type']='date';}
      if (strstr($field['Type'],'varchar')) {$ret[$field['Field']]['Type']='text';}
      if (strstr($field['Type'],'text')) {$ret[$field['Field']]['Type']='longtext';}
      if (strstr($field['Field'],'_id')) {$ret[$field['Field']]['Type']='relation';}
      if (strstr($field['Type'],'int')) {$ret[$field['Field']]['Type']='int';}
      if (strstr($field['Field'],'id')) {$ret[$field['Field']]['Type']='id';}
      if (strstr($field['Null'], 'YES')) {
        $ret[$field['Field']]['Null'] = True;
      }
    }
    return $ret;
  }
}