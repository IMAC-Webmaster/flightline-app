/*
 * Copyright (c) 2019 Dan Carroll
 *
 * This file is part of FlightLine.
 *
 * FlightLine is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * FlightLine is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FlightLine.  If not, see <https://www.gnu.org/licenses/>.
 */
$(document).ready(function() {
    // Set up our handlers here.

    $('#breakFlag').off('click').on('click', function () {
        console.log("Break penalty: " + $(this).is(":checked"));
        $('input[name=breakFlag]').val($(this).is(":checked") ? 1 : 0);
    } );

    $('button[type=submit]').off('click').on('click', function () {
        $('input[name=button]').val($(this).val());
        console.log("Pressed button: " + $(this).text());
    } );

});

function parseRoundData(round) {
    // round.* is from round table...
    // round.schedule is the schedule table.
    // round.schedule.figures is the figure table
    // round.pilots is the pilots table.
    // round.pilots.sheets are the pilots sheets for the round (double round with 2 judges = 4 sheets!)
    // round.pilots.sheets.scores are the scores!

    // Save the round globally..
    currentRound = round;
    let roundInfo = gatherInfo(round);
    roundInfo['sequenceCount'] = round.sequences;
    roundInfo['sheetCount'] = round.sequences * roundInfo['judgeCount'];
    let thisColumnCount = (roundInfo['sheetCount'] + 2 );
    console.log("Got the info from the round data.  We need:");
    console.log("  " + thisColumnCount + " columns for " + round.sequences  + " sheets per judge from " + roundInfo['judgeCount'] + " judges.");
    if (thisColumnCount != lastColumnCount) {
        console.log("  The table must be redrawn.");
        lastColumnCount = thisColumnCount;
        destroyTable = true;
        lastFlightInfo = {latestScoreTime:"", pilotId:"", roundId:""}
    } else {
        destroyTable = false;
    }
    console.log("Building datatable data.");
    let dataTable = {columns:[ {data:"num", title: "Num."}, {data:"fig", title: "Figure"} ], data:[]};
    // Lets do this for each pilot in the data!
    let judgeCols = {};
    for (var pIdx in round.pilots) {
        dataTable.data.push(new Array());
        for (var fIdx in round.schedule.figures) {
            var myDataRow = {
                num: round.schedule.figures[fIdx].figureNum, 
                fig: round.schedule.figures[fIdx].longDesc
            };
            for (var sIdx in round.pilots[pIdx].sheets) {
                var colSeqNum = round.pilots[pIdx].sheets[sIdx].sequenceNum;
                var colJudgeNum = round.pilots[pIdx].sheets[sIdx].judgeNum;
                var colSheetId = round.pilots[pIdx].sheets[sIdx].sheetId;
                var colName = " Seq " + colSeqNum + " Judge " + colJudgeNum;
                var colData = "S" + colSeqNum + "J" + colJudgeNum;

                myDataRow[colData] = {};
                myDataRow[colData] = { ...round.pilots[pIdx].sheets[sIdx].scores[fIdx] };
                //myDataRow[colData] = round.pilots[pIdx].sheets[sIdx].scores[fIdx];
                myDataRow[colData]["sheetId"] = colSheetId;
                judgeCols[colData] = { title: colName, seqnum: colSeqNum, judgenum: colJudgeNum};
            }
            // We really only want to add  
            dataTable.data[pIdx].push(myDataRow);
        }
        round.pilots[pIdx].datatable = dataTable.data[pIdx];
    }
    var colIds = Object.keys(judgeCols);
    colIds.sort();
    for (var colIdx in colIds) {
        dataTable["columns"].push({data: colIds[colIdx], title: judgeCols[colIds[colIdx]].title, seqnum: judgeCols[colIds[colIdx]].seqnum, judgenum: judgeCols[colIds[colIdx]].judgenum});
    }

    round["datatable_columns"] = dataTable["columns"];

}

function padRoundData(resultdata, tabledata, pilotId) {

    var p = getPilotIndexFromId(pilotId, resultdata.pilots);
    if (p === null) {
        console.log("Could not find any pilot data in this round...");
        return;
    }

    // Fill empty pilot sheets here (based on whats in resultdata.datable_columns)
    for (var dti in resultdata.pilots[p].datatable) {
        for (var col in resultdata.datatable_columns) {
            // The sheet itself was not there.   So we don't have any data.
            if ((col > 1) && (typeof resultdata.pilots[p].datatable[dti][resultdata.datatable_columns[col].data] === "undefined")) {
                console.log("There is no data for this pilot in this resultset!");
                // How to destroy the DT?
                tabledata.data = resultdata.pilots[p].datatable;
                tabledata.sheets = resultdata.pilots[p].sheets;
                tabledata.columns = resultdata.datatable_columns;
                return;
            }

            // If we had a sheet, but did not have a score for this fig, then the 'score' property of the score object wont be defined.
            // The object itself will be there because we already added the sheetId when parsing the response.
            if ((col > 1) && (typeof resultdata.pilots[p].datatable[dti][resultdata.datatable_columns[col].data].score === "undefined")) {
                // We need to add it.  (A blank one)
                let currentScoreObject = resultdata.pilots[p].datatable[dti][resultdata.datatable_columns[col].data];
                let defaultScoreObject = {figureNum: resultdata.pilots[p].datatable[dti][resultdata.datatable_columns[0].data], scoreTime: null, breakFlag: null, score: 'No Score', comment: null, scoreDelta: null}
                // ES6 Only!   Pre 2015 will break.
                resultdata.pilots[p].datatable[dti][resultdata.datatable_columns[col].data] = {...currentScoreObject, ...defaultScoreObject};
            }
        }
    }

    tabledata.data = resultdata.pilots[p].datatable;
    tabledata.sheets = resultdata.pilots[p].sheets;
    tabledata.columns = resultdata.datatable_columns;
    for (col in tabledata.columns) {
        // Iterate over the columns and add the sheet data.
        if (tabledata.columns[col].data === "num" || tabledata.columns[col].data === "fig") {
            ;
        } else {
            for (var sheet in tabledata.sheets) {
                if (tabledata.sheets[sheet].sequenceNum === tabledata.columns[col].seqnum && tabledata.sheets[sheet].judgeNum === tabledata.columns[col].judgenum) {
                    // This is the sheet!   Delete the scores to save some memory...
                    // Hold it...   Maybe we need to pass this one to the API!
                    //delete tabledata.sheets[sheet].scores;
                    tabledata.columns[col]["sheet"] = tabledata.sheets[sheet];
                    if (tabledata.sheets[sheet].mppFlag === 1) {
                        tabledata.columns[col]["className"] = "score mpp";
                    }
                }
            }
        }
    }
    tabledata.pilot = {
        active: resultdata.pilots[p].active,
        fullName: resultdata.pilots[p].fullName,
        airplane: resultdata.pilots[p].airplane,
        freestyle: resultdata.pilots[p].freestyle,
        imacClass: resultdata.pilots[p].imacClass,
        in_customclass1: resultdata.pilots[p].in_customclass1,
        in_customclass2: resultdata.pilots[p].in_customclass2,
        pilotId: resultdata.pilots[p].pilotId,
        pilotPrimaryId: resultdata.pilots[p].pilotPrimaryId
    };
    tabledata.round = {
        description: resultdata.description,
        imacClass: resultdata.imacClass,
        imacType: resultdata.imacType,
        phase: resultdata.phase,
        roundId: resultdata.roundId,
        roundNum: resultdata.roundNum,
        schedId: resultdata.schedId,
        sequences: resultdata.sequences,
        status: resultdata.status
    };
}

function gatherInfo(item) {
    var roundInfo = new Array();
    roundInfo['pilotCount'] = item.pilots.length;
    console.log("Pilot Count: " +  roundInfo['pilotCount']);
    var maxSheets = 0;
    var maxJudgeNum = 0;
    var judgeNumbers = new Array();
    roundInfo['judgeCount'] = 0;
    for (pilot in item.pilots) {  
        if (item.pilots[pilot].sheets.length > maxSheets) {
            maxSheets = item.pilots[pilot].sheets.length;
        }
        for (sheet in item.pilots[pilot].sheets) {
            if (judgeNumbers[item.pilots[pilot].sheets[sheet].judgeNum] === undefined) {
                judgeNumbers[item.pilots[pilot].sheets[sheet].judgeNum] = ++roundInfo['judgeCount'];
            }
            if (item.pilots[pilot].sheets[sheet].judgeNum > maxJudgeNum) {
                maxJudgeNum = item.pilots[pilot].sheets[sheet].judgeNum;
            }
        }
        roundInfo['judgeIds'] = Object.keys(judgeNumbers);
        roundInfo['judgeIds'].sort();
    }
    
    roundInfo['sheetCount'] = maxSheets;
    console.log("Judge Count: " +  roundInfo['judgeCount']);
    return (roundInfo);
}

function paintTableInfo() {
    // Draw the pilot details.
    $("#pilotName").html(data.pilot.fullName);
    $("#roundNum").html(data.round.roundNum);
    if (data.round.sequences === 1) {
        $("#roundType").html(data.round.imacType + " Single");
    } else if (data.round.sequences === 2) {
        $("#roundType").html(data.round.imacType + " Double");
    }
    $("#roundClass").html(data.round.imacClass);
    $("#roundSchedule").html(data.round.description);

    lastFlightInfo.pilotId = data.pilot.pilotId;
    lastFlightInfo.roundId = data.round.roundId;
}

function handleAjaxResponse(roundId, pilotId, liveResults) {
    var str;
    console.log("Displaying pilot: " + data.pilot.fullName + " T: " + pilotId+ " L: " + lastFlightInfo.pilotId );

    // Check if we need to destroy the old table.    Need to make this more generic.   Lastflightinfo is for live scores but we need a 'lastShownFlightInfo' as well.
    if (liveResults === true && (lastFlightInfo.roundId != roundId || lastFlightInfo.pilotId != pilotId) ) {
        if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
            console.log("Different round/pilot, recreating table...");
            table.clear();
            table.destroy();
            $("#scores thead tr").empty();
        }
    } else {
        if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
            paintTableInfo();
            return;
        }
        console.log("Creating Datatable...");
    }

    // Datatable is empty.   Create it from scratch.
    // Iterate each column and print table headers for Datatables
    $.each(data.columns, function (k, colObj) {
        str = '<th></th>';
        $(str).appendTo('#scores>thead>tr');
    });


    $.fn.dataTable.ext.errMode = 'none';
    table = $('#scores')
        .on( 'error.dt', function ( e, settings, techNote, message ) {
            console.log( 'An error has been reported by DataTables: ', message );
        } )
        .DataTable({
        "ajax": {
            // Not sure which is a better API pattern
            // "url": "/api/1/rounds/" + roundId + "/pilots/" + pilotId,
            "url": "/api/1/rounds/" + roundId + "/scores"+ "?pilot=" + pilotId,
            "dataSrc": function ( json ) {
                return processAJAXResposeForDT(json);
            }
        },
        "dom": "t",
        "columns": data.columns,
        "lengthChange": false,
        "pageLength": 12,
        "ordering": false,
        "columnDefs": [
            { "targets": [0],    className: "num" },
            { "targets": [1],    className: "desc" },
            { "targets": "_all",    className: "score" },
            { "targets": "_all",    createdCell: function (td, cellData, rowData, row, col) { renderScoreContainer(td, cellData, rowData, row, col); } },
            { "targets": "_all",    render: function ( data, type, row ) { return (typeof data === "object") ? renderScore(data, type, row) : data; } }
        ],
        "fnInitComplete": function () {
            // Event handler to be fired when rendering is complete (Turn off Loading gif for example)
            console.log('Datatable rendering complete');
        }
    });

    // Set validation defaults
    /*********************
    jQuery.validator.setDefaults({
        success: 'valid',
        rules: {
            breakFlag:     { excluded: true }
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
     /*********************/
    /********************
    $("#form_editscore").validate({
        // Specify validation rules
        rules: {
            // The key name on the left side is the name attribute
            // of an input field. Validation rules are defined
            // on the right side
            breakFlag: { excluded: true },
            score: { required: true }
        },
        // Specify validation error messages
        messages: {
            score: "Please enter a score."
        },
        // Make sure the form is submitted to the destination defined
        // in the "action" attribute of the form when valid
        submitHandler: function(form) {
            form.submit();
        }
    });
     /*********************/

    // Edit score submit form
    $(document).on('submit', '#form_editscore', function(e) {
        e.preventDefault();

        if (validateScoreForm()){
            // Send score information to database
            //hide_ipad_keyboard();
            //hide_lightbox();
            show_loading_message();
            let formObject = helpers.getFormData($('#form_editscore'));
            let formMethod = 'get';
            let action = $('input[name=button]').val();
            //action = 'save';

            switch(action) {
                case 'undo':  //delete the adjustment.
                    formMethod = 'delete';
                    break;
                case 'delete':  //delete the score.
                    formMethod = 'post';
                    break;
                default:
                    formMethod = 'post';
                    if (!validateScoreForm()) {
                        return;
                    }
                    break;
            }
            var request   = $.ajax({
                //url:            "/api/1/rounds/" + roundId + "/scores"+ "?pilot=" + pilotId,
                //url:            "/api/1/jsonblah/" + roundId + "/pilot/" + pilotId,
                url:            "/api/1/sheets/" + formObject.sheetId + "/" + formObject.figureNum + "/adjustment",
                cache:          false,
                data:           JSON.stringify(formObject),
                dataType:       'json',
                contentType:    'application/json; charset=utf-8',
                type:           formMethod
            });
            request.done(function(output){
                if (output.result === 'success'){
                    // Reload datable
                    $('.lightbox_close').click();
                    hide_loading_message();
                    show_message(output.message, 'success');
                    // Should we reload the table?   Probably...
                    //table.ajax.reload(function(){
                    //    hide_loading_message();
                    //    show_message("Score X edited successfully." + output.message, 'success');
                    //}, true);
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
    }); // Edit score submit.


    // Handle score editing...
    $('#scores').off('click').on( 'click', 'tbody td.score', function (event) { displayScoreEditForm(event, this, roundId, pilotId) });

    // Handle close....
    $(document).off('click').on('click', '.lightbox_close', function(){
        $('.lightbox_bg').hide();
        $('.lightbox_container').hide();
        $('.lightbox_content').hide();
    });

    // Handle Escape Key
    $(document).keyup(function(e) { if (e.keyCode === 27) $('.lightbox_close').click(); });

    paintTableInfo();
}

function displayScoreEditForm(event, theScoreCell, roundId, pilotId) {
    event.preventDefault();
    show_loading_message();
    let d = table.cell( theScoreCell ).data();
    let offset = $(theScoreCell).offset();
    let fig = getFigureDetails(d.figureNum);
    console.log("Score cell data: ");
    console.log( d );
    console.log("Set up event for DT R:" + roundId + " P:" + pilotId);
    console.log("Figure:");
    console.log(fig);
    $('#editscoreTitle').html("Score");
    $('#figureDetails').html("<div>Figure: " + fig.figureNum + "</div><div>Desc: " + fig.longDesc + "</div><div>K-Factor: " + fig.k + "</div><div>Rule: " + fig.rule + "</div><div>Long Desc: " + fig.spokenText + "</div>");
    $('#form_editscore').trigger("reset");
    $('#form_editscore').validate().resetForm();

    // Set some defaults.
    $('input[name=score]').val("");
    $('input[name=score]').prop('disabled', false);
    $('input[name=comment]').val("");
    $('input[name=comment]').prop('disabled', false);
    $('input[name=cdcomment]').val("");
    $('input[name=cdcomment]').prop('disabled', false);
    $('#delete').prop('disabled', false);
    $('#delete').val("delete");
    $('#breakFlag').prop('disabled', false);
    $('#save').prop('disabled', false);
    $('#delete').html("Delete Score");

    if (d.scoredelta) {
        // There is extra score info...   Add it.
        $('#delete').html("Undo adjustment");
        $('#delete').val("undo");
        if (d.scoredelta.deleted) {
            $('input[name=score]').val("");
            $('input[name=score]').prop('disabled', true);
            $('#breakFlag').prop('disabled', true);
            $('#save').prop('disabled', true);
        } else {
            $('input[name=score]').val(d.scoredelta.score);
            $('input[name=comment]').val(d.scoredelta.comment);
            $('input[name=cdcomment]').val(d.scoredelta.cdcomment);
            if (d.scoredelta.breakFlag)
                $('#breakFlag').prop('checked', d.scoredelta.breakFlag == 1 ? true : false);
        }
    } else {
        // If there is no score, dont show the deleted button.
        $('#delete').prop('disabled', d.scoreTime ? false : true);
        $('input[name=score]').val(d.score);
        $('input[name=comment]').val(d.comment);
        if (d.breakFlag)
            $('#breakFlag').prop('checked', (d.breakFlag == 1 ? true : false));
    }

    // Fill in the hidden fields.
    $('input[name=sheetId]').val(d.sheetId);
    $('input[name=figureNum]').val(d.figureNum);
    $('input[name=breakFlag]').val($('#breakFlag').is(":checked") ? 1 : 0);

    $('#save').attr('disabled', true);
    $('#form_editscore').on('input change', function() {
        $('#save').attr('disabled', false);
    });

    $("div.lightbox_content#editscore").show();
    $('.lightbox_bg').show();
    $('.lightbox_container').show();

    $('.lightbox_container')
        .fadeIn()
        .css({
            left: Math.min( offset.left, (($(window).innerWidth() / 2) - ($('.lightbox_container').outerWidth() / 2)) ),
            top:  Math.min( (offset.top + $(this).innerHeight()), (($(window).innerHeight() / 2) - ($('.lightbox_container').outerHeight() / 2)) )
        });
    hide_loading_message();
}

/*************************/
function validateScoreForm() {
    let form_valid = true;

    // Always allow undo/deleted to be pressed.
    if ($('input[name=button]').val() === "undo" || $('input[name=button]').val() == "delete")
        return true;

    if ($('#form_editscore #score').val() === "") {
        $('#form_editscore #score').parent('.field_container').addClass('error');
        $('#form_editscore #score-error').text("Score cannot be empty.").show();
        form_valid = false;
    }

    if (form_valid === true) {
        // Anything else to do before submit?
    }

    return form_valid;
}
/*************************/

function getFigureDetails(figureNum) {
    var foundItem = null;
    if (currentRound)
    currentRound.schedule.figures.forEach(function(item, index) {
        if (item.figureNum && item.figureNum == figureNum)
            foundItem = item;
    });
    return foundItem;
}

function renderScoreContainer (td, cellData, rowData, row, col) {
    var chosendata = cellData;

    if ((typeof cellData === "object") && (cellData !== null)) {
        if (cellData.scoredelta) {
            // We've got adjusted scores!
            chosendata = cellData.scoredelta;
        }
        if (chosendata.breakFlag === 1 && !chosendata.deleted) {
            $(td).addClass("break");
        }
        var dateNow = +new Date();
        if ((chosendata.scoreTime + 30) > (dateNow / 1000) ) { //Is this less than 30 seconds old?
            $(td).addClass("new");
        }
    }
}

function renderScore (d, t, r) {
    var classes = "score";
    var extradivs = "";
    var chosendata = d;
    if (typeof d.features !== "undefined") {
        for (var i = 0, len = d.features.length; i < len; i++) {
          classes = classes + " " + d.features[i];
        }
    }

    if (d.scoredelta) {
        chosendata = d.scoredelta;
    }

    if (chosendata.breakFlag === 1) {
        extradivs = extradivs + "<div class='break'>[br]</div>";
    }

    if (chosendata.boxFlag === 1) {
        // We dont do box errors any more.
        extradivs = extradivs + "<div class='box'>[bx]</div>";
    }

    if (d.scoredelta) {
        extradivs = extradivs + "<div class='adjusted'>[adjusted]</div>";
    }
    if (chosendata.deleted) {
        // If the score is deleted, ignore the other classes (such as break)
        return ("<div class='" + classes + "'>No Score</div><div class='scorewrapper'><div class='adjusted'>[adjusted]</div></div>");
    } else {
        return ("<div class='" + classes + "'>" + chosendata.score + "</div><div class='scorewrapper'>" + extradivs + "</div>");
    }
}

function getPilotIndexFromId(id, data) {
    var idx = null;
    $.each(data, function (i, v) {
        if (v["pilotId"] == id) {
            idx = i;
        }
    });
    return idx;
}

function getMostRecentPilotAndFlight(loadScores = false) {
    var xhr;
    var result = null;
    loadingRecentFlightInfo = true;
    console.log("Getting the most recent flight data.")
    xhr = $.ajax(
        {
            url: '/data.php?job=get_latest_round_and_pilot',
            type: 'GET',
            cache: false,
            timeout: 15000,
            async: true
        })
        .done(function()
        {
            loadingRecentFlightInfo = 'DONE';
            result = JSON.parse(xhr.responseText);
            latestFlightInfo = result.data;
            console.log("DEBUG: Most recent round: " + result.data.roundId);
            console.log("DEBUG: Most recent pilot: " + result.data.pilotId);
            if (loadScores) {
                loadScoreData(result.data.roundId, result.data.pilotId);
            }
        })
        .fail(function()
        {
            loadingRecentFlightInfo = 'FAILED';
            latestFlightInfo = {latestScoreTime:"", pilotId:"", roundId:""};
        });
}

function processAJAXResposeForDT(result) {

    // Number 1...   Are we looking at live data?
    // If so, get the round details from the latest flight array.

    let roundId = $('#roundSel option:selected').val();
    let pilotId = $('#pilotSel option:selected').val();
    let liveResults = false;  // Are we operating from liveData or did the user select something.

    if ( (!roundId || roundId === 'Live') || (pilotId === null || pilotId === '')) {

        switch(loadingRecentFlightInfo) {
            // ToDo: Change if block for switch...
        }
        if (loadingRecentFlightInfo === true) {
            // We're not done...
            setTimeout(function()  { return processAJAXResposeForDT(result); }, 30);

        } else if (loadingRecentFlightInfo === false) {
            // We're not loading, but we need it...
            lastFlightInfo = latestFlightInfo;  // Should be set from last time...
            liveResults = true;

            $('#pilotSel').hide();
            getMostRecentPilotAndFlight();
            return processAJAXResposeForDT(result);
        } else if (loadingRecentFlightInfo === 'DONE') {
            // We got the data!
            loadingRecentFlightInfo = false;
            if ( lastFlightInfo && (latestFlightInfo.roundId == lastFlightInfo.roundId) && (latestFlightInfo.pilotId == lastFlightInfo.pilotId) ) {
                destroyTable = false;
            } else {
                destroyTable = true;
            }
            roundId = latestFlightInfo.roundId;
            pilotId = latestFlightInfo.pilotId;
        } else if (loadingRecentFlightInfo === 'FAILED') {
            console.log("ERROR: Could not get flight info.")
            return null;
        }

    }

    parseRoundData(result.data);
    padRoundData(result.data, data, pilotId);
    handleAjaxResponse(roundId, pilotId, liveResults);
    return data.data;
}

function loadScoreData(roundId, pilotId) {
    // sequenceNumber is optional...    The others are not.
    if (!roundId && !pilotId) {
        return;
    }

    if (roundId === 'Live') {
        // In this case we don't know what round to get the data for.   So first we mist ask.
        getMostRecentPilotAndFlight(true);
        return;
    }

    //Before creating the table, we need to get the data once so we can check out the columns.
    //This means a second ajax call the first time we load the page...  :-(

    var url = '/api/1/rounds/' + roundId + '/scores';

    jqxhr = $.ajax(url)
        .done(function(){
            let result = JSON.parse(jqxhr.responseText);
            parseRoundData(result.data);
            padRoundData(result.data, data, pilotId);
            handleAjaxResponse(roundId, pilotId, true);

        })
        .fail(function (jqXHR, exception) {
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.\n' + jqXHR.responseText;
            }
            console.log(msg);
            data.columns = new Array();
            data.data = new Array();
            data.sheets = new Array();
        })
        .always(function() {
            paintPage();
        });
}

function loadRoundData(roundId, pilotId) {
    // If they selected nothing, then just clear table, and clear selects...
    if (!roundId || roundId === 'Live' || pilotId === null || pilotId === '') {
        switch(loadingRecentFlightInfo) {
            // ToDo: Change if block for switch..
            case true:
                // We're not done...
                console.log ("Recursion at: " + recursionCounter++);
                setTimeout(function()  { loadRoundData(roundId, pilotId); }, 30);
                return;
            case false:
                // We're not loading, but we need it...
                //lastFlightInfo = latestFlightInfo;  // Should be set from last time...
                recursionCounter = 1;
                //show_message("Geting the latest flight info.", "success");
                show_loading_message();
                $('#pilotSel').hide();
                getMostRecentPilotAndFlight();
                //if (latestFlightInfo.latestScoreTime)
                //    loadRoundData(roundId, pilotId);
                setTimeout(function()  { loadRoundData(roundId, pilotId); }, 30);
                return;
            case "DONE":
                // We got the data!
                loadingRecentFlightInfo = false;
                if ( lastFlightInfo && (latestFlightInfo.roundId == lastFlightInfo.roundId) && (latestFlightInfo.pilotId == lastFlightInfo.pilotId) ) {
                    destroyTable = false;
                } else {
                    destroyTable = true;
                }
                //hide_message();
                hide_loading_message();
                loadRoundData(latestFlightInfo.roundId, latestFlightInfo.pilotId, null);
                return;
            case "FAILED":
                console.log("ERROR: Could not get flight info.")
                hide_loading_message();
                show_message("Could not get latest flight info.", "error");
                return;
            default:
                break;
        }
    }

    //Before creating the table, we need to get the data once so we can check out the columns.
    //This means a second ajax call the first time we load the page...  :-(

    var url = '/api/1/rounds/' + roundId + '/scores';

    jqxhr = $.ajax(url)
        .done(function(){ 
            result = JSON.parse(jqxhr.responseText);
            parseRoundData(result.data);
            padRoundData(result.data, data, pilotId);
            handleAjaxResponse(roundId, pilotId, true);

        })
        .fail(function (jqXHR, exception) {
            var msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.\n Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.\n' + jqXHR.responseText;
            }
            console.log(msg);
            data.columns = new Array();
            data.data = new Array();
            data.sheets = new Array();
        })
        .always(function() {
            paintPage();
        });
}

function getSelection() {
    selectionData.roundId = $('#roundSel option:selected').val();
    selectionData.pilotId = $('#pilotSel option:selected').val();
    selectionData.autoRefresh = $('#autoRefresh').is(':checked') ? true : false;
}

function scoreDataAvailable() {

    if ((latestFlightInfo.latestScoreTime && selectionData.autoRefresh === true) || (data.sheets && data.sheets.length > 0)) {
        return true;
    } else {
        return false;
    }
}

function paintPage() {
    // When we are here we should have all data ready to go.
    // 1. Set the select boxes to be what they should be and display/hide as need be.
    // 2. Paint the Class/Round/Sched etc fields if need be.
    // 3. Set the pilot/table/nodata stuff vis/hidden as need be.
    getSelection();
    if (loadingPilotInfo || loadingRoundInfo) {
        $("#pilotSel").hide();
        return;
    }

    $("#roundSel").show();
    if (selectionData.roundId === "Live") {
        $("#pilotSel").hide();
    } else {
        $("#pilotSel").show();
    }
    if (scoreDataAvailable()) {
        $("#scores").show();
        $("#pilotNameHeader").show();
        $("#noDataHeader").hide();
    } else {
        $("#scores").hide();
        $("#pilotNameHeader").hide();
        $("#noDataHeader").show();
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

function populateRoundSelect(selectedRound) {
    let xhr;
    let url = '/api/1/rounds';
    loadingRoundInfo = true;
    xhr = $.ajax(url)
        .done(function()
        {
            let result = JSON.parse(xhr.responseText);
            helpers.buildDropdownWithDefaultValue( helpers.cleanData("Round", result.data), $('#roundSel'), 'Live Data', 'Live', selectedRound);
            getSelection();
            if (!initialLoadDone  && ( selectionData.roundId === 'Live' || (typeof selectionData.roundId == 'number' && selectionData.pilotId)) ) {
                initialLoadDone = true;
                loadScoreData(selectionData.roundId, selectionData.pilotId);
            }
        })
        .fail(function() {
            console.log("ERROR: populateRoundSelect ajax call failed.");
        })
        .always(function() {
            console.log("DEBUG: populateRoundSelect painting.");
            loadingRoundInfo = false;
            paintPage();
        });

}

function populatePilotSelect(roundId, selectedPilot) {
    let xhr;
    let url = '/data.php?job=get_round_pilots&roundId=' + roundId;
    loadingPilotInfo = true;
    paintPage();
    xhr = $.ajax(url)
        .done(function() {
            let result = JSON.parse(xhr.responseText);
            helpers.buildDropdownWithMessage( helpers.cleanData("Pilot", result.data), $('#pilotSel'), 'Choose Pilot', selectedPilot);
            //$('#pilotSel option:selected').val($('#pilotSel option:selected').val()).trigger('change');
        })
        .fail(function() {
            console.log("ERROR: populatePilotSelect ajax call failed.");
        })
        .always(function() {
            loadingPilotInfo = false;
            paintPage();
        });
}

var data = {data: [], columns: [], pilot:[], round:[]};
var currentRound = null;
var lastColumnCount = 0;
var jqxhr, table, scoresEditor, latestFlightInfo = {latestScoreTime:"", pilotId:"", roundId:""}, lastFlightInfo, destroyTable = true;
var selectionData = {roundId:null, pilotId:null, autoRefresh:false};
var loadingRoundInfo = null;
var loadingPilotInfo = null;
var loadingRecentFlightInfo = false;
var initialLoadDone = false;
var recursionCounter;
var timeout_message;
