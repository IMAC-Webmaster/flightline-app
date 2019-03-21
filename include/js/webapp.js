$(document).ready(function() {
  var classlist;
  var schedulelist;
  var nextroundNums;
  var rounddata;
  var buttonRefresh;

  // On page load: datatable
  var table_pilotlist = null;
  var table_roundlist = $('#table_roundlist').DataTable({
    // Oldway: "ajax": "data.php?job=get_rounds",
    "ajax": "/api/1/rounds/",
    "processing": false,
    "columns": [
      { "data": "roundId"},
      { "data": "imacClass"},
      { "data": "imacType" },
      { "data": "roundNum",       "sClass": "integer" },
      { "data": "description" },
      { "data": "schedId",        "visible": false },
      { "data": "sequences",      "render": function ( data, type, row ) { return renderSequence(data); } },
      { "data": "phase",          "render": function ( data, type, row ) { return renderPhase(data); } },
      { "data": "status" },
      { "data": "functions",      "sClass": "functions" }
    ],
    "columnDefs": [
      { targets: '_all', "className": 'details-control' },
      { targets: [0], visible: false  },
      { targets: [-1], "orderable": false }
    ],
    "lengthMenu": [[100, -1], [100, "All"]],
    "oLanguage": {
      "oPaginate": {
        "sFirst":       "<",
        "sPrevious":    "<",
        "sNext":        ">",
        "sLast":        ">"
      },
      "sEmptyTable": "There are no rounds defined.   Click 'Add Round' to add a round.",
      "sLoadingRecords": "Loading...",
      "sLengthMenu":    "Records per page: _MENU_",
      "sInfo":          "Total of _TOTAL_ records (showing _START_ to _END_)",
      "sInfoFiltered":  "(filtered from _MAX_ total records)"
    }
  });
  
  // On page load: form validation
  jQuery.validator.setDefaults({
    success: 'valid',
    rules: {
      imacClass:     { excluded: true },
      schedule:       { excluded: true },
      imacType:      { excluded: true },
      sequences:      { excluded: true }
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
  var form_round = $('#form_round');
  //form_round.validate();

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
    }, 10000);
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

  function clearForm() {
    removeOptions(document.getElementById("schedule"));
    $("#imacType").val("Known");
    $("#sequences").val("1");
    $("#roundNum").val("");
    $("#imacClass").show();
    $("#sequences").show();
    $("#hidden_imacClass").hide();
    $("#hidden_sequences").hide();
  }

  function fillForm() {
    // Edit mode...   Fill in the form.
    if (typeof nextroundNums!== 'undefined' ) {
        // The round we are editing needs to not increment...
        for (var rnd in nextroundNums) {
            if (nextroundNums[rnd].imacType === rounddata.imacType && nextroundNums[rnd].imacClass === rounddata.imacClass) {
                nextroundNums[rnd].nextroundNum = rounddata.roundNum;
            }
        }
    }
    if (rounddata.imacType !== "Freestyle") {
        $('#imacClass').val(rounddata.imacClass);
        $("#imacClass").show();
        $("#sequences").show();
        $("#hidden_imacClass").hide();
        $("#hidden_sequences").hide();
        if (rounddata.imacType === "Unknown") {
            $("#sequences").hide();
            $("#hidden_sequences").show();
        }
    } else {
        $("#imacClass").hide();
        $("#sequences").hide();
        $("#hidden_imacClass").show();
        $("#hidden_sequences").show();
    }

    $('#imacType').val(rounddata.imacType);
    fillSchedules($('#imacClass').val(), $('#imacType').val());
    $('#schedule').val(rounddata.schedId);
    $('#roundNum').val(rounddata.roundNum);
    $('#sequences').val(rounddata.sequences);
  }
  // Show lightbox
  function show_lightbox(){
    $('.lightbox_bg').show();
    $('.lightbox_container').show();
    if ($('#form_round button').text() === 'Edit round') {
        fillForm();
    } else {
        rounddata = "";
        clearForm();
    }
  }
  // Hide lightbox
  function hide_lightbox(){
    $('.lightbox_bg').hide();
    $('.lightbox_container').hide();
  }

  // Show lightbox
  function show_roundbox(data){
    var roundId = data.roundId;
    $('.roundbox_bg').show();
    $('.roundbox_container').show();
    $('#class-details').text(data.imacClass);
    $('#roundsched-details').text(data.description);
    if (data.sequences === 2) {
        $('#roundtype-details').text(data.imacType + " Double");
    } else if(data.imacType === 'Known') {
        $('#roundtype-details').text(data.imacType + " Single");        
    } else {
        $('#roundtype-details').text(data.imacType);        
    }
    $('#roundnum-details').text(data.roundNum); 
    
    table_pilotlist = $('#table_pilotlist').DataTable({
      // OldWay: "ajax": "data.php?job=get_round_pilots&roundId=" + data.roundId + "&imacType=" + data.imacType,
      "ajax": "api/1/rounds/" + data.roundId + "/pilotflights",
      "columns": [
        { "data": "pilotId"},
        { "data": "fullName"},
        { "data": "noteFlightId"},
        { "data": "functions",      "sClass": "functions" },
        { "data": "noteHint"},
        { "data": "compId"}
      ],
      "columnDefs": [
        { targets: '_all', "className": 'details-control' },
        { targets: [0, 5], visible: false  },
        { targets: [-2], "orderable": false }
      ],
      "lengthMenu": [[50, 100, -1], [50, 100, "All"]],
      "initComplete": function(settings, json) {
        fillNextFlight(data.roundId, null, null);
      },
      "oLanguage": {
        "oPaginate": {
          "sFirst":       "<",
          "sPrevious":    "<",
          "sNext":        ">",
          "sLast":        ">"
        },
        "sLengthMenu":    "Records per page: _MENU_",
        "sInfo":          "Total of _TOTAL_ records (showing _START_ to _END_)",
        "sInfoFiltered":  "(filtered from _MAX_ total records)"
      }
    });
    adjustNextFlightButtons(roundId);
    buttonRefresh = setInterval(function() {
        adjustNextFlightButtons(roundId);
    }, 5000);
  }
  // Hide lightbox
  function hide_roundbox(){
    clearInterval(buttonRefresh);    
    $('.roundbox_bg').hide();
    $('.roundbox_container').hide();
    if ( $.fn.dataTable.isDataTable( table_pilotlist ) ) {
        table_pilotlist.destroy();
    }
  }
  
  function validateForm() {
    var form_valid = true;
    if ($('#imacType').val() === "Freestyle" || $('#imacType').val() === "Unknown") {
        if ($('#sequences').val() !== 1) $('#sequences').val(1);
    }

    if ($('#roundNum').val() === "") {
        $('#roundNum').parent('.field_container').addClass('error');
        $('#roundNum-error').text("A valid round number must be chosen.").show();
        form_valid = false;
    }
      
    if ($('#imacType').val() !== "Freestyle" && $('#imacClass').val() === "" ) {
        $('#imacClass').parent('.field_container').addClass('error');
        $('#imacClass-error').text("Please choose a imacClass.").show();
        form_valid = false;
    }

    if ($('#schedule').val() === "") {
        $('#schedule').parent('.field_container').addClass('error');
        $('#schedule-error').text("Please choose a schedule.").show();
        form_valid = false;
    }
    
    return form_valid;
  }

  function fillSchedules(imacClass, imacType) {
    var schedsel = document.getElementById("schedule");
    removeOptions(schedsel);
    if (imacType === "Freestyle") imacClass = "Freestyle";
    for (var sched in schedulelist) {
        if (schedulelist[sched].imacClass === imacClass  && schedulelist[sched].imacType === imacType) {
            var opt = document.createElement("option");
            opt.text = schedulelist[sched].description;
            opt.value = schedulelist[sched].schedId;
            schedsel.add(opt);
        }       
    }
  }

  function setNextRound(imacClass, imacType) {
    var blFoundNext = false;
    for (var rnd in nextroundNums) {
        if (nextroundNums[rnd].imacType === imacType  && (nextroundNums[rnd].imacClass === imacClass || imacType === "Freestyle") ){
            $("#roundNum").val(nextroundNums[rnd].nextroundNum);
            blFoundNext = true;
        }       
    }
    if (!blFoundNext) {
        $("#roundNum").val(1);
    }
  }

  // Roundbox background
  $(document).on('click', '.roundbox_bg', function(){
    hide_roundbox();
  });
  // Lightbox close button
  $(document).on('click', '.roundbox_close', function(){
    hide_roundbox();
  });
  
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
    if (e.keyCode === 27){
      hide_lightbox();
      hide_roundbox();
    }
  });

  // Hide iPad keyboard
  function hide_ipad_keyboard(){
    document.activeElement.blur();
    $('input').blur();
  }

  // Add round button
  $(document).on('click', '#add_round', function(e) {
    e.preventDefault();

    // Get next round details..
    show_loading_message();
    var blGotSchedules = false;
    var blGotRounds = false;    
    
    // First, get the next round numbers.
    var next_round_request = $.ajax({
      // Oldway: url:          'data.php?job=get_nextrnd_ids',
      url:          '/api/1/rounds/nextids',
      cache:        false,
      dataType:     'json',
      contentType:  'application/json; charset=utf-8',
      type:         'get'
    });
    
    next_round_request.done(function(output){
      if (output.result === 'success'){
        $('.lightbox_content h2').text('Add Round');
        $('#form_round button').text('Add Round');
        $('#form_round').attr('class', 'form add');
        $('#form_round .field_container label.error').hide();
        $('#form_round .field_container').removeClass('valid').removeClass('error');
        nextroundNums = output.data;
        blGotRounds = true;
        if (blGotSchedules === true && blGotRounds === true) {
          hide_loading_message();
          show_lightbox();
        }
      } else {
        hide_loading_message();
        show_message('Could not get next round: ' + output.message, 'error');
      }
    });

    next_round_request.fail(function(jqXHR, textStatus){
      hide_loading_message();
      show_message('Could not get next round: ' + textStatus, 'error');
    });

    // Now get the schedules.
    var sched_request = $.ajax({
      //Oldway: url:          'data.php?job=get_schedlist',
      url:          'api/1/schedules',
      cache:        false,
      dataType:     'json',
      contentType:  'application/json; charset=utf-8',
      type:         'get'
    });
    
    sched_request.done(function(output){
      if (output.result === 'success'){
        $('.lightbox_content h2').text('Add Round');
        $('#form_round button').text('Add Round');
        $('#form_round').attr('class', 'form add');
        $('#form_round .field_container label.error').hide();
        $('#form_round .field_container').removeClass('valid').removeClass('error');
        schedulelist = output.data;
        blGotSchedules = true;
        if (blGotSchedules === true && blGotRounds === true) {
          hide_loading_message();
          show_lightbox();
        }
      } else {
        hide_loading_message();
        show_message('Could not get schedule list: ' + output.message, 'error');
      }
    });

    sched_request.fail(function(jqXHR, textStatus){
      hide_loading_message();
      show_message('Could not get schedule list: ' + textStatus, 'error');
    });
  });

  // Add round submit form
  $(document).on('submit', '#form_round.add', function(e) {
    e.preventDefault();


    if (validateForm()) {
      // Send company information to database
      hide_ipad_keyboard();
      hide_lightbox();
      show_loading_message();
      var formObject = helpers.getFormData($('#form_round'));

      var request   = $.ajax({
        //Oldway: url:          'data.php?job=add_round',
        url:          'api/1/rounds',
        cache:        false,
        data:         JSON.stringify(formObject),
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'post'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.ajax.reload(function(){
            hide_loading_message();
            var round_class = $('#imacClass').val();
            var round_type =  $('#imacType').val();
            var round_num =   $('#roundNum').val();
            show_message("'" + round_type + "' round '" + round_num + "' in class '" + round_class + "' was added successfully.", 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Add request failed: ' + output.message, 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Add request failed: ' + textStatus, 'error');
      });
    }
  });

  // Click on a table row.
  $('#table_roundlist tbody').on('click', 'td.details-control', function () {
      var tr = $(this).closest('tr');
      var row = table_roundlist.row( tr );

      show_roundbox(row.data()); 
  });

  // Edit round button
  $(document).on('click', '.function_edit a', function(e){
    e.preventDefault();
    // Get company information from database
    show_loading_message();
    var round_class    = $(this).data('imacclass');
    var round_type     = $(this).data('imactype');
    var round_num      = $(this).data('roundnum');
    var round_schedId  = $(this).data('schedid');
    var blGotSchedules = false;
    var blGotRounds    = false;
    var blGotRoundData = false;

    
    // First, get the next round numbers.
    var next_round_request = $.ajax({
      // Oldway: url:          'data.php?job=get_nextrnd_ids',
      url:          '/api/1/rounds/nextids',
      cache:        false,
      dataType:     'json',
      contentType:  'application/json; charset=utf-8',
      type:         'get'
    });
    
    next_round_request.done(function(output){
      if (output.result === 'success'){

        nextroundNums = output.data;
        blGotRounds = true;
        if (blGotSchedules === true && blGotRounds === true && blGotRoundData === true) {
          hide_loading_message();
          show_lightbox();
        }
      } else {
        hide_loading_message();
        show_message('Could not get next round: ' + output.message, 'error');
      }
    });

    next_round_request.fail(function(jqXHR, textStatus){
      hide_loading_message();
      show_message('Could not get next round: ' + textStatus, 'error');
    });

    // Now get the schedules.
    var sched_request = $.ajax({
      //Oldway: url:          'data.php?job=get_schedlist',
      url:          'api/1/schedules',
      cache:        false,
      dataType:     'json',
      contentType:  'application/json; charset=utf-8',
      type:         'get'
    });
    
    sched_request.done(function(output){
      if (output.result === 'success'){

        schedulelist = output.data;
        blGotSchedules = true;
        if (blGotSchedules === true && blGotRounds === true && blGotRoundData === true) {
          hide_loading_message();
          show_lightbox();
        }
      } else {
        hide_loading_message();
        show_message('Could not get schedule list: ' + output.message, 'error');
      }
    });

    sched_request.fail(function(jqXHR, textStatus){
      hide_loading_message();
      show_message('Could not get schedule list: ' + textStatus, 'error');
    });
    
    var round_request = $.ajax({
      //Oldway: url:          'data.php?job=get_round',
      url:          'api/1/rounds/' + round_class + '/' + round_type + '/' + round_num,
      cache:        false,
      dataType:     'json',
      contentType:  'application/json; charset=utf-8',
      type:         'get'
    });
    round_request.done(function(output){
      if (output.result === 'success'){
        $('.lightbox_content h2').text('Edit round');
        $('#form_round button').text('Edit round');
        $('#form_round').attr('class', 'form edit');
        $('#form_round').attr('data-imacClass', round_class);
        $('#form_round').attr('data-imacType', round_type);
        $('#form_round').attr('data-roundNum', round_num);
        $('#form_round').attr('data-schedid', round_schedId);
        $('#form_round .field_container label.error').hide();
        $('#form_round .field_container').removeClass('valid').removeClass('error');

        blGotRoundData = true;
        rounddata = output.data;
        if (blGotSchedules === true && blGotRounds === true && blGotRoundData === true) {
          hide_loading_message();
          show_lightbox();
        }
      } else {
        hide_loading_message();
        show_message('Information request failed: ' + output.message, 'error');
      }
    });
    round_request.fail(function(jqXHR, textStatus){
      hide_loading_message();
      show_message('Information request failed: ' + textStatus, 'error');
    });
  }); // Edit round.
  
  // Edit round submit form
  $(document).on('submit', '#form_round.edit', function(e) {
    e.preventDefault();

    if (validateForm()){
      // Send round information to database
      hide_ipad_keyboard();
      hide_lightbox();
      show_loading_message();
      var round_class    = $('#form_round').attr('data-imacClass');
      var round_type     = $('#form_round').attr('data-imacType');
      var round_num      = $('#form_round').attr('data-roundNum');
      var form_data = $('#form_round').serialize();
      var request   = $.ajax({
        url:          'data.php?job=edit_round&prevclass=' + round_class + '&prevtype=' + round_type + '&prevroundNum=' + round_num,
        cache:        false,
        data:         form_data,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.ajax.reload(function(){
            hide_loading_message();
            show_message("Round '" + round_type + "' number '" + round_num + "' in class '" + round_class + "' edited successfully." + output.message, 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Edit request failed: ' + output.message, 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Edit request failed: ' + textStatus, 'error');
      });
    }
  }); // Edit round submit.

  // Delete round - only if it has not yet beel flown.
  $(document).on('click', '.function_delete a', function(e){
    e.preventDefault();
    var round_class = $(this).data('imacclass');
    var round_type  = $(this).data('imactype');
    var round_num   = $(this).data('roundnum');
    if (confirm("Are you sure you want to delete '" + round_type + "' round '" + round_num + "' in class '" + round_class + "' ?")){
      show_loading_message();
      var request = $.ajax({
        url:          'data.php?job=delete_round&imacClass=' + round_class + '&imacType=' + round_type + '&roundNum=' + round_num,
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.ajax.reload(function(){
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

  // Start a round - only if it's paused or unstarted and if no other rounds are started.
  $(document).on('click', '.function_start a', function(e){
    e.preventDefault();
    var round_class = $(this).data('imacclass');
    var round_type  = $(this).data('imactype');
    var round_num   = $(this).data('roundnum');
    var round_phase = $(this).data('phase');
    var blOkToGo = true;
    
    if (round_phase === 'O' || round_phase === 'D') {
        blOkToGo = false;
    }
    if (!confirm("Are you sure you want to start flying '" + round_type + "' round '" + round_num + "' in class '" + round_class + "' ?")) {
        blOkToGo = false;
    }

    if (blOkToGo) {
      show_loading_message();
      var request = $.ajax({
        url:          'data.php?job=start_round&imacClass=' + round_class + '&imacType=' + round_type + '&roundNum=' + round_num,
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.ajax.reload(function(){
            hide_loading_message();
            show_message("Round '" + round_type + "' number '" + round_num + "' in class '" + round_class + "' is started.", 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Start request failed: ' + output.message, 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Start request failed: ' + textStatus, 'error');
      });
    }
  });  // Start round...

  // Pause a round - only if it's currently open (flying).
  $(document).on('click', '.function_pause a', function(e){
    e.preventDefault();
    var round_class = $(this).data('imacclass');
    var round_type  = $(this).data('imactype');
    var round_num   = $(this).data('roundnum');
    var round_phase = $(this).data('phase');
    var blOkToGo = true;
    
    if (round_phase !== 'O') {
        blOkToGo = false;
    }
    if (!confirm("Are you sure you want to pause '" + round_type + "' round '" + round_num + "' in class '" + round_class + "' ?")) {
        blOkToGo = false;
    }

    if (blOkToGo) {
      show_loading_message();
      var request = $.ajax({
        url:          'data.php?job=pause_round&imacClass=' + round_class + '&imacType=' + round_type + '&roundNum=' + round_num,
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.ajax.reload(function(){
            hide_loading_message();
            show_message("Round '" + round_type + "' number '" + round_num + "' in class '" + round_class + "' is paused.", 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Pause request failed: ' + output.message, 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Pause request failed: ' + textStatus, 'error');
      });
    }
  });  // Pause round...

  $(document).on('click', '.function_scores a', function(e){
      var roundId = $(this).data('roundid');
      window.location.assign('/scores/' + roundId);
  });

  // Set a paused round to be completed.
  $(document).on('click', '.function_finish a', function(e){
    e.preventDefault();
    var round_class = $(this).data('imacclass');
    var round_type  = $(this).data('imactype');
    var round_num   = $(this).data('roundnum');
    var round_phase = $(this).data('phase');
    var blOkToGo = true;
    
    if (round_phase !== 'P') {
        blOkToGo = false;
    }
    if (!confirm("Are you sure you want to finish '" + round_type + "' round '" + round_num + "' in class '" + round_class + "' ?")) {
        blOkToGo = false;
    }

    if (blOkToGo) {
      show_loading_message();
      var request = $.ajax({
        url:          'data.php?job=finish_round&imacClass=' + round_class + '&imacType=' + round_type + '&roundNum=' + round_num,
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.ajax.reload(function(){
            hide_loading_message();
            show_message("Round '" + round_type + "' number '" + round_num + "' in class '" + round_class + "' is finished.", 'success');
          }, true);
        } else {
          hide_loading_message();
          show_message('Finish request failed: ' + output.message, 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Finish request failed: ' + textStatus, 'error');
      });
    }
  });  // Finish round...

  // Chose this flight for the next.
  $(document).on('click', '.function_set_next_flight_button a', function(e){
    e.preventDefault();
    var thisButton = $(this);
    var next_seqnum       = $(thisButton).data('seqnum');
    var next_roundid      = $(thisButton).data('roundid');
    var next_pilotid      = $(thisButton).data('pilotid');
    var next_noteflightid = $(thisButton).data('noteflightid');
    var next_pilotname    = $(thisButton).data('pilotname');
    var blOkToGo = true;
    
    //show_message("Setting next flight to " + next_noteflightid + " pilot " + next_pilotid, 'success');
    blOkToGo = true;

    if (blOkToGo) {
      show_loading_message();
      var request = $.ajax({
        url:          'data.php?job=set_next_flight&roundId=' + next_roundid + '&seqNum=' + next_seqnum + '&pilotId=' + next_pilotid + '&noteFlightId=' + next_noteflightid,
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });
      request.done(function(output){
        if (output.result === 'success'){
          // Reload datable
          table_roundlist.ajax.reload(function(){
            hide_loading_message();
            show_message("Set next flight to " + next_noteflightid + " pilot " + next_pilotid, 'success');
            fillNextFlight(next_roundid, next_pilotname, next_seqnum);
            adjustNextFlightButtons(next_roundid);
            // Reset the Buttons!
            $('.function_set_next_flight_button a').removeClass("highlighted_button");
            $(thisButton).addClass("highlighted_button");
          }, true);
        } else {
          hide_loading_message();
          show_message('Set next flight request failed: ' + output.message, 'error');
        }
      });
      request.fail(function(jqXHR, textStatus){
        hide_loading_message();
        show_message('Set next flight request failed: ' + textStatus, 'error');
      });
    }
  });  // Set next pilot....

  $('#imacType').on('change',function(){
    $(this).parent('.field_container').removeClass('error');
    $('#imacType-error').hide();
    if( $(this).val()==="Freestyle"){
      $('#imacClass').parent('.field_container').removeClass('error');
      $('#imacClass-error').hide();
      $('#sequences').parent('.field_container').removeClass('error');
      $('#sequences-error').hide();

      $("#imacClass").hide();
      $("#sequences").hide();
      $("#hidden_imacClass").show();
      $("#hidden_sequences").show();
    } else if ( $(this).val()==="Unknown") {
      $("#imacClass").show();
      $("#hidden_imacClass").hide();
      $("#sequences").hide();
      $("#hidden_sequences").show();
    } else {
      $("#imacClass").show();
      $("#sequences").show();
      $("#hidden_imacClass").hide();
      $("#hidden_sequences").hide();
    }
    fillSchedules($('#imacClass').val(), $(this).val());
    setNextRound($('#imacClass').val(), $(this).val());
  });

  $('#roundNum').on('focus',function(){
    $(this).parent('.field_container').removeClass('error');
    $('#roundNum-error').hide();
  });
  
  $('#schedule').on('change',function(){
    $(this).parent('.field_container').removeClass('error');
    $('#schedule-error').hide();
  });

  $('#imacClass').on('change',function(){
    $(this).parent('.field_container').removeClass('error');
    $('#imacClass-error').hide();
    fillSchedules($(this).val(), $('#imacType').val());
    setNextRound($(this).val(), $('#imacType').val());
  });

  function fillNextFlight(roundId, pilotName, seqNum) {
    if (pilotName === null || seqNum === null) {
        
      var request = $.ajax({
        //Oldway: url:          'data.php?job=get_next_flight&roundId=' + roundId,
        url:          'api/1/rounds/' + roundId + '/nextflight',
        cache:        false,
        dataType:     'json',
        contentType:  'application/json; charset=utf-8',
        type:         'get'
      });

      request.done(function(output) {
        if (output.result === 'success') {
          $('#nextflight-details').text("Sequence " + output.data.nextSequenceNum + " for pilot " + output.data.nextPilotName);
          highlightNextFlightButton(output.data.nextRoundId, output.data.nextPilotId, output.data.nextNoteFlightId, output.data.nextCompId);
        } else {
          $('#nextflight-details').text("Sequence unknown for pilot unknown.");   
        }
      });

      request.fail(function(jqXHR, textStatus) {
        $('#nextflight-details').text("Sequence unknown for pilot unknown.");   
        show_message('Could not get the next flight data: ' + textStatus, 'error');
      });
    
    } else {
      $('#nextflight-details').text("Sequence " + seqNum + " for pilot " + pilotName);   
    }
  }

 function adjustNextFlightButtons(roundId) {
    //var roundId = $(this).data('roundId');
    var request = $.ajax({
      //Oldway: url:          'data.php?job=get_round_flightstatus&roundId=' + roundId,
      url:          'api/1/rounds/' + roundId + '/flightstatus',
      cache:        false,
      dataType:     'json',
      contentType:  'application/json; charset=utf-8',
      type:         'get'
    });

    request.done(function(output) {
      if (output.result === 'success') {
        var roundFlightData = output.data;        

        $('.function_set_next_flight_button a').each(function() {
            
            var blFlightHasFinishedSheets = false;
            var blFlightHasUnfinishedSheets = false;
            var thisButtonId = $(this).attr("id");

            console.log("Checking the status for next flight button " + this.id + ".");

            roundFlightData.forEach(function (flightSheetStatus) {
                if (flightSheetStatus.buttonID === thisButtonId) {
                    if (flightSheetStatus.phase === "D") {
                        blFlightHasFinishedSheets = true;
                    } else {
                        blFlightHasUnfinishedSheets = true;
                    } 
                }
            });
            if (blFlightHasUnfinishedSheets === true) {
                console.log("   At least some sheets not finished.");
                $('#' + thisButtonId).removeClass("disabled_button");
            } else if (blFlightHasFinishedSheets === true) {
                console.log("   All sheets done!  Removing button.");
                $('#' + thisButtonId).addClass("disabled_button");
            } else {
                console.log("   No score data found.");
            }

        });
      }
    });

    request.fail(function(jqXHR, textStatus) {
      show_message('Could not get the next flight data: ' + textStatus, 'error');
    });
  }

  function highlightNextFlightButton(roundId, pilotId, noteFlightId, classId) {
    $('.function_set_next_flight_button a').removeClass("highlighted_button");
    $('#' + roundId + '_' + pilotId + '_' + noteFlightId + '_' + classId).addClass("highlighted_button");
  }

  function removeOptions(selectbox) {
    var i;
    for(i = selectbox.options.length - 1 ; i > 0 ; i--) {
      selectbox.remove(i);
    }
  }

  function listCookies() {
    var theCookies = document.cookie.split(';');
    var aString = '';
    for (var i = 1 ; i <= theCookies.length; i++) {
      aString += i + ' ' + theCookies[i-1] + "\n";
    }
    console.log("Cookies: " + aString);
  }
  listCookies();
});
