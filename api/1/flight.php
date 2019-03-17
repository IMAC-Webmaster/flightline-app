<?php

require '../../libs/flight/Flight.php';
include_once '../../include/functions.php';
include_once 'api_functions.php';
ini_set("display_errors", 0);

// Config
$dbfile = '../../db/flightline.db';
$apiurl = "/api/1";


// Init...
$db = null;
if (isset($_REQUEST['jsondebug']))  { $jsondebug = $_REQUEST['jsondebug']; } else { $jsondebug = false; }

$resultObj = createEmptyResultObject();
$resultObj["requestTime"] = time();

if (dbConnect($dbfile) === false) {
    // Do some error handling...
    header("HTTP/1.1 500 Internal Server Error");
    echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">";
    echo "<html><head>";
    echo "<title>500 Internal Server Error</title>";
    echo "</head><body>";
    echo "<h1>Internal Server Error</h1>";
    echo "<p>Could not connect to the DB.</p>";
    echo "</body></html>";
    exit();
}

/**************
 *
 * /rounds                      - rounds interface.
 * /rounds(/[0-1]+)?/results    - results interface.  Full result info for round(s).
 * /rounds/scores               - scores interface.  Basic data with scoresheets.
 * /pilots                      - pilots interface.
 * /flights                     - flights interface.
 * /sheets                      - sheets interface.
 * /users                       - users interface.
 *
 **************/



Flight::route ("/rounds/@id:[0-9]+", function($id) {
    global $resultObj;
    getRound($resultObj, array(
        "roundId" => $id,
        "imacClass" => null,
        "imacType" => null,
        "roundNum" => null
    ));
});

Flight::route ("/rounds/@class:[A-Za-z]+/@type:[A-Za-z]+/@roundNum:[0-9]+", function($class, $type, $roundNum) {
    global $resultObj;
    getRound($resultObj, array(
        "roundId" => null,
        "imacClass" => $class,
        "imacType" => $type,
        "roundNum" => $roundNum
    ));
});

Flight::route ("/rounds/Freestyle/@roundNum:[0-9]+", function($roundNum) {
    global $resultObj;
    getRound($resultObj, array(
        "roundId" => null,
        "imacClass" => null,
        "imacType" => "Freestyle",
        "roundNum" => $roundNum
    ));
});

Flight::route ("/rounds/@roundId:[0-9]+/pilots/@pilotId:[0-9]+", function($roundId, $pilotId) {
    global $resultObj;
    getPilotsForRound($resultObj, array(
        "roundId" => $roundId,
        "pilotId" => $pilotId
    ));
});

Flight::route ("/rounds/@roundId:[0-9]+/pilots", function($roundId) {
    global $resultObj;
    getPilotsForRound($resultObj, array(
        "roundId" => $roundId
    ));
});

Flight::route ("/rounds/@roundId:[0-9]+/results", function($roundId) {
    global $resultObj;
    getFlownRound($resultObj, $roundId);
});

Flight::route ("/flights/@flightId:[0-9]+", function($flightId) {
    global $resultObj;
    getSheetsForFlight($resultObj, $flightId);
});

Flight::route ("/pilots/@pilotId:[0-9]*", function($pilotId) {
    global $resultObj;
    getPilot($resultObj, $pilotId);
});

Flight::route ("/rounds/", function() { global $resultObj; getRounds($resultObj); });
Flight::start();

dbDisconnect();
unset($db);

// Convert PHP array to JSON array
if ($jsondebug === false || $jsondebug === "false") {
    $json_data = json_encode($resultObj, null);
} else {
    $json_data = json_encode($resultObj, JSON_PRETTY_PRINT);
}
header('Content-Type: application/json');
print $json_data;