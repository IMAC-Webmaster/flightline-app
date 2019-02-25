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
    console.log("Got the info from the round data.  We need:");
    console.log("  " + (roundInfo['sheetCount'] + 2 ) + " colums for " + round.sequences  + " sheets per judge from " + roundInfo['judgeCount'] + " judges.");
    console.log("Building datatable data.");
    var dataTable = {columns:[ {title: "Num.", data:"num"}, {title: "Figure",  data:"fig"}], data:[]};
    // Lets do this for each pilot in the data!
    var judgeCols = {};
    for (var pIdx in round.pilots) {
        dataTable.data.push(new Array());
        for (var fIdx in round.schedule.figures) {
            var myDataRow = {
                num: round.schedule.figures[fIdx].figureNum, 
                fig: round.schedule.figures[fIdx].shortDesc
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

function handleAjaxResponse() {
    var str;
   
    if ( $.fn.DataTable.isDataTable( '#demotable' ) ) {
        table.clear();
        table.destroy();
        $("#demotable thead tr").empty();
    }

    // Iterate each column and print table headers for Datatables
    $.each(data.columns, function (k, colObj) {
        //str = '<th>' + colObj.name + '</th>';
        str = '<th></th>';
        $(str).appendTo('#demotable>thead>tr');
    });

    table = $('#demotable').DataTable({
        "data": data.data,
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
    
}

function renderScoreContainer (td, cellData, rowData, row, col) {
    if (typeof cellData === "object") {
        if (cellData.breakFlag === 1) {
            $(td).addClass("break");
        }
        if (cellData.comment !== null) {
            // Add comment here!
        }
        if ((cellData.scoreTime + 30) > +new Date() ) { //Is this less than 30 seconds old?
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
    var url = '/data.php?job=get_latest_round_and_pilot';
    result = null;
    xhr = $.ajax(url)
        .done(function()
        {
            result = JSON.parse(xhr.responseText);
            latestFlightInfo = result.data;
        })
        .fail();
}

function loadRoundData(roundId, pilotId, sequenceNum) {
    // If they selected nothing, then just clear table, and clear selects...
    if (roundId === null || roundId === '') {
        if ( $.fn.DataTable.isDataTable( '#demotable' ) ) {
            table.clear();
            table.destroy();
            $("#demotable thead tr").empty();
        }
        $('#pilotSel').hide();
        getMostRecentPilotAndFlight();
        loadRoundData(latestFlightInfo.roundId, latestFlightInfo.pilotId, null);
        return;
    }
    
    var url = '/data.php?job=get_scores_for_round&roundId=' + roundId;

    jqxhr = $.ajax(url)
        .done(function(){ 
            result = JSON.parse(jqxhr.responseText);
            parseRoundData(result.data);
            var p = getPilotIndexFromId(pilotId, result.data.pilots);
            if (p === null) {
                return;
            }
            // Fill empty pilot sheets here (based on whats in result.data.datable_columns)
            for (var dti in result.data.pilots[p].datatable) {
                for (var col in result.data.datatable_columns) {
                if (typeof result.data.pilots[p].datatable[dti][result.data.datatable_columns[col].data] === "undefined") {
                        // We need to add it.  (A blank one)
                        result.data.pilots[p].datatable[dti][result.data.datatable_columns[col].data] = {figureNum: null, scoreTime: null, breakFlag: null, score: 'No Score', comment: null};
                    }
                }
            }
            
            data.data = result.data.pilots[p].datatable;
            data.sheets = result.data.pilots[p].sheets;
            data.columns = result.data.datatable_columns;
            for (col in data.columns) {
                // Iterate over the columns and add the sheet data.
                if (data.columns[col].data === "num" || data.columns[col].data === "fig") {
                    ;
                } else {
                    for (var sheet in data.sheets) {
                        if (data.sheets[sheet].sequenceNum === data.columns[col].seqnum && data.sheets[sheet].judgeNum === data.columns[col].judgenum) {
                            // This is the sheet!   Delete the scores to save some memory...
                            delete data.sheets[sheet].scores;
                            data.columns[col]["sheet"] = data.sheets[sheet];
                            if (data.sheets[sheet].mppFlag === 1) {
                                data.columns[col]["className"] = "score mpp";
                            }
                        }
                    }
                }
            }
            data.pilot = {
                active: result.data.pilots[p].active,
                fullName: result.data.pilots[p].fullName,
                airplane: result.data.pilots[p].airplane,
                freestyle: result.data.pilots[p].freestyle,
                imacClass: result.data.pilots[p].imacClass,
                in_customclass1: result.data.pilots[p].in_customclass1,
                in_customclass2: result.data.pilots[p].in_customclass2,
                pilotId: result.data.pilots[p].pilotId,
                pilotPrimaryId: result.data.pilots[p].pilotPrimaryId
            };
            data.round = {
                description: result.data.description,
                imacClass: result.data.imacClass,
                imacType: result.data.imacType,
                phase: result.data.phase,
                roundId: result.data.roundId,
                roundNum: result.data.roundNum,
                schedId: result.data.schedId,
                sequences: result.data.sequences,
                status: result.data.status               
            };
            handleAjaxResponse();
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

function populateRoundSelect() {
    var xhr;
    var url = '/data.php?job=get_rounds';
    xhr = $.ajax(url)
        .done(function()
        {
            result = JSON.parse(xhr.responseText);
            helpers.buildDropdown( helpers.cleanData("Round", result.data), $('#roundSel'), 'Live Data');
        })
        .fail();
    
}

function populatePilotSelect(roundId) {
    var xhr;
    var url = '/data.php?job=get_round_pilots&roundId=' + roundId;
    $("#pilotSel").show();
    xhr = $.ajax(url)
        .done(function()
        {
            result = JSON.parse(xhr.responseText);
            helpers.buildDropdown( helpers.cleanData("Pilot", result.data), $('#pilotSel'), 'Choose Pilot');
        })
        .fail();
    
}

var data = {data: [], columns: [], pilot:[], round:[]};
var jqxhr, table, latestFlightInfo;
