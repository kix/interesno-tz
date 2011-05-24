<?php
require_once 'libs/dbo_mysql.php';
require_once 'libs/xml_parser.php';

ini_set('display_errors', 'On');
ini_set('error_reporting', E_ALL);
define('DEBUG', FALSE);
define('APPROOT', dirname(__FILE__));

class Application {
  var $config = array(
      'db_host' => '127.0.0.1',
      'db_user' => 'tz1',
      'db_pass' => '',
      'db_base' => 'tz1',
  );
   
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
      
      $this->parser->parse($file_id);

     // header('Location: /'); // TODO: uploads should be AJAXy
    }  
  }
  /**
   * Gets XML nodes from the database. By default returns root element only,
   * controlled by $parent_id
   * 
   * TODO: Params are taken from request, this is foolish and should really be routed
   * @param integer $file_id ID of a file to get nodes for
   * @param integer $parent_id Parent node ID
   */
  function get_nodes(){
    $file_id = $_REQUEST['file_id'];
    $parent_id = isset($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : 0;
    $data = $this->dbo->get('elements', array('file'=>$file_id, 'parent'=>$parent_id));
    print json_encode($data);
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