<?php
    include_once("data.php");
    $scores = json_decode($json);
    $cols = 0;
    foreach($scores->data as $score) {
        if ($cols < count((array)$score)) {
            $cols = count((array)$score);
        }
    }


?>
<html>
    <head>
        <link rel="stylesheet" href="a.css">
        <link rel="stylesheet" href="/layout.css">
        <script type="text/javascript" charset="utf-8" src="/include/DataTables/jQuery-1.12.3/jquery-1.12.3.js"></script>
        <script type="text/javascript" charset="utf-8" src="/include/DataTables/datatables.js"></script> 
        <script>
            $(document).ready( function () {
                $.fn.dataTable.ext.errMode = 'none';
                var reloadInterval = setInterval(ajaxCall, 5000);

                var table = $('#example').on( 'xhr.dt', function ( e, settings, processing ) {
                    if (typeof settings.json != 'undefined') {
                        checkColumns(settings.aoColumns, settings.json.data);
                    }
                })
                .DataTable( {
                    ajax: 'jsondata.php',
                    dom: 't',
                    paging: false,
                    columnDefs: [ {
                      targets: "_all",
                      render: function ( data, type, row ) { return (typeof data === "object") ? renderScore(data, type, row) : data; }
                    } ],
                    columns: [
                        { data: "Number", className: "num"},
                        { data: "Figure", className: "desc"}<?php
                            for ($i = 2 ; $i < $cols ; $i++ ) {
                                echo ",\n                    {data: \"Judge" . ($i - 1) . "\", className: \"score\" }";
                            }
                            echo "\n";
                        ?>
                    ],
                    processing: false
                } );

                function renderScore (score, type, row) {
                    if (typeof score.features !== "undefined") {
                        var classes = "score";
                        for (var i = 0, len = score.features.length; i < len; i++) {
                          //console.log ("Adding class: " + score.features[i]);
                          classes = classes + " " + score.features[i];
                        }
                        //console.log (score.value + " " + type + " " + row);
                    }
                    return ("<div class='" + classes + "'>" + score.value + "</div>");
                }
                
                
                
                function checkColumns (dtArr, jsData) {
                    var maxCol = 0;
                    jsData.forEach(function(element) {
                          var elementLength = Object.keys(element).length;
                          if (maxCol < elementLength) {
                              maxCol = elementLength;
                          }
                    });
                    if (maxCol != dtArr.length) {
                        window.location.reload();
                    }
                }

                function ajaxCall() {
                    table.ajax.reload();
                }


                $('#reload').click( function () {
                    table.ajax.reload();
                } );
                
                $('#stoprefresh').click( function () {
                    clearInterval(reloadInterval);
                } );
                
            } );
        </script>
        <meta charset=utf-8 />
        <title>Test</title>
    </head>
    <body>
        <button id="reload" class='scoreboard'>Reload</button>
        <button id="stoprefresh" class='scoreboard'>Stop Refresh</button>
        <div id="page_container">
            <h1>Live Scores</h1>
            <h2>Pilot: Dan Carroll</h2>
            <h2>Round: 3 Known</h2>
            <h2>Class: Intermediate</h2>
            <table id="example" class="display" width="70%">
            <thead>
                <tr>
                    <th class='desc'>Num.</th>
                    <th class='desc'>Figure</th>
                    <?php
                    // Ok, lets get the JSON object that DT will call later and have a look,..

                    for ($i = 2 ; $i < $cols ; $i++ ) {
                        echo "                    <th>Judge " . ($i - 1) . "</th>\n";
                    }
                ?>
                </tr>
            </thead>
            </table>
        </div>
    </body>
</html>