<?php

require '../libs/flight/Flight.php';
include_once '../include/functions.php';
ini_set("display_errors", 0);

/*********************
 *
 * This file is the first entry for a client.
 * By accessing /api the client can find out which API versions are available and which one the server defaults to.
 *
 */

$apiver = 1;



// Config
$dbfile = '../db/flightline.db';
$apiurl = "/api/$apiver";

include_once "$apiver/api_functions.php";

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

Flight::route ("/", function() {
    global $resultObj;
    getFlightLineDetails($resultObj);
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