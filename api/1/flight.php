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

require '../../libs/flight/Flight.php';
include_once '../../include/functions.php';
include_once 'api_functions.php';
ini_set("display_errors", 0);

$secretfile = 'secret.php';

// Include the secret file, or create it if it does not exist...
if (!file_exists($secretfile)) {
    $fh = fopen($secretfile, 'w');
    $secret = random_str(32);
    fwrite($fh, "<?php\n\$jwtkey = '$secret';\n");
    fclose($fh);
}
include_once $secretfile;

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

Flight::route ('/jsonblah/*', function($route) {
    global $resultObj;
    error_log("INFO: in /jsonblah.");

    apiJSONTest($resultObj, $route->splat);
}, true);

Flight::route ('/jsonblah', function() {
    global $resultObj;
    error_log("INFO: in /jsonblah2.");
    apiJSONTest($resultObj);
});

Flight::route ("GET /info", function() {
    global $resultObj;
    getFlightLineDetails($resultObj);
});

Flight::route ("DELETE /auth", function() {
    global $resultObj;
    authLogoff($resultObj);
});

Flight::route ("GET /auth/@role", function($role) {
    global $resultObj;
    authHasRole($resultObj, $role);
});

Flight::route ("GET /auth", function() {
    global $resultObj;
    // Just get the current auth object (JS does not have access to the cookies).
    authGetPayload($resultObj);
});

Flight::route ("POST /auth", function() {
    global $resultObj;
    $authData = @json_decode((($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}"), true);
    // authData should be an array with keys username and password...

    authLogon($resultObj, $authData);
});

Flight::route ("POST /rounds", function() {
    global $resultObj;
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        addRound($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add a round.";
    }
});

Flight::route ("GET /rounds", function() {
    global $resultObj;
    getRounds($resultObj);
});

Flight::route ("/rounds/@id:[0-9]+", function($id) {
    global $resultObj;
    getRound($resultObj, array(
        "roundId" => $id,
        "imacClass" => null,
        "imacType" => null,
        "roundNum" => null
    ));
});

Flight::route ("/rounds/@id:[0-9]+/sheets", function($id) {
    global $resultObj;
    getRoundSheets($resultObj, $id);
});

Flight::route ("/flights/@id:[0-9]+/sheets", function($id) {
    global $resultObj;
    getFlightSheets($resultObj, $id);
});

Flight::route ("/sheets/@id:[0-9]+", function($id) {
    global $resultObj;
    getSheet($resultObj, $id);
});

Flight::route ("/sheets", function() {
    global $resultObj;
    getSheets($resultObj);
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

Flight::route ("/rounds/@roundId:[0-9]+/nextflight", function($roundId) {
    global $resultObj;
    getNextFlight($resultObj, $roundId);
});

Flight::route ("/rounds/@roundId:[0-9]+/flightstatus", function($roundId) {
    global $resultObj;
    getRoundFlightStatus($resultObj, $roundId);
});

Flight::route ("/rounds/nextids", function() {
    global $resultObj;
    getNextRoundIds($resultObj);
});

Flight::route ("/rounds/@roundId:[0-9]+/pilotflights", function($roundId) {
    global $resultObj;
    getRoundPilotFlights($resultObj, $roundId);
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

Flight::route ("GET /rounds/@roundId:[0-9]+/scores", function($roundId) {
    global $resultObj;

    if (isset($_REQUEST['pilot']))  { $pilotId = $_REQUEST['pilot']; } else { $pilotId = null; }
    error_log("INFO: GETTING /rounds/<id>/scores for pilot " . $pilotId);

    if ($pilotId && is_numeric($pilotId)) {
        getScoresForRound($resultObj, array(
            "roundId" => $roundId,
            "pilotId" => $pilotId
        ));
    } else {
        getScoresForRound($resultObj, array(
            "roundId" => $roundId
        ));
    }
});

Flight::route ("DELETE /rounds/@roundId:[0-9]+/scores", function($roundId) {
    global $resultObj;

    if (isset($_REQUEST['pilot']))  { $pilotId = $_REQUEST['pilot']; } else { $pilotId = null; }
    error_log("INFO: DELETING /rounds/<id>/scores for pilot " . $pilotId);

    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        deleteScoreOnSheet($resultObj, array(
            "roundId" => $roundId,
            "pilotId" => $pilotId
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to adjust scores.";
    }
});


Flight::route ("POST /rounds/@roundId:[0-9]+/scores", function($roundId) {
    global $resultObj;

    if (isset($_REQUEST['pilot']))  { $pilotId = $_REQUEST['pilot']; } else { $pilotId = null; }
    error_log("INFO: POSTING /rounds/<id>/scores for pilot " . $pilotId);

    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        adjustScoreForRound($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to adjust scores.";
    }
});

// Not sure if this is a better API.
//Flight::route ("/rounds/@roundId:[0-9]+/pilots/@pilotId:[0-9]+/scores", function($roundId, $pilotId) {
//    global $resultObj;
//    getScoresForRound($resultObj, array(
//        "roundId" => $roundId,
//        "pilotId" => $pilotId
//    ));
//});

Flight::route ("/flights/@flightId:[0-9]+", function($flightId) {
    global $resultObj;
    getSheetsForFlight($resultObj, $flightId);
});

Flight::route ("/pilots/@pilotId:[0-9]*", function($pilotId) {
    global $resultObj;
    getPilot($resultObj, $pilotId);
});

Flight::route ("DELETE /pilots", function() {
    global $resultObj;
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        clearPilots($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to delete pilots.";
    }
});

Flight::route ("DELETE /results", function() {
    global $resultObj;
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        clearResults($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to delete the results.";
    }
});

Flight::route ("POST /pilots", function() {
    global $resultObj;
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        postPilots($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add pilots.";
    }
});

Flight::route ("/schedules", function() {
    global $resultObj;
    error_log("WARN: /schedules is depricated.   Use /sequences instead.");
    getSchedList($resultObj);
});

Flight::route ("GET /sequences", function() {
    global $resultObj;
    getSchedList($resultObj);
});

Flight::route ("POST /sequences", function() {
    global $resultObj;
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        postSequences($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add schedules.";
    }
});

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