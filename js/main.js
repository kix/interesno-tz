var iddqd = "GOD MODE ON!";

$(document).ready(function(){
  $.getJSON('api.php?action=files_list', function(data){
   $(data.files).each(function(i, file){
     $('#files').append('<li id="file-'+file.id+'"><a class="file-link" href="#file-'+i+'">'+file.name+'</li>');
     console.log('<li id="file-'+file.id+'>'+file.name+'</li>');
   })
  })
  
  $('.file-link').live('click', function(){
    $('#the-content').html('asdsad');
  });
});