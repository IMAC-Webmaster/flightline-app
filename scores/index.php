<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Round scores</title>

    <link rel="stylesheet" href="/include/css/layout.css">
    <link rel="stylesheet" href="/include/css/scores.css">
    <link rel="stylesheet" href="/libs/fontawesome/css/all.min.css"/>
    <link rel="stylesheet" href="/include/css/slider.css">
    <link rel="stylesheet" type="text/css" href="/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css"/>


    <script type="text/javascript" src="/libs/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script type="text/javascript" src="/include/js/scores.js"></script>
    <script type="text/javascript" src="/include/js/helpers.js"></script>
    <?php 
    if (isset($_GET['roundId'])) { $roundId = $_GET['roundId'];}  else $roundId = "null";
    if (isset($_GET['pilotId'])) { $pilotId = $_GET['pilotId'];}  else $pilotId = "null";
    ?>
    <script>
        $(document).ready( function () {
            var currentRound = <?php echo $roundId ?>, currentPilot = <?php echo $pilotId ?>, currentSequence = null;
            var d = getMostRecentPilotAndFlight();
            $("#pilotSel").hide();
            populateRoundSelect(currentRound);

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
                populatePilotSelect($(this).val(), null);
            });

            var reloadInterval = null;

            if (currentRound !== null) {
                populatePilotSelect(currentRound, currentPilot);
                if (currentPilot !== null) {
                    loadRoundData(currentRound, currentPilot, null);
                }
            }

            function ajaxCall() {
                if ($('#testCheck').is(':checked')) {
                    if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
                        table.ajax.reload();
                    } else {
                        loadRoundData(currentRound, currentPilot, currentSequence);
                    }
                    setTimeout(ajaxCall, 5000);
                }
            }
            $('#reload').click( function () {
                if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
                    table.ajax.reload();
                } else {
                    loadRoundData(currentRound, currentPilot, currentSequence);
                }
            });
            $('#testCheck').click( function () { 
                if ($(this).is(':checked')) {
                    reloadInterval = setTimeout(ajaxCall, 5000);
                    console.log("Enabled Autorefresh...");
                } else {
                    clearInterval(reloadInterval); 
                    console.log("Disabled Autorefresh...");
                }
            } );

        });
    </script>

</head>
<body>
    <body>
        <section class="slider-checkbox">
          <input type="checkbox" id="testCheck" />
          <label class="label" for="testCheck">Refresh (5 secs)</label>
        </section>
        <button id="reload" class='scoreboard'>Force Reload</button>
        <div id="page_container">
            <h1>Round Scores</h1>
            <h2>Class: <div class="rounddetails" id="roundClass"></div></h2>
            <h2>Round Type: <div class="rounddetails" id="roundType"></div></h2>
            <h2>Round Number: <div class="rounddetails" id="roundNum"></div></h2>
            <h2>Schedule: <div class="rounddetails" id="roundSchedule"></div></h2>
            <select id="roundSel"><option value=""></select>
            <select id="pilotSel"><option value=""></select>
            <h1 class='pilotName'>Pilot: <div class="rounddetails" id="pilotName"></div></h1>
            <table id="scores" class="datatable" width="80%">
                <thead><tr></tr></thead>
            </table>
        </div>
    </body>
    
</body>
</html>
