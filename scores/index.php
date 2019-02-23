<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Datatable with dynamic headers</title>

    <link rel="stylesheet" href="../layout.css">
    <link rel="stylesheet" href="a.css">
    <link rel="stylesheet" type="text/css" href="../include/DataTables/DataTables-1.10.18/css/dataTables.bootstrap.css"/>
    <script type="text/javascript" src="../include/DataTables/jQuery-3.3.1/jquery-3.3.1.js"></script>
    <script type="text/javascript" src="../include/DataTables/DataTables-1.10.18/js/dataTables.bootstrap.js"></script>
    <script type="text/javascript" src="../include/DataTables/DataTables-1.10.18/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="/include/liveresults.js"></script>
    <script type="text/javascript" src="/include/helpers.js"></script>
    <script>
        $(document).ready( function () {
            $("#pilotSel").hide();
            populateRoundSelect();

            $('#pilotSel').change(function() {
                loadRoundData($('#roundSel option:selected').val(), $(this).val(), null);
            });
            $('#roundSel').change(function() {
                populatePilotSelect($(this).val());
            });
    
        });
    </script>

</head>
<body>
    <body>
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
