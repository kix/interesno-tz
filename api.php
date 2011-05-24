<?php
require_once 'libs/dbo_mysql.php';
require_once 'libs/xml_parser.php';

ini_set('display_errors', 'On');
ini_set('error_reporting', E_ALL);
define('DEBUG', TRUE);

class Application {
  var $config = array(
      'db_host' => '127.0.0.1',
      'db_user' => 'tz1',
      'db_pass' => '',
      'db_base' => 'tz1'
  );
  
  /*
  var $db_host = '127.0.0.1';
  var $db_user = 'tz1';
  var $db_pass = '';
  var $db_base = 'tz1';
  */
  
  var $dbo;
  
  var $debug = array();
  
  /**
   * A quick in-class debugger
   * @param string $msg Message to record
   */
  function d($msg){
    $this->debug[] = array('object'=>'Application', 'message'=>$msg);
  }  
  
  function __construct() {
    $this->d('Construct');
    $this->dbo = new DBO_MySQL($this);
    $this->parser = new Parser($this);
  }
  
  function files_list(){
    $data = $this->dbo->get('files');
    $this->d('Data: '. json_encode($data));
    $ret = array('files'=>$data);
    print json_encode($ret);
  }
  
  function upload_file(){
    if (isset($_FILES['file'])){
      $file = $_FILES["file"]["tmp_name"];
      $sha1 = sha1_file($file);
      $filename = $sha1 . $_FILES["file"]["name"];
      move_uploaded_file($file, dirname(__FILE__)."/upload/".$filename);
      
      $file_id = $this->dbo->insert('files', array('name'=>$_FILES["file"]["name"], 'filename' => $filename));
      
      $this->parser->parse(dirname(__FILE__)."/upload/".$filename);

     // header('Location: /'); // TODO: uploads should be AJAXy
    }  
  }
  
  function index(){
    
  }
}

$app = new Application();
$action = $_REQUEST['action'];
$app->d('Action: '. $action);
if ($action) {
  $app->$action();
} else {
  $app->index();
}

if (DEBUG) {
  print "<ul>";
  foreach($app->debug as $msg) {
    print "<li><strong>".$msg['object'].":</strong> ".$msg['message']."</li>";
  }
  print "</ul>";
}