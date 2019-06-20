<?php
/**
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="google" content="notranslate">
    <meta http-equiv="Content-Language" content="en">
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
            var reloadInterval = null;

            $('#roundSel').change(function() {
                if ($(this).val() === '') {
                    helpers.emptyDropdown($('#pilotSel'), 'Choose Pilot');

                    $('#pilotSel').hide();
                    loadTable();
                } else {
                    if ($('#autoRefresh').is(':checked')) {
                        $('#autoRefresh').click();
                    }
                    populatePilotSelect($(this).val(), null);
                }
            });

            $('#pilotSel').change(function() {
                if ( ($(this).val() !== '') && ($('#autoRefresh').is(':checked'))) {
                    $('#autoRefresh').click();
                }
                loadTable();
            });

            $('#reload').click( function () {
                if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
                    table.ajax.reload();
                } else {
                    loadTable();
                }
            });

            $('#autoRefresh').click( function () {

                if ($('#autoRefresh').is(':checked')) {
                    if ($('#roundSel option:selected').val() !== '' ||  $('#pilotSel option:selected').val() !== '') {
                        show_message("Auto refresh is for live scores only", "error");
                        return false;
                    }
                    reloadInterval = setTimeout(ajaxCall, 5000);
                    console.log("Enabled Auto refresh...");
                } else {
                    clearInterval(reloadInterval); 
                    console.log("Disabled Auto refresh...");
                }
            } );

            function ajaxCall() {
                if ($('#autoRefresh').is(':checked')) {
                    if ($('#roundSel option:selected').val() === '' &&  $('#pilotSel option:selected').val() === '') {
                        if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
                            table.ajax.reload();
                        } else {
                            loadTable();
                        }
                        setTimeout(ajaxCall, 5000);
                    } else {
                        $('#autoRefresh').click();
                    }
                }
            }

            function initialLoad() {
                if (initialRoundLoadDone) {
                    loadTable();
                } else {
                    reloadInterval = setTimeout(initialLoad, 100);
                }
            }

            function loadTable() {
                loadRoundData($('#roundSel option:selected').val(), $('#pilotSel option:selected').val(), null);
            }

            // Stuff to do initially...

            if (<?php echo $roundId ?> === null) {
                populateRoundSelect(<?php echo $roundId ?>);
            } else {
                populatePilotSelect(<?php echo $roundId ?>, <?php echo $pilotId ?>);
                if (<?php echo $pilotId ?> !== null) {
                    loadRoundData(<?php echo $roundId ?>, <?php echo $pilotId ?>, null);
                }
            }

            initialLoad();

        });
    </script>

</head>
<body>
    <body>
        <section class="slider-checkbox">
          <input type="checkbox" id="autoRefresh" />
          <label class="label" for="autoRefresh">Auto Refresh (5 secs)</label>
        </section>
        <button id="reload" class='scoreboard'>Reload</button>
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
        <noscript id="noscript_container">
            <div id="noscript" class="error">
                <p>JavaScript support is needed to use this page.</p>
            </div>
        </noscript>

        <div id="message_container">
            <div id="message" class="success">
                <p>This is a success message.</p>
            </div>
        </div>

        <div id="loading_container">
            <div id="loading_container2">
                <div id="loading_container3">
                    <div id="loading_container4">
                        Loading, please wait...
                    </div>
                </div>
            </div>
        </div>
    </body>
    
</body>
</html>
