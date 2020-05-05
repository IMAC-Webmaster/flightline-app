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

include_once '../../include/functions.php';
include_once 'api_functions.php';
ini_set("display_errors", 0);

// Database details
$dbfile = '../../db/flightline.db';


// Get job (and id)
unset ($job);
unset ($id);
unset ($jsondebug);
unset($message);
if (isset($_GET['job'])) { $job = $_GET['job']; }
if (isset($_GET['id']))  { $id = $_GET['id']; }
if (isset($_GET['jsondebug']))  { $jsondebug = $_GET['jsondebug']; } else { $jsondebug = false; }


// Prepare array
$sqlite_data = array();
$db = null;
$result = null;
$message = null;
$verboseMsgs = array();
$requestTime = time();
$source = null;
$requestId = null;

// Connect to database
if (dbConnect($dbfile) === false) {
    $result  = 'error';
    $message = 'Failed to connect to database: ' . $e->getMessage();
    unset ($job);
}

$resultObj = createEmptyResultObject();

switch ($job) {
        case 'del_all_results':
            clearResults($resultObj);
            break;
        case 'del_all_pilots':
            clearPilots($resultObj);
            break;
        case 'del_schedules':
            clearSchedules($resultObj);
            break;
        case 'post_pilots':
            postPilots($resultObj);
            break;
        case "post_sequences":
            postSequences($resultObj);
            break;
    case 'get_rounds':
        getRounds($resultObj);
        break;

        case 'get_rounds_select_list':
            getRoundsSelectList($resultObj);
            break;
    case 'get_round':
        getRound($resultObj);
        break;
        case "get_flight_order_for_round":
            getFlightOrderForRound($resultObj);
            break;
        case "get_scores_for_round":
            getScoresForRound($resultObj);
            break;
        case 'get_flown_rounds':
            getFlownRounds($resultObj);
            break;
        case 'get_latest_round_and_pilot':
            getMostRecentPilotAndRound($resultObj);
            break;
        case 'get_flightline_data':
            getFlightLineData($resultObj);
            break;
        //case 'get_round_results':
        //    // This function is broken.
        //    getRoundResults();
        //    break;
        case 'get_round_pilots':
            getRoundPilotFlights($resultObj);
            break;
        case 'get_round_flightstatus':
            getRoundFlightStatus($resultObj);
            break;
        case 'get_nextrnd_ids':
            getNextRndIds($resultObj);
            break;
        case 'set_next_flight':
            setNextFlight($resultObj);
            break;
        case 'get_next_flight':
            getNextFlight($resultObj);
            break;
        case 'get_schedlist':
            getSchedlist($resultObj);
            break;
        case 'add_round':
            addRound($resultObj);
            break;
        case 'edit_round':
            editRound($resultObj);
            break;
        case 'start_round':
            startRound($resultObj);
            break;
        case 'pause_round':
            pauseRound($resultObj);
            break;
        case 'finish_round':
            finishRound($resultObj);
            break;
        case 'delete_round':
            deleteRound($resultObj);
            break;
        case 'get_flight_scores':
            getFlightScores($resultObj, null, null);  // We are obviously not using this yet.
            break;
    default:
        $resultObj["result"]  = 'error';
        $resultObj["message"] = 'unknown job';
        unset ($job);
        break;
}

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
