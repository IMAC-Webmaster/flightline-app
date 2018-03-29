$(document).ready(function(){
  
  // On page load: datatable
  var table_roundlist = $('#table_roundlist').dataTable({
    "ajax": "data_sqlite.php?job=get_rounds",
    "columns": [
      { "data": "class"},
      { "data": "type" },
      { "data": "roundnum",       "sClass": "integer" },
      { "data": "name" },
      { "data": "id",             "visible": false },
      { "data": "sequences",      "render": function ( data, type, row ) { return renderSequence(data); } },
      { "data": "phase",          "render": function ( data, type, row ) { return renderPhase(data); } },
      { "data": "status" },
      { "data": "functions",      "sClass": "functions" }
    ],
    "aoColumnDefs": [
      { "bSortable": false, "aTargets": [-1] }
    ],
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "oLanguage": {
      "oPaginate": {
        "sFirst":       " ",
        "sPrevious":    " ",
        "sNext":        " ",
        "sLast":        " "
      },
      "sLengthMenu":    "Records per page: _MENU_",
      "sInfo":          "Total of _TOTAL_ records (showing _START_ to _END_)",
      "sInfoFiltered":  "(filtered from _MAX_ total records)"
    }
  });
  
  // On page load: form validation
  jQuery.validator.setDefaults({
    success: 'valid',
    rules: {
      sequences: {
        required: true,
        min:      0,
        max:      2
      }
    },
    errorPlacement: function(error, element){
      error.insertBefore(element);
    },
    highlight: function(element){
      $(element).parent('.field_container').removeClass('valid').addClass('error');
    },
    unhighlight: function(element){
      $(element).parent('.field_container').addClass('valid').removeClass('error');
    }
  });
  var form_company = $('#form_company');
  form_company.validate();

  function renderPhase(phase) {
    switch (phase) {
        case "U":
            return "<i class=\"fa fa-square-o fa-1x fa-fw\"></i>Unflown";
        case "O":
            return "<i class=\"fa fa-plane fa-spin fa-1x fa-fw\"></i>Flying";
        case "P":
            return "<i class=\"fa fa-pause fa-1x fa-fw\"></i>Paused";
        case "D":
            return "<i class=\"fa fa-check-square-o fa-1x fa-fw\"></i>Completed";
    }
  }
  
  function renderSequence (sequence) {
    switch (sequence) {
        case 0:
            return "";
        case 1:
            return "Single";
        case 2:
            return "Double";
    }
  }
  // Show message
  function show_message(message_text, message_type){
    $('#message').html('<p>' + message_text + '</p>').attr('class', message_type);
    $('#message_container').show();
    if (typeof timeout_message !== 'undefined'){
      window.clearTimeout(timeout_message);
    }
    timeout_message = setTimeout(function(){
      hide_message();
    }, 8000);
  }
  // Hide message
  function hide_message(){
    $('#message').html('').attr('class', '');
    $('#message_container').hide();
  }

  // Show loading message
  function show_loading_message(){
    $('#loading_container').show();
  }
  // Hide loading message
  function hide_loading_message(){
    $('#loading_container').hide();
  }

  // Show lightbox
  function show_lightbox(){
    $('.lightbox_bg').show();
    $('.lightbox_container').show();
  }
  // Hide lightbox
  function hide_lightbox(){
    $('.lightbox_bg').hide();
    $('.lightbox_container').hide();
  }
  // Lightbox background
  $(document).on('click', '.lightbox_bg', function(){
    hide_lightbox();
  });
  // Lightbox close button
  $(document).on('click', '.lightbox_close', function(){
    hide_lightbox();
  });
  // Escape keyboard key
  $(document).keyup(function(e){
    if (e.keyCode == 27){
      hide_lightbox();
    }
  });
  
  // Hide iPad keyboard
  function hide_ipad_keyboard(){
    document.activeElement.blur();
    $('input').blur();
  }

  // Delete round - only if it has not yet beel flown.
  $(document).on('click', '.function_delete a', function(e){
    e.preventDefault();
    var round_class = $(this).data('class');
    var round_type = $(this).data('type');
    var round_num = $(this).data('roundnum');
    if (confirm("Are you sure you want to delete '" + round_type + "' round '" + round_num + "' in class '" + round_class + "' ?")){
      show_loading_message();
      var id      = $(this).data('id');
      var request = $.ajax({
        url:          'data_sqlite.php?job=delete_round&class=' + round_class + '&type=' + round_type + '&roundnum=' + round_num,
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.api().ajax.reload(function(){
            hide_loading_message();
            show_message("Round '" + round_type + "' number '" + round_num + "' in class '" + round_class + "' deleted successfully.", 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Delete request failed: ' + output.message, 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Delete request failed: ' + textStatus, 'error');
      });
    }
  });

});