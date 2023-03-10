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
global $logger;

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
    echo "<html lang='en_UK' ><head><title>FlightLine</title></head>";
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
 * /results                     - results interface.  Get/Delete results.
 * /rounds/scores               - scores interface.  Basic data with scoresheets.
 * /pilots                      - pilots interface.
 * /flights                     - flights interface.
 * /sheets                      - sheets interface.
 * /users                       - users interface.
 * /nextflight                  - sets or gets next flight(s).
 *
 **************/

/*
Flight::route ('/jsonblah/*', function($route) {
    global $resultObj, $logger;
    $logger->debug("in /jsonblah.");

    apiJSONTest($resultObj, $route->splat);
}, true);

Flight::route ('/jsonblah', function() {
    global $resultObj, $logger;
    $logger->debug("in /jsonblah.");
    apiJSONTest($resultObj);
});
*/

Flight::route ("GET /info", function() {
    global $resultObj, $logger;

    $logger->info("Routing: GET /info");
    getFlightLineDetails($resultObj);
});

Flight::route ("DELETE /auth", function() {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /auth");
    authLogoff($resultObj);
});

Flight::route ("GET /auth/@role", function($role) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /auth/@role=" . $role);
    authHasRole($resultObj, $role);
});

Flight::route ("GET /auth", function() {
    global $resultObj, $logger;

    // Just get the current auth object (JS does not have access to the cookies).
    $logger->info("Routing: GET /auth");
    authGetPayload($resultObj);
});

Flight::route ("POST /auth", function() {
    global $resultObj, $logger;

    $logger->info("Routing: POST /auth");
    $authData = @json_decode((($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}"), true);
    // authData should be an array with keys username and password...

    $logger->info("Authenticating: " . $authData["username"]);
    authLogon($resultObj, $authData);
});

Flight::route ("POST /nextflight", function() {
    global $resultObj, $logger;

    $logger->info("Routing: POST /nextflight");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        setNextFlight($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to set next flight.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("POST /rounds", function() {
    global $resultObj, $logger;

    $logger->info("Routing: POST /rounds");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        addRound($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add a round.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /rounds", function() {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds");
    getRounds($resultObj);
});

Flight::route ("GET /rounds/@class:[A-Za-z]+/@type:[A-Za-z]+/@roundNum:[0-9]+/start", function($class, $type, $roundNum) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@class=" . $class . "/@type=" . $type . "/@roundNum=" . $roundNum . "/start");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        startRound($resultObj, array(
            "roundId" => null,
            "imacClass" => $class,
            "imacType" => $type,
            "roundNum" => $roundNum
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to set the round state.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /rounds/@id:[0-9]+/start", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@id=" . $id . "/start");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        startRound($resultObj, array(
            "roundId" => $id,
            "imacClass" => null,
            "imacType" => null,
            "roundNum" => null
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to set the round state.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /rounds/@class:[A-Za-z]+/@type:[A-Za-z]+/@roundNum:[0-9]+/pause", function($class, $type, $roundNum) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@class=" . $class . "/@type=" . $type . "/@roundNum=" . $roundNum . "/pause");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        pauseRound($resultObj, array(
            "roundId" => null,
            "imacClass" => $class,
            "imacType" => $type,
            "roundNum" => $roundNum
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to set the round state.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /rounds/@id:[0-9]+/pause", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@id=" . $id . "/pause");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        pauseRound($resultObj, array(
            "roundId" => $id,
            "imacClass" => null,
            "imacType" => null,
            "roundNum" => null
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to set the round state.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /rounds/@class:[A-Za-z]+/@type:[A-Za-z]+/@roundNum:[0-9]+/finish", function($class, $type, $roundNum) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@class=" . $class . "/@type=" . $type . "/@roundNum=" .$roundNum . "/finish");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        finishRound($resultObj, array(
            "roundId" => null,
            "imacClass" => $class,
            "imacType" => $type,
            "roundNum" => $roundNum
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to set the round state.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /rounds/@id:[0-9]+/finish", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@id=" . $id . "/finish");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        finishRound($resultObj, array(
            "roundId" => $id,
            "imacClass" => null,
            "imacType" => null,
            "roundNum" => null
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to set the round state.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /rounds/@id:[0-9]+/sheets", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@id=" . $id . "/sheets");
    getRoundSheets($resultObj, $id);
});

Flight::route ("GET /flights/@id:[0-9]+/sheets", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /flights/@id=" . $id . "/sheets");
    getFlightSheets($resultObj, $id);
});

Flight::route ("GET /sheets/@id:[0-9]+", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /sheets/@id=" . $id);
    getSheet($resultObj, $id);
});

Flight::route ("GET /sheets", function() {
    global $resultObj, $logger;

    $logger->info("Routing: GET /sheets");
    getSheets($resultObj);
});

Flight::route ("GET /rounds/@id:[0-9]+", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@id=" . $id);
    getRound($resultObj, array(
        "roundId" => $id,
        "imacClass" => null,
        "imacType" => null,
        "roundNum" => null
    ));
});

Flight::route ("GET /rounds/@class:[A-Za-z]+/@type:[A-Za-z]+/@roundNum:[0-9]+", function($class, $type, $roundNum) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@class=" . $class . "/@type=" . $type . "/@roundNum=" . $roundNum);
    getRound($resultObj, array(
        "roundId" => null,
        "imacClass" => $class,
        "imacType" => $type,
        "roundNum" => $roundNum
    ));
});

Flight::route ("GET /rounds/Freestyle/@roundNum:[0-9]+", function($roundNum) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/Freestyle/@roundNum=" . $roundNum);
    getRound($resultObj, array(
        "roundId" => null,
        "imacClass" => null,
        "imacType" => "Freestyle",
        "roundNum" => $roundNum
    ));
});

Flight::route ("DELETE /competition/", function() {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /competition/");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        resetCompetition($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to reset the competition.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("DELETE /rounds/", function() {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /rounds/");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        deleteAllRounds($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to delete a round.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("DELETE /rounds/@id:[0-9]+", function($id) {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /rounds/@id=" . $id);
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        deleteRound($resultObj, array(
            "roundId" => $id,
            "imacClass" => null,
            "imacType" => null,
            "roundNum" => null
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to delete a round.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("DELETE /rounds/@class:[A-Za-z]+/@type:[A-Za-z]+/@roundNum:[0-9]+", function($class, $type, $roundNum) {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /rounds/@class=" . $class . "/@type=" . $type . "/@roundNum=" . $roundNum);
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        deleteRound($resultObj, array(
            "roundId" => null,
            "imacClass" => $class,
            "imacType" => $type,
            "roundNum" => $roundNum
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add a round.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("DELETE /rounds/Freestyle/@roundNum:[0-9]+", function($roundNum) {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /rounds/Freestyle/@roundNum=" . $roundNum);
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN,JUDGE")) {
        mergeResultMessages($resultObj, $authResultObj);
        deleteRound($resultObj, array(
            "roundId" => null,
            "imacClass" => null,
            "imacType" => "Freestyle",
            "roundNum" => $roundNum
        ));
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add a round.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }

});

Flight::route ("GET /rounds/@roundId:[0-9]+/nextflight", function($roundId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@roundId=" . $roundId . "/nextflight");
    getNextFlight($resultObj, $roundId);
});

Flight::route ("GET /rounds/@roundId:[0-9]+/flightstatus", function($roundId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@roundId=" . $roundId . "/flightstatus");
    getRoundFlightStatus($resultObj, $roundId);
});

Flight::route ("GET /rounds/nextids", function() {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/nextids");
    getNextRoundIds($resultObj);
});

Flight::route ("GET /rounds/@roundId:[0-9]+/pilotflights", function($roundId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@roundId=" . $roundId . "/pilotflights");
    getRoundPilotFlights($resultObj, $roundId);
});

Flight::route ("GET /rounds/@roundId:[0-9]+/pilots/@pilotId:[0-9]+", function($roundId, $pilotId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@roundId=" . $roundId . "/pilots/@pilotId=" . $pilotId);
    getPilotsForRound($resultObj, array(
        "roundId" => $roundId,
        "pilotId" => $pilotId
    ));
});

Flight::route ("GET /rounds/@roundId:[0-9]+/pilots", function($roundId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@roundId=" . $roundId . "/pilots");
    getPilotsForRound($resultObj, array(
        "roundId" => $roundId
    ));
});

Flight::route ("GET /rounds/@roundId:[0-9]+/results", function($roundId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@roundId=" . $roundId . "/results");
    getFlownRound($resultObj, $roundId);
});

Flight::route ("GET /rounds/@roundId:[0-9]+/scores", function($roundId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /rounds/@roundId=" . $roundId . "/scores");
    if (isset($_REQUEST['pilot']))  { $pilotId = $_REQUEST['pilot']; } else { $pilotId = null; }
    $logger->info("GETTING /rounds/<id>/scores for pilot " . $pilotId);

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

Flight::route ("DELETE /sheets/@sheetId:[0-9]+/@figureNum:[0-9]+", function($sheetId, $figureNum) {
    global $resultObj, $logger;

    // Note!    This actually does delete the score (and any delta) rather than adding a 'delete' adjustment...
    $logger->info("Routing: DELETE /sheets/@sheetId=" . $sheetId . "/@figureNum=" . $figureNum);
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        /*
        deleteScoreOnSheet($resultObj, array(
            "sheetId" => $sheetId,
            "figureNum" => $figureNum
        ));
        */
        $logger->info("This method is not yet implemented.");
        $resultObj["result"]  = 'not implemented';
        $resultObj['message'] = "This method is not yet implemented.";
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to adjust scores.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("DELETE /sheets/@sheetId:[0-9]+/@figureNum:[0-9]+/adjustment", function($sheetId, $figureNum) {
    global $resultObj, $logger;

    // Note!  Where here to just delete the delta!
    $logger->info("Routing: DELETE /sheets/@sheetId=" . $sheetId . "/@figureNum=" . $figureNum . "/adjustment");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);

        deleteScoreAdjustment($resultObj, array(
            "sheetId" => $sheetId,
            "figureNum" => $figureNum
        ));

    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to adjust scores.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("POST /sheets/@sheetId:[0-9]+/@figureNum:[0-9]+/adjustment", function($sheetId, $figureNum) {
    global $resultObj, $logger;

    $logger->info("Routing: POST /sheets/@sheetId=" . $sheetId . "/@figureNum=" . $figureNum . "/adjustment");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);

        postScoreAdjustment($resultObj, array(
            "sheetId" => $sheetId,
            "figureNum" => $figureNum
        ));

    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to adjust scores.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});


Flight::route ("POST /sheets/@sheetId:[0-9]+/@figureNum:[0-9]+", function($sheetId, $figureNum) {
    global $resultObj, $logger;

    // Note!    This actually does adjust the score rather than manking an adjustment...
    $logger->info("Routing: POST /sheets/@sheetId=" . $sheetId . "/@figureNum=" . $figureNum);
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        //editScoreForRound($resultObj);

        $logger->info("This method is not yet implemented.");
        $resultObj["result"]  = 'not implemented';
        $resultObj['message'] = "This method is not yet implemented.";
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to adjust scores.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

// Not sure if this is a better API.
//Flight::route ("GET /rounds/@roundId:[0-9]+/pilots/@pilotId:[0-9]+/scores", function($roundId, $pilotId) {
//    global $resultObj, $logger;
//
//    $logger->info("Routing:");
//    getScoresForRound($resultObj, array(
//        "roundId" => $roundId,
//        "pilotId" => $pilotId
//    ));
//});

Flight::route ("GET /flights/@flightId:[0-9]+", function($flightId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /flights/@flightId=" . $flightId);
    getSheetsForFlight($resultObj, $flightId);
});

Flight::route ("GET /pilots/@pilotId:[0-9]*", function($pilotId) {
    global $resultObj, $logger;

    $logger->info("Routing: GET /pilots/@pilotId=" . $pilotId);
    getPilot($resultObj, $pilotId);
});

Flight::route ("DELETE /pilots", function() {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /pilots");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        clearPilots($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to delete pilots.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("DELETE /results", function() {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /results");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        clearResults($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to delete the results.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("POST /pilots", function() {
    global $resultObj, $logger;

    $logger->info("Routing: POST /pilots");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        postPilots($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add pilots.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("GET /schedules", function() {
    global $resultObj, $logger;

    $logger->info("Routing: GET /schedules");
    $logger->warning(" /schedules is depricated.   Use /sequences instead.");
    getSchedList($resultObj);
});

Flight::route ("GET /sequences", function() {
    global $resultObj, $logger;

    $logger->info("Routing: GET /sequences");
    getSchedList($resultObj);
});

Flight::route ("POST /sequences", function() {
    global $resultObj, $logger;

    $logger->info("Routing: POST /sequences");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        postSequences($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to add sequences.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::route ("DELETE /sequences", function() {
    global $resultObj, $logger;

    $logger->info("Routing: DELETE /sequences");
    $authResultObj = createEmptyResultObject();
    if (authHasRole($authResultObj, "ADMIN")) {
        mergeResultMessages($resultObj, $authResultObj);
        deleteSequences($resultObj);
    } else {
        mergeResultMessages($resultObj, $authResultObj);
        $resultObj['message'] = "Not authorised to delete sequences.";
        $logger->info("Auth failed: " . $resultObj['message']);
    }
});

Flight::start();

dbDisconnect();
unset($db);

// Lots of kinda useless info...
// $logger->debug("Sending back: ", $resultObj);

// Convert PHP array to JSON array
if ($jsondebug === false || $jsondebug === "false") {
    $json_data = json_encode($resultObj, null);
} else {
    $json_data = json_encode($resultObj, JSON_PRETTY_PRINT);
}
header('Content-Type: application/json');
print $json_data;