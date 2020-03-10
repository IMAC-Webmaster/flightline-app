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
    <link rel="stylesheet" type="text/css" href="/libs/DataTables-1.10.20/css/dataTables.bootstrap4.min.css"/>
<!--    <link rel="stylesheet" type="text/css" href="/libs/datatables.net-editor/css/editor.bootstrap4.min.css"/>-->

    <script type="text/javascript" src="/libs/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="/libs/DataTables-1.10.20/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="/libs/DataTables-1.10.20/js/dataTables.bootstrap4.min.js"></script>

    <script type="text/javascript" src="/libs/jquery-validation/dist/jquery.validate.min.js"></script>
    <script type="text/javascript" src="/include/js/scores-form-validation.js"></script>
    <script type="text/javascript" src="/include/js/scores.js"></script>
    <script type="text/javascript" src="/include/js/helpers.js"></script>
<!--    <script type="text/javascript" src="/libs/datatables.net-editor/js/dataTables.editor.js"></script>-->
    <?php 
    if (isset($_GET['roundId'])) { $roundId = $_GET['roundId'];}  else $roundId = "null";
    if (isset($_GET['pilotId'])) { $pilotId = $_GET['pilotId'];}  else $pilotId = "null";
    ?>
    <script>

        $(document).ready( function () {
            // Initial load is broken..
            // On page load we need to:
            //   1. Populate the round dropdown.
            //   2. If live data is selected, then grab the latest round/pilot info and load the table.
            //   3. If we have selected a round then make sure the pilot dropdown is populated.
            //   4. If a pilot is chosen, then load the table.
            //   5. Handle refresh/autorefresh.
            var reloadInterval = null;

            $('#roundSel').change(function() {
                //if ( ($(this).val() !== '') && ($('#autoRefresh').is(':checked'))) {
                //    $('#autoRefresh').click();
                //}
                getSelection();
                processRoundSelect($(this).val());
            });

            $('#pilotSel').change(function() {
                //if ( ($(this).val() !== '') && ($('#autoRefresh').is(':checked'))) {
                //    $('#autoRefresh').click();
                //}
                getSelection();
                processPilotSelect($(this).val());
            });

            $('#reload').click( function () {
                getSelection();
                if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
                    table.ajax.reload();
                } else {
                    loadTable();
                }
            });

            $('#autoRefresh').click( function () {
                getSelection();
                if (selectionData.autoRefresh) {
                    if (selectionData.roundId !== 'Live' ||  selectionData.pilotId !== '') {
                        // Why does this need to be true?   It doesn't...
                        //show_message("Auto refresh is for live scores only...", "error");
                        //return false;
                        show_message("Auto refresh is for live scores only, but we're testing it out for all...", "error");

                    }
                    reloadInterval = setTimeout(ajaxCall, 1000);
                    console.log("Enabled Auto refresh...");
                } else {
                    clearInterval(reloadInterval); 
                    console.log("Disabled Auto refresh...");
                }
            } );

            function processPilotSelect(pilotVal) {
                if (pilotVal) {
                    loadTable();
                }
            }
            function processRoundSelect(roundVal) {
                if (roundVal === 'Live' ) {
                    helpers.emptyDropdown($('#pilotSel'), 'Choose Pilot');
                    $('#pilotSel').hide();
                    getMostRecentPilotAndFlight(true)
                } else {
                    populatePilotSelect(roundVal, null);
                }
            }

            function ajaxCall() {
                getSelection();
                if (selectionData.autoRefresh) {
                    //if ($('#roundSel option:selected').val() === '' &&  $('#pilotSel option:selected').val() === '') {
                    if ( (selectionData.roundId === 'Live') || (typeof selectionData.roundId == 'number' && selectionData.pilotId) ) {
                        if ( $.fn.DataTable.isDataTable( '#scores' ) ) {
                            table.ajax.reload();
                        } else {
                            loadTable();
                        }
                        setTimeout(ajaxCall, 1000);
                    } else {
                        $('#autoRefresh').click();
                    }
                }
            }


            function loadTable() {
                getSelection();
                loadScoreData(selectionData.roundId, selectionData.pilotId);
            }

            // Stuff to do initially...

            populateRoundSelect(<?php echo $roundId ?>);
            if (<?php echo $roundId ?> !== null) {
                populatePilotSelect(<?php echo $roundId ?>, <?php echo $pilotId ?>);
                if (<?php echo $pilotId ?> !== null) {
                    // Why load if we have both?   What about live scores?
                    loadScoreData(<?php echo $roundId ?>, <?php echo $pilotId ?>);
                }
            }
        });
    </script>

</head>
<body>
    <body>
        <section class="slider-checkbox">
          <input type="checkbox" id="autoRefresh" />
          <label class="label" for="autoRefresh">Auto Refresh</label>
        </section>
        <button id="reload" class='scoreboard'>Reload</button>

        <div class="lightbox_bg"></div>
        <div class="lightbox_container">
            <div class="lightbox_close"></div>
            <div class="lightbox_content" id="editscore">
                <h2 id="editscoreTitle">Score - </h2>
                <h3 id="figureDetails"></h3>
                <form class="form editscore" id="form_editscore" data-id="" novalidate>
                    <div class="input_container">
                        <label for="score">Score: </label>
                        <div class="field_container">
                            <input type="text" style="width: 100px;" class="text" name="score" id="score" placeholder="Score" value="">
                        </div>
                    </div>
                    <div class="input_container">
                        <label for="comment">Judge Comment: </label>
                        <div class="field_container">
                            <input type="text" style="width: 300px;" class="text" name="comment" id="comment" placeholder="Judge's Comment" value="">
                        </div>
                    </div>
                    <div class="input_container">
                        <label for="cdcomment">CD Comment: </label>
                        <div class="field_container">
                            <input type="text" style="width: 300px;" class="text" name="cdcomment" id="cdcomment" placeholder="CD's Comment" value="">
                        </div>
                    </div>
                    <div class="input_container">
                        <label for="breakFlagSlider">Break: </label>
                        <div class="field_container">
                            <section class="slider-checkbox" style="padding-bottom: 22px;">
                                <input type="checkbox" id="breakFlagSlider"/>
                                <label class="label" for="breakFlagSlider"></label>
                            </section>
                            <input type="hidden" name="breakFlag">
                        </div>
                    </div>

                    <input type="hidden" name="sheetId">
                    <input type="hidden" name="figureNum">
                    <div class="button_container">
                        <button type="submit" id="delete" value="delete">Delete Score</button>
                        <button type="submit" id="save" value="save">Save</button>
                        <input type="hidden" name="button">
                    </div>
                </form>
            </div>

        </div>

        <div id="page_container">
            <h1>Round Scores</h1>
            <h2>Class: <div class="rounddetails" id="roundClass"></div></h2>
            <h2>Round Type: <div class="rounddetails" id="roundType"></div></h2>
            <h2>Round Number: <div class="rounddetails" id="roundNum"></div></h2>
            <h2>Schedule: <div class="rounddetails" id="roundSchedule"></div></h2>
            <select id="roundSel" style="display: none;"><option value=""></select>
            <select id="pilotSel" style="display: none;"><option value=""></select>
            <h1 class='noData' id='noDataHeader'><div id='noData'>There is no data available yet.</div></h1>
            <h1 class='pilotName' id='pilotNameHeader'>Pilot: <div class="rounddetails" id="pilotName"></div></h1>
            <table id="scores" class="datatable">
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
