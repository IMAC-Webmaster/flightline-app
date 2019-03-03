<?php
include_once '../../include/functions.php';
include_once 'api_functions.php';
ini_set("display_errors", 0);

// Database details
$dbfile = '../../db/flightline.db';


// Get job (and id)
unset ($job);
unset ($id);
unset($message);
if (isset($_GET['job'])) { $job = $_GET['job']; }
if (isset($_GET['id']))  { $id = $_GET['id']; }


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
try {
  $db = new SQLite3($dbfile);
  $db->busyTimeout(5000);
  // WAL mode has better control over concurrency.
  // Source: https://www.sqlite.org/wal.html
  $db->exec('PRAGMA journal_mode = wal;');
} catch (Exception $e) {
  $result  = 'error';
  $message = 'Failed to connect to database: ' . $e->getMessage();
  unset ($job);
}    

$resultObj = createEmptyResultsObject();

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
        getRound();
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
    case 'get_round_results':
        getRoundResults($resultObj);
        break;
    case 'get_round_pilots':
        getRoundPilots($resultObj);
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
        $result  = 'error';
        $message = 'unknown job';
        unset ($job);
        break;
}

$db->close();
unset($db);

// Convert PHP array to JSON array
$json_data = json_encode($resultObj, JSON_PRETTY_PRINT);
print $json_data;
