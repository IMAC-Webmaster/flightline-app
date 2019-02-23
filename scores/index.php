<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Datatable with dynamic headers</title>

    <link rel="stylesheet" href="../layout.css">
    <link rel="stylesheet" href="a.css">
    <link rel="stylesheet" type="text/css" href="../include/DataTables/DataTables-1.10.18/css/dataTables.bootstrap.css"/>
    <script type="text/javascript" src="../include/DataTables/jQuery-3.3.1/jquery-3.3.1.js"></script>
    <script type="text/javascript" src="../include/DataTables/DataTables-1.10.18/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="../include/DataTables/DataTables-1.10.18/js/dataTables.bootstrap.js"></script>
    <script type="text/javascript" src="/include/scores.js"></script>
    <script type="text/javascript" src="/include/helpers.js"></script>
    <script>
        $(document).ready( function () {
            var currentRound = null, currentPilot = null, currentSequence = null;
            var d = getMostRecentPilotAndFlight();
            $("#pilotSel").hide();
            populateRoundSelect();

            $('#pilotSel').change(function() {
                currentRound = $('#roundSel option:selected').val();
                currentPilot = $(this).val();
                currentSequence = null;
                loadRoundData(currentRound, currentPilot, currentSequence);
            });
            $('#roundSel').change(function() {
                if ($(this).val() === '') {
                    currentRound = null;
                    currentPilot = null;
                    currentSequence = null;
                    loadRoundData(currentRound, currentPilot, currentSequence);
                }
                populatePilotSelect($(this).val());
            });

            var reloadInterval = setInterval(ajaxCall, 5000);

            function ajaxCall() { loadRoundData(currentRound, currentPilot, currentSequence); }
            $('#reload').click( function () { loadRoundData(currentRound, currentPilot, currentSequence); } );
            $('#stoprefresh').click( function () { clearInterval(reloadInterval); } );

        });
    </script>

</head>
<body>
    <body>
        <button id="reload" class='scoreboard'>Reload</button>
        <button id="stoprefresh" class='scoreboard'>Stop Refresh</button>
        <div id="page_container">
            <h1>Round Scores</h1>
            <h2>Round Number: <div class="rounddetails" id="roundNum"></div></h2>
            <h2>Class: <div class="rounddetails" id="roundClass"></div></h2>
            <h2>Round Type: <div class="rounddetails" id="roundType"></div></h2>
            <h2>Schedule: <div class="rounddetails" id="roundSchedule"></div></h2>
            <h2>Pilot: <div class="rounddetails" id="pilotName"></div></h2>
            <select id="roundSel"><option value=""></select>
            <select id="pilotSel"><option value=""></select>
            <table id="demotable" class="datatable" width="80%">
                <thead><tr></tr></thead>
            </table>
        </div>
    </body>
    
</body>
</html>