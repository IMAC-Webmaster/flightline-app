<?php
include_once 'include/functions.php';
include_once 'include/data_functions.php';
ini_set("display_errors", 0);

// Database details
$dbfile = 'flightline.db';


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

// Connect to database
try {
  $db = new SQLite3($dbfile);
} catch (Exception $e) {
  $result  = 'error';
  $message = 'Failed to connect to database: ' . $e->getMessage();
  unset ($job);
}    

switch ($job) {
    case 'get_rounds':
        getRounds();
        break;
    case 'get_round':
        getRound();
        break;
    case 'get_flightline':
        getFlightline();
        break;
    case 'get_round_results':
        getRoundResults();
        break;
    case 'get_round_pilots':
        getRoundPilots();
        break;
    case 'get_nextrnd_ids':
        getNextRndIds();
        break;
    case 'set_next_flight':
        setNextFlight();
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
    default:
        $result  = 'error';
        $message = 'unknown job';
        unset ($job);
        break;
}

$db->close();
// Prepare data
$data = array(
  "result"  => $result,
  "message" => $message,
  "data"    => $sqlite_data
);

// Convert PHP array to JSON array
$json_data = json_encode($data, JSON_PRETTY_PRINT);
print $json_data;
