<?php
class Parser {
  var $xml_parser;
  
  function __construct($app = false) {
    if ($app) {
      $this->app = $app;
    }

    $this->d('Constructing');
    
    $this->xml_parser = xml_parser_create();
    xml_set_element_handler($this->xml_parser, array(&$this, 'startEl'), array(&$this, 'endEl'));
  }
  
  function startEl($parser, $name, $attrs){
    $this->d('Element: '.$name);
    $this->d('Attrs: '.print_r($attrs, TRUE));
  }

  function endEl($parser, $name, $attrs){
    $this->d('Element: '.$name);
    $this->d('Attrs: '.print_r($attrs, TRUE));   
  }
  
  function d($msg){
    if ($this->app) {
      $this->app->debug[] = array('object'=>'XML Parser', 'message'=>$msg);
    }
  }
  
  function parse($file) {
    $handle = fopen($file, 'r');
    while ($data = fread($handle, 4096)) {
      $this->d($data);
    }
  }
}