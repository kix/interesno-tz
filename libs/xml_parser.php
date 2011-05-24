<?php
class Parser {
  var $xml_parser;
  var $el_id;
  var $depth;
  var $file_id;
  var $route = array();
  
  function __construct($app = false) {
    $this->el_id = 0;
    
    if ($app) {
      $this->app = $app;
    }

    $this->d('Constructing');
    $this->depth = 0;
    $this->xml_parser = xml_parser_create();
    xml_set_element_handler($this->xml_parser, array(&$this, 'startEl'), array(&$this, 'endEl'));
  }
  
  function startEl($parser, $name, $attrs){
    $this->depth++;
    $parent = $this->route[$this->depth - 1];
    $el_id = $this->app->dbo->insert('elements', array('file'=>$this->file_id,
                                                       'parent'=> $parent,
                                                       'name'=>$name, 
                                                       'attrs'=>json_encode($attrs)));
    array_push($this->route, $el_id);
    $this->d('Route: '. implode($this->route, ' -> '));
  }

  function endEl($parser, $name){
    //$this->d('Element: '.$name);
    array_pop($this->route);
    $this->depth--;
  }
  
  function d($msg){
    if ($this->app) {
      $this->app->debug[] = array('object'=>'XML Parser', 'message'=>$msg);
    }
  }
  
  function parse($file_id) {
    $this->file_id = $file_id;
    $file = $this->app->dbo->get('files', array('id'=>$file_id));
    
    array_push($this->route, 0);
    
  //  print_r($file);
    
    $handle = fopen(APPROOT."/upload/".$file['filename'], 'r');
    while ($data = fread($handle, 4096)) {
      if (!xml_parse($this->xml_parser, $data, feof($handle))) {
        $this->d('Malformed XML'); // TODO: Warn user
      }
    }
  }
}
/*
  function startEl($parser, $name, $attrs){
    //print_r($name);
    print 'startel';
    $this->d('startEl');
    $this->d('Element: '.$name);
    $this->d('Attrs: '.print_r($attrs, TRUE));
  }

  function endEl($parser, $name, $attrs){
    $this->d('Element: '.$name);
    $this->d('Attrs: '.print_r($attrs, TRUE));   
  }*/