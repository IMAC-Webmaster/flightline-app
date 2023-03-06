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


/**************
 * List of things still to do..
 *
 *  ToDo: Flightline: Delete button in score adjustment should not validate.
 *  ToDo: Flightline: Do pilot flight ordering.
 *  ToDo: Update Datatables and add extensions:
 *      FixedHeader (Puts the header at the top of the page when scrolling.
 *      FixedColums (Future: left/right scrolling keeps certain columns fixed).
 *      RowGroup (Future: group by one of the columns (Knowns, Unknowns, Class etc)...
 *      SearchPanes (Future: quick filters - a bit like RowGroup I guess...)
 *      RowReorder (drag/drop flight ordering...)
 *  ToDo: remove all of the !important CSS hacks.
 *  ToDO: populatePilotSelect is broken for freestyle...
 *  Done: Fix boostrap CSS issue...  Including breaks the style...
 *
 ****************/
?>
<!doctype html>
<html lang="en" dir="ltr">
  <head>
    <title>Score! Flightline Controller</title>
      <meta charset="UTF-8">
      <meta name="google" content="notranslate">
      <meta http-equiv="Content-Language" content="en">
      <meta name="viewport" content="width=1000, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">

      <!--  This one is the bundle. <link rel="stylesheet" type="text/css" href="/libs/dataTables.min.css"/> -->
      <link rel="stylesheet" type="text/css" href="/libs/Bootstrap-4-4.1.1/css/bootstrap.min.css"/>
      <link rel="stylesheet" type="text/css" href="/libs/DataTables-1.10.20/css/dataTables.bootstrap4.min.css"/>
      <link rel="stylesheet" type="text/css" href="/libs/Editor-1.9.2/css/editor.bootstrap4.min.css"/>
      <link rel="stylesheet" type="text/css" href="/libs/RowReorder-1.2.6/css/rowReorder.bootstrap4.min.css"/>
      <!-- Remove those above if you want to use the one bundled file... -->

      <link rel="stylesheet" href="/include/css/layout.css"/>
      <link rel="stylesheet" href="/libs/fontawesome/css/all.min.css"/>


      <script type="text/javascript" src="/libs/jquery/dist/jquery.min.js"></script>
      <script type="text/javascript" src="/libs/datatables.min.js"></script>
      <!--  This one is the bundle. <script type="text/javascript" src="/libs/datatables.min.js"></script>  -->
      <script type="text/javascript" src="/libs/Bootstrap-4-4.1.1/js/bootstrap.min.js"></script>
      <script type="text/javascript" src="/libs/DataTables-1.10.20/js/jquery.dataTables.min.js"></script>
      <script type="text/javascript" src="/libs/DataTables-1.10.20/js/dataTables.bootstrap4.min.js"></script>
      <script type="text/javascript" src="/libs/Editor-1.9.2/js/dataTables.editor.min.js"></script>
      <script type="text/javascript" src="/libs/Editor-1.9.2/js/editor.bootstrap4.min.js"></script>
      <script type="text/javascript" src="/libs/RowReorder-1.2.6/js/dataTables.rowReorder.min.js"></script>
      <!-- Remove those above if you want to use the one bundled file... -->
      <script type="text/javascript" src="/libs/jquery-validation/dist/jquery.validate.min.js"></script>
      <script type="text/javascript" src="/include/js/webapp.js"></script>
      <script type="text/javascript" src="/include/js/helpers.js"></script>
  </head>
  <body>

    <div id="page_container">
        <h1>Score! Flightline controller</h1>
        <button type="button" class="button" id="do_auth">Login</button>
        <button type="button" class="button" id="livescores">Live Scores</button>
        <button type="button" class="button" id="add_round">Add round</button>
        <table class="datatable clickable" id="table_roundlist">
        <thead>
        <tr>
            <th>RoundNum</th>
            <th>Class</th>
            <th>Round Type</th>
            <th>Round Num.</th>
            <th>Schedule</th>
            <th>Schedule ID</th>
            <th>Sequences</th>
            <th>Phase</th>
            <th>Status</th>
            <th>Functions</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
        </table>
    </div>

    <div class="lightbox_bg"></div>
    <div class="lightbox_container">
        <div class="lightbox_close"></div>
      <div class="lightbox_content" id="addround">
        <h2>Add Round</h2>
        <form class="form add" id="form_round" data-id="" novalidate>
          <div class="input_container">
            <label for="imacClass">Class: </label>         
            <div class="field_container">
              <label class='error' id="class-error"></label>
              <select name="imacClass" id="imacClass">
                <option value="">Please choose a class</option>
                <option value="Basic">Basic</option>
                <option value="Sportsman">Sportsman</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
                <option value="Unlimited">Unlimited</option>
              </select>
              <label id="hidden_imacClass" class="hiddenlabel">Any</label>  
            </div>
          </div>
          <div class="input_container">
            <label for="imacType">Round Type: <span class="required">*</span></label>
            <div class="field_container">
              <label class='error' id="imacType-error"></label>
              <select name="imacType" id="imacType">
                <option value="Known">Known</option>
                <option value="Unknown">Unknown</option>
                <option value="Freestyle">Freestyle</option>
              </select>
            </div>
          </div>
          <div class="input_container">
            <label for="roundNum">Round Number: <span class="required">*</span></label>
            <div class="field_container">
              <label class='error' id="roundNum-error"></label>
              <input type="text" class="text" name="roundNum" id="roundNum" value="" required>
            </div>
          </div>
          <div class="input_container">
            <label for="schedule">Schedule: <span class="required">*</span></label>
            <div class="field_container">
              <label class='error' id="schedule-error"></label>
              <select type="text" name="schedule" id="schedule">
                <option value="">Please choose a schedule</option>
              </select>
            </div>
          </div>
          <div class="input_container">
            <label for="sequences">Number of sequences: <span class="required">*</span></label>
            <div class="field_container">
              <label class='error' id="sequences-error"></label>
              <select name="sequences" id="sequences">
                  <option value="1">Single</option>
                  <option value="2">Double</option>
              </select>
              <label id="hidden_sequences" class="hiddenlabel">Single</label>  
            </div>
          </div>
          <div class="button_container">
            <button type="submit">Add Round</button>
          </div>
        </form>
      </div>


        <div class="lightbox_content" id="login">
            <h2>Login</h2>
            <form class="form login" id="form_login" data-id="" novalidate>
                <div class="input_container">
                    <label for="username">Username: </label>
                    <div class="field_container">
                        <label class='error' id="username-error"></label>
                        <input type="text" class="text" name="username" id="username" placeholder="Username" value="" required>
                    </div>
                </div>
                <div class="input_container">
                    <label for="password">Password: </label>
                    <div class="field_container">
                        <label class='error' id="password-error"></label>
                        <input type="password" class="text" name="password" id="password" value="" required>
                    </div>
                </div>

                <div class="button_container">
                    <button type="submit">Log In</button>
                </div>
            </form>
        </div>

    </div>

    <div class="roundbox_bg"></div>
    <div class="roundbox_container">
      <div class="roundbox_close"></div>
      <div class="roundbox_content">
        <h2>Round Details</h2>
        <div class="rounddetails_container">
          <div class="field_container">Class:</div><div class="field_details" id="class-details">Some Class</div>
          <div class="field_container">Round:</div><div class="field_details" id="roundnum-details">Round Num</div>
          <div class="field_container">Type:</div><div class="field_details" id="roundtype-details">Known</div>
          <div class="field_container">Schedule:</div><div class="field_details" id="roundsched-details"></div>
          <div class="field_container">Next Flight:</div><div class="field_details" id="nextflight-details"></div>
          <div class="roundinstructions_container">
           Some instructions here...
          </div>
          <div class="roundpilots_container">
            <table class="datatable" id="table_pilotlist">
              <thead>
                <tr>
                  <th>Pilot ID</th>
                  <th>Pilot Name</th>
                  <th>Flight ID</th>
                  <th>Next Flight</th>
                  <th>Notaumatic Hint</th>
                  <th>Class ID</th>
                </tr>
              </thead>
              <tbody>
              </tbody>
            </table>
          </div>
        </div>
        <form class="form add" id="form_roundview" data-id="" novalidate>
        </form>
        
      </div>
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
</html>