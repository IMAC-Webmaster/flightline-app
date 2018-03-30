<!doctype html>
<html lang="en" dir="ltr">
  <head>
    <title>jQuery SCRUD system</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1000, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <link rel="stylesheet" href="/include/css/font-awesome.min.css">
    <link rel="stylesheet" href="layout.css">
    <script charset="utf-8" src="/include/DataTables/jQuery-1.12.3/jquery-1.12.3.js"></script>
    <script type="text/javascript" src="/include/DataTables/datatables.js"></script> 
    <script charset="utf-8" src="/include/jquery-validation-1.17.0/jquery.validate.js"></script>
    <script charset="utf-8" src="webapp.js"></script>
  </head>
  <body>

    <div id="page_container">

      <h1>IMAC Contest Flightline controller</h1>

      <button type="button" class="button" id="add_round">Add round</button>

      <table class="datatable" id="table_roundlist">
        <thead>
          <tr>
            <th>Class</th>
            <th>Round Type</th>
            <th>Round Num.</th>
            <th>Schedule Name</th>
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
      <div class="lightbox_content">
        <h2>Add Round</h2>
        <form class="form add" id="form_round" data-id="" novalidate>
          <div class="input_container">
            <label for="class">Class: </label>         
            <div class="field_container">
              <label class='error' id="class-error"></label>
              <select name="class" id="class">
                <option value="">Please choose a class</option>
                <option value="Basic">Basic</option>
                <option value="Sportsman">Sportsman</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
                <option value="Unlimited">Unlimited</option>
              </select>
              <label id="hiddenclass" class="hiddenlabel">Any</label>  
            </div>
          </div>
          <div class="input_container">
            <label for="type">Round Type: <span class="required">*</span></label>
            <div class="field_container">
              <label class='error' id="type-error"></label>
              <select name="type" id="type">
                <option value="Known">Known</option>
                <option value="Unknown">Unknown</option>
                <option value="Freestyle">Freestyle</option>
              </select>
            </div>
          </div>
          <div class="input_container">
            <label for="roundnum">Round Number: <span class="required">*</span></label>
            <div class="field_container">
              <label class='error' id="roundnum-error"></label>
              <input type="text" class="text" name="roundnum" id="roundnum" value="" required>
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
              <label id="hiddensequences" class="hiddenlabel">Single</label>  
            </div>
          </div>
          <div class="button_container">
            <button type="submit">Add Round</button>
          </div>
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