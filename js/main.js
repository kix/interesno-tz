var iddqd = "GOD MODE ON!";
var file_id;

/*
 * http://tz1.lc/api.php?action=get_nodes&file_id=38
 */

/**
 * Appends a node to a certain parent
 * @param node 
 */
function appendNodes(parent_id){
  var url = 'api.php?action=get_nodes&file_id='+file_id+'&parent_id='+parent_id;
  $.getJSON(url, function(data){
    console.log(data);
    $('#node-'+parent_id).append('<ul></ul>');
     if ($(data)) {
       $(data).each(function(i, node){
         $('#node-'+parent_id+' > ul').append('<li id="node-'+node.id+'"><a data-node-id="'+node.id+'" href="#node-'+node.id+'" class="node lazy closed">'+node.name+'</li>');
       })
     } else {
       console.log('no data'); // или проверить рез-те кол-во детей
     }
   });  
}

$(document).ready(function(){

  // Get a list of all uploaded files, just a shortcut for testing 
  $.getJSON('api.php?action=files_list', function(data){
   $(data.files).each(function(i, file){
     $('#files').append('<li id="file-'+file.id+'"><a data-file-id="'+file.id+'" class="file-link" href="#file-'+i+'">'+file.name+'</li>');
   })
  })
  
  // Click on a file shows its root node
  $('.file-link').live('click', function(){
    var id = $(this).attr('data-file-id');
    file_id = id;
    $('#the-content').html('').append('<ul id="file-view" data-file-id="'+id+'"></ul>');
    var url = 'api.php?action=get_nodes&file_id='+id;
    $.getJSON(url, function(data){
      $('#file-view').append('<li id="node-'+data.id+'"><a data-node-id="'+data.id+'" class="node lazy closed" href="#node-'+data.id+'">'+data.name+'</a>'+' ' +'</li>');
    });
  });
  
  // Click on a node shows its children...
  $('.node').live('click', function(){
    // ... requested lazily from the server ...
    if ($(this).hasClass('lazy')) {
      $(this).removeClass('lazy');
      appendNodes($(this).attr('data-node-id'));
    // ... or just toggled once the data is loaded ...
    } else {
      $(this).parent().children('ul').toggle();
    }
    $(this).toggleClass('open');
    $(this).toggleClass('closed');
  })  
  
});