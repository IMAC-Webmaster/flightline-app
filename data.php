<?php
include_once 'include/functions.php';
include_once 'include/data_functions.php';
ini_set("display_errors", 0);

// Database details
$dbfile = 'db/flightline.db';


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

switch ($job) {
    case 'del_all_results':
        clearResults();
        break;
    case 'del_all_pilots':
        clearPilots();
        break;
    case 'del_schedules':
        clearSchedules();
        break;
    case 'post_pilots':
        postPilots();
        break;
    case "post_sequences":
        postSequences();
        break;
    case 'get_rounds':
        getRounds();
        break;
    case 'get_rounds_select_list':
        getRoundsSelectList();
        break;
    case 'get_round':
        getRound();
        break;
    case "get_flight_order_for_round":
        getFlightOrderForRound();
        break;
    case "get_scores_for_round":
        getScoresForRound();
        break;
    case 'get_flown_rounds':
        getFlownRounds();
        break;
    case 'get_flightline_data':
        getFlightLineData();
        break;
    case 'get_round_results':
        getRoundResults();
        break;
    case 'get_round_pilots':
        getRoundPilots();
        break;
    case 'get_round_flightstatus':
        getRoundFlightStatus();
        break;
    case 'get_nextrnd_ids':
        getNextRndIds();
        break;
    case 'set_next_flight':
        setNextFlight();
        break;
    case 'get_next_flight':
        getNextFlight();
        break;
    case 'get_schedlist':
        getSchedlist();
        break;
    case 'add_round':
        addRound();
        break;
    case 'edit_round':
        editRound();
        break;
    case 'start_round':
        startRound();
        break;
    case 'pause_round':
        pauseRound();
        break;
    case 'finish_round':
        finishRound();
        break;
    case 'delete_round':
        deleteRound();
        break;
    case 'get_flight_scores':
        getFlightScores(5, 3);
        break;
    default:
        $result  = 'error';
        $message = 'unknown job';
        unset ($job);
        break;
}

$db->close();
unset($db);
// Prepare data
$data = array(
  "result"          => $result,
  "message"         => $message,
  "requestId"       => $requestId,
  "requestTime"     => $requestTime,
  "verboseMsgs"     => $verboseMsgs,
  "source"          => $source,
  "data"            => $sqlite_data
);

if (!isSet($data["data"]) || $data["data"] == null)
    $data["data"] = array();

if (!isSet($data["verboseMsgs"]) || $data["verboseMsgs"] == null)
    unset($data["verboseMsgs"]);

if (!isSet($data["requestTime"]) || $data["requestTime"] == null)
    unset($data["requestTime"]);

if (!isSet($data["source"]) || $data["source"] == null)
    unset($data["source"]);

if (!isSet($data["requestId"]) || $data["requestId"] == null)
    unset($data["requestId"]);

// Convert PHP array to JSON array
$json_data = json_encode($data, JSON_PRETTY_PRINT);
print $json_data;
