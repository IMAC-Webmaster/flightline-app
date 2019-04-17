function parseRoundData(round) {
    // round.* is from round table...
    // round.schedule is the schedule table.
    // round.schedule.figures is the figure table
    // round.pilots is the pilots table.
    // round.pilots.sheets are the pilots sheets for the round (double round with 2 judges = 4 sheets!)
    // round.pilots.sheets.scores are the scores!

    var roundInfo = gatherInfo(round);
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
    var dataTable = {columns:[ {title: "Num.", data:"num"}, {title: "Figure",  data:"fig"}], data:[]};
    // Lets do this for each pilot in the data!
    var judgeCols = {};
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
                var colName = " Seq " + colSeqNum + " Judge " + colJudgeNum;
                var colData = "S" + colSeqNum + "J" + colJudgeNum;
                myDataRow[colData] = round.pilots[pIdx].sheets[sIdx].scores[fIdx];
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

function drawTableInfo() {
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

    console.log("Doing pilot: " + data.pilot.fullName + " T: " + pilotId+ " L: " + lastFlightInfo.pilotId );
    if (liveResults === true && (lastFlightInfo.roundId != roundId || lastFlightInfo.pilotId != pilotId) ) {
        if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
            table.clear();
            table.destroy();
            $("#scores thead tr").empty();
        }
    } else {
        if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
            console.log("Not destroying table...");
            drawTableInfo();
            return;
        }
        console.log("Creating Datatable...");
    }

    // Iterate each column and print table headers for Datatables
    $.each(data.columns, function (k, colObj) {
        //str = '<th>' + colObj.name + '</th>';
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
            //"url": "/api/1/rounds/" + roundId + "/pilots/" + pilotId + "/   // Not sure which is a better API pattern
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
            { "targets": [0, 1],    className: "desc" },
            { "targets": "_all",    className: "score" },
            { "targets": "_all",    createdCell: function (td, cellData, rowData, row, col) { renderScoreContainer(td, cellData, rowData, row, col); } },
            { "targets": "_all",    render: function ( data, type, row ) { return (typeof data === "object") ? renderScore(data, type, row) : data; } }
        ],
        "fnInitComplete": function () {
            // Event handler to be fired when rendering is complete (Turn off Loading gif for example)
            console.log('Datatable rendering complete');
        }
    });
    drawTableInfo();
}

function renderScoreContainer (td, cellData, rowData, row, col) {
    if ((typeof cellData === "object") && (cellData !== null)) {
        if (cellData.breakFlag === 1) {
            $(td).addClass("break");
        }
        if (cellData.comment !== null) {
            // Add comment here!
        }
        var dateNow = +new Date();
        if ((cellData.scoreTime + 30) > (dateNow / 1000) ) { //Is this less than 30 seconds old?
            $(td).addClass("new");
        }
    }
}

function renderScore (d, t, r) {
    var classes = "score";
    if (typeof d.features !== "undefined") {
        for (var i = 0, len = d.features.length; i < len; i++) {
          classes = classes + " " + d.features[i];
        }
    }
    if (d.breakFlag === 1) {
        classes = classes + " break";
    }
    return ("<div class='" + classes + "'>" + d.score + "</div>");
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

function getMostRecentPilotAndFlight() {
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
        })
        .fail(function()
        {
            loadingRecentFlightInfo = 'FAILED';
            latestFlightInfo = {latestScoreTime:"", pilotId:"", roundId:""};
        });
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
            if (typeof resultdata.pilots[p].datatable[dti][resultdata.datatable_columns[col].data] === "undefined") {
                // We need to add it.  (A blank one)
                resultdata.pilots[p].datatable[dti][resultdata.datatable_columns[col].data] = {figureNum: null, scoreTime: null, breakFlag: null, score: 'No Score', comment: null};
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
                    delete tabledata.sheets[sheet].scores;
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

function processAJAXResposeForDT(result) {

    // Number 1...   Are we looking at live data?
    // If so, get the round details from the latest flight array.

    let roundId = $('#roundSel option:selected').val();
    let pilotId = $('#pilotSel option:selected').val();
    let seqNum = null;       // Not supported yet.
    let liveResults = false;  // Are we operating from liveData or did the user select something.

    if ( (roundId === null || roundId === '') || (pilotId === null || pilotId === '')) {

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

function loadRoundData(roundId, pilotId, sequenceNum) {
    // If they selected nothing, then just clear table, and clear selects...
    if (roundId === null || roundId === '' || pilotId === null || pilotId === '') {
        switch(loadingRecentFlightInfo) {
            // ToDo: Change if block for switch...
        }
        if (loadingRecentFlightInfo === true) {
            // We're not done...
            console.log ("Recursion at: " + recursionCounter++);
            setTimeout(function()  { loadRoundData(roundId, pilotId, sequenceNum); }, 30);
            return;
        } else if (loadingRecentFlightInfo === false) {
            // We're not loading, but we need it...
            //lastFlightInfo = latestFlightInfo;  // Should be set from last time...
            recursionCounter = 1;
            //show_message("Geting the latest flight info.", "success");
            show_loading_message();
            $('#pilotSel').hide();
            getMostRecentPilotAndFlight();
            loadRoundData(roundId, pilotId, sequenceNum);
            return;
        } else if (loadingRecentFlightInfo === 'DONE') {
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
        } else if (loadingRecentFlightInfo === 'FAILED') {
            console.log("ERROR: Could not get flight info.")
            hide_loading_message();
            show_message("Could not get latest flight info.", "error");
            return;
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
        });
}

function populateRoundSelect(selectedRound) {
    var xhr;
    var url = '/data.php?job=get_rounds';
    xhr = $.ajax(url)
        .done(function()
        {
            result = JSON.parse(xhr.responseText);
            helpers.buildDropdown( helpers.cleanData("Round", result.data), $('#roundSel'), 'Live Data', selectedRound);
            initialRoundLoadDone = true;
        })
        .fail();
    
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


function populatePilotSelect(roundId, selectedPilot) {
    var xhr;
    var url = '/data.php?job=get_round_pilots&roundId=' + roundId;
    $("#pilotSel").show();
    xhr = $.ajax(url)
        .done(function()
        {
            result = JSON.parse(xhr.responseText);
            helpers.buildDropdown( helpers.cleanData("Pilot", result.data), $('#pilotSel'), 'Choose Pilot', selectedPilot);
        })
        .fail();
}

var data = {data: [], columns: [], pilot:[], round:[]};
var lastColumnCount = 0;
var jqxhr, table, latestFlightInfo = {latestScoreTime:"", pilotId:"", roundId:""}, lastFlightInfo, destroyTable = true;
var initialRoundLoadDone = false;
var loadingRecentFlightInfo = false;
var recursionCounter;
var timeout_message;
