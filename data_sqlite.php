<?php
include_once 'include/functions.php';
ini_set("display_errors", 0);

// Database details
$dbfile = 'flightline.db';


// Get job (and id)
unset ($job);
unset ($id);
unset($message);
if (isset($_GET['job'])) {
  $job = $_GET['job'];
  if ($job == 'get_rounds'        ||
      $job == 'get_round'         ||
      $job == 'get_flightline'    ||
      $job == 'get_round_results' ||
      $job == 'get_nextrnds'      ||
      $job == 'get_schedlist'     ||
      $job == 'add_round'         ||
      $job == 'edit_round'        ||
      $job == 'start_round'       ||
      $job == 'pause_round'       ||
      $job == 'finish_round'      ||
      $job == 'delete_round')       {
    if (isset($_GET['id'])){ $id = $_GET['id'];}
  } else {
    $result  = 'error';
    $message = 'unknown job';
    unset ($job);
  }
}

// Prepare array
$sqlite_data = array();

// Connect to database
try {
  $db = new SQLite3($dbfile);
} catch (Exception $e) {
  $result  = 'error';
  $message = 'Failed to connect to database: ' . $e->getMessage();
  unset ($job);
}    

// Valid job found
if (isset($job)){

  // Execute job
  if ($job == 'get_rounds') {
    // Get rounds
    $query = "select s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status from round r left join schedule s on s.schedId = r.schedId order by r.imacClass, r.imacType, r.roundNum;";
    if ($statement = $db->prepare($query)) {
      try {
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      $result  = 'success';
      $message = 'query success';
      while ($round = $res->fetchArray()){
        $functions  = '<div class="function_buttons"><ul>';
        switch($round["phase"]) {
            case "U":
                $functions .= '<li class="function_start"><a data-imacclass="'  . $round['imacClass'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '" data-phase="' . $round['phase'] . '"><span>Start</span></a></li>';
                $functions .= '<li class="function_edit"><a data-imacclass="'   . $round['imacClass'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '"><span>Edit</span></a></li>';
                $functions .= '<li class="function_delete"><a data-imacclass="' . $round['imacClass'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '"><span>Delete</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
            case "O":
                $functions .= '<li class="function_pause"><a data-imacclass="'   . $round['imacClass'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '" data-phase="' . $round['phase'] . '"><span>Pause</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
            case "P":
                $functions .= '<li class="function_start"><a data-imacclass="'   . $round['imacClass'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '"><span>Start</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_finish"><a data-imacclass="'  . $round['imacClass'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '" data-phase="' . $round['phase'] . '"><span>Finalise</span></a></li>';
                break;
            case "D":
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
        }
        $functions .= '</ul></div>';
        $sqlite_data[] = array(
          "imacClass"    => $round['imacClass'],
          "imacType"     => $round['imacType'],
          "roundNum"      => $round['roundNum'],
          "description"   => $round['description'],
          "schedId"   => $round['schedId'],
          "sequences"     => $round['sequences'],
          "phase"         => $round['phase'],
          "status"        => $round['status'],
          "functions"     => $functions
        );
      }
    }
  } // get_rounds...

  elseif ($job == 'get_round_results') {
    // Get the full data for a round.
    $query = "select * from round where roundId = :roundId;";
    if (isset($_GET['roundId']))  { $roundId   = $_GET['roundId'];  } else $roundId = null;

    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':roundId',   $roundId);
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
        $result  = 'success';
        $message = 'query success';
        $round = $res->fetchArray(SQLITE3_ASSOC);
        
        if (!$round) {
            $sqlite_data = array();
        } else {
            $flight_stmt = $db->prepare("select * from flight where roundId = :roundId;");
            $flight_stmt->bindValue(':roundId',   $round["roundId"]);
            $flight_res = $flight_stmt->execute();
            $flights = array();
            while ($flight = $flight_res->fetchArray(SQLITE3_ASSOC)){
                // Get the sheets to add to this flight.
                unset($flight["roundId"]);
                $sheet_stmt = $db->prepare("select * from sheet where flightId = :flightId;");
                $sheet_stmt->bindValue(':flightId',   $flight["flightId"]);
                $sheet_res = $sheet_stmt->execute();
                $sheets = array();
                while ($sheet = $sheet_res->fetchArray(SQLITE3_ASSOC)){
                    // Get the scores to add to this sheet.
                    unset($sheet["flightId"]);
                    $score_stmt = $db->prepare("select * from score where sheetId = :sheetId;");
                    $score_stmt->bindValue(':sheetId',   $sheet["sheetId"]);
                    $score_res = $score_stmt->execute();
                    $scores = array();
                    while ($score = $score_res->fetchArray(SQLITE3_ASSOC)){
                        unset($score["sheetId"]);
                        $scores[] = $score;  
                    }
                    $score_res->finalize();
                    $score_stmt->close();
                    $sheet["scores"] = $scores;
                    $sheets[] = $sheet;   
                }
                $sheet_res->finalize();
                $sheet_stmt->close();
                $flight["sheets"] = $sheets;
                $flights[] = $flight;
            }
            $flight_res->finalize();
            $flight_stmt->close();
            $round["flights"] = $flights;

            $res->finalize();
            $statement->close();
            $result  = 'success';
            $message = 'query success';
            $sqlite_data = $round;
        }

    }
  } // get_round_results...
  elseif ($job == 'get_flightline') {
    // Get the full DB Dump in JSON format for importing into Score!
    
    $conf_stmt = $db->prepare("select * from config;");
    $conf_res = $conf_stmt->execute();
    $users = array();
    $conf = $conf_res->fetchArray(SQLITE3_ASSOC);
    $conf_res->finalize();
    $conf_stmt->close();

    $users_stmt = $db->prepare("select * from user;");
    $users_res = $users_stmt->execute();
    $users = array();
    while ($user = $users_res->fetchArray(SQLITE3_ASSOC)){
        $users[] = $user;  
    }
    $users_res->finalize();
    $users_stmt->close();
    
    $query = "select * from round;";
    if ($statement = $db->prepare($query)) {
      try {
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
        $result  = 'success';
        $message = 'query success';
        $all_rounds = array();
        while ($round = $res->fetchArray(SQLITE3_ASSOC)){
            // For each round, now we need to get the flights
            $flight_stmt = $db->prepare("select * from flight where roundId = :roundId;");
            $flight_stmt->bindValue(':roundId',   $round["roundId"]);
            $flight_res = $flight_stmt->execute();
            $flights = array();
            while ($flight = $flight_res->fetchArray(SQLITE3_ASSOC)){
                // Get the sheets to add to this flight.
                unset($flight["roundId"]);
                $sheet_stmt = $db->prepare("select * from sheet where flightId = :flightId;");
                $sheet_stmt->bindValue(':flightId',   $flight["flightId"]);
                $sheet_res = $sheet_stmt->execute();
                $sheets = array();
                while ($sheet = $sheet_res->fetchArray(SQLITE3_ASSOC)){
                    // Get the scores to add to this sheet.
                    unset($sheet["flightId"]);
                    $score_stmt = $db->prepare("select * from score where sheetId = :sheetId;");
                    $score_stmt->bindValue(':sheetId',   $sheet["sheetId"]);
                    $score_res = $score_stmt->execute();
                    $scores = array();
                    while ($score = $score_res->fetchArray(SQLITE3_ASSOC)){
                        unset($score["sheetId"]);
                        $scores[] = $score;  
                    }
                    $score_res->finalize();
                    $score_stmt->close();
                    $sheet["scores"] = $scores;
                    $sheets[] = $sheet;   
                }
                $sheet_res->finalize();
                $sheet_stmt->close();
                $flight["sheets"] = $sheets;
                $flights[] = $flight;
            }
            $flight_res->finalize();
            $flight_stmt->close();
            $round["flights"] = $flights;
            $all_rounds[] = $round;
        }
        $res->finalize();
        $statement->close();
        $result  = 'success';
        $message = 'query success';
        $sqlite_data["flightLineId"] = $conf["flightLineId"];
        $sqlite_data["users"] = $users;
        $sqlite_data["rounds"] = $all_rounds;

    }
  } // get_flightline...
   elseif ($job == 'get_round') {
    // Get round  (imacClass, imacType, round number).
    if (isset($_GET['imacClass'])){ $imacClass = $_GET['imacClass'];} else $imacClass = null;
    if (isset($_GET['imacType'])) { $imacType  = $_GET['imacType']; } else $imacType  = null;
    if (isset($_GET['roundNum']))  { $roundNum   = $_GET['roundNum'];  } else $roundNum   = null;


    $query =  "select s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status from round r left join schedule s on s.schedId = r.schedId ";
    $query .= "where r.imacClass = :imacClass and r.imacType = :imacType and r.roundNum = :roundNum";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imacClass',    $imacClass);
        $statement->bindValue(':imacType',     $imacType);
        $statement->bindValue(':roundNum',     $roundNum);
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      $result  = 'success';
      $message = 'query success';
      while ($round = $res->fetchArray()){
        $sqlite_data = array(
          "imacClass"     => $round['imacClass'],
          "imacType"      => $round['imacType'],
          "roundNum"      => $round['roundNum'],
          "description"   => $round['description'],
          "schedId"       => $round['schedId'],
          "sequences"     => $round['sequences'],
          "phase"         => $round['phase'],
          "status"        => $round['status']
        );
      }
    }
  } // get_round...

  elseif ($job == 'get_nextrnds') {
    // Get rounds
    $query = "select imacClass, imacType, (max(roundNum) + 1) as nextroundNum from round group by imacClass, imacType;";
    if ($statement = $db->prepare($query)) {
      try {
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      $result  = 'success';
      $message = 'query success';
      while ($round = $res->fetchArray()){
        $sqlite_data[] = array(
          "imacClass"          => $round['imacClass'],
          "imacType"           => $round['imacType'],
          "nextroundNum"       => $round['nextroundNum'],
        );
      }
    }
  } // get_roundlist...

  elseif ($job == 'get_schedlist') {
    // Get rounds
    $query = "select * from schedule order by imacClass;";
    if ($statement = $db->prepare($query)) {
      try {
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      $result  = 'success';
      $message = 'query success';
      while ($round = $res->fetchArray()){
        $sqlite_data[] = array(
          "schedId"     => $round['schedId'],
          "imacClass"   => $round['imacClass'],
          "imacType"    => $round['imacType'],
          "description" => $round['description'],
        );
      }
    }
  } // get_schedlist...
 
  elseif ($job == 'add_round') {
    // Add round
      
    $query =  "INSERT into round (imacClass, imacType, roundNum, schedId, sequences, phase) ";
    $query .= "VALUES (:imacClass, :imacType, :roundNum, :schedId, :sequences, :phase );";

    if ($statement = $db->prepare($query)) {
      try {
        if (isset($_GET['imacClass'])) { $statement->bindValue(':imacClass',   $_GET['imacClass']);   };
        if (isset($_GET['imacType']))  { $statement->bindValue(':imacType',    $_GET['imacType']);    };
        if (isset($_GET['roundNum']))   { $statement->bindValue(':roundNum',     $_GET['roundNum']);     };
        if (isset($_GET['schedule']))   { $statement->bindValue(':schedId',     $_GET['schedule']);     };
        if (isset($_GET['sequences']))  { $statement->bindValue(':sequences',    $_GET['sequences']);    };
        $statement->bindValue(':phase',        'U');
        error_log($query);
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
      $res = FALSE;
      $err = error_get_last();
      $message = $err['message'];
    }

    if ($res === FALSE) {
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      $result  = 'success';
      $message = 'query success';
    }
      
  } // add_round...

  elseif ($job == 'delete_round') {
    // Delete round
    if (isset($_GET['imacClass'])){ $imacClass = $_GET['imacClass'];} else $imacClass = null;
    if (isset($_GET['imacType'])){ $imacType = $_GET['imacType'];} else $imacType = null;
    if (isset($_GET['roundNum'])){ $roundNum = $_GET['roundNum'];} else $roundNum = null;
    $query = "delete from round where imacClass = :imacClass and imacType = :imacType and roundNum = :roundNum and phase ='U';";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imacClass',    $imacClass);
        $statement->bindValue(':imacType',     $imacType);
        $statement->bindValue(':roundNum', $roundNum);
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      // Query was OK, but let's check if we actually deleted it (business rule - can only delete unflown rounds).
      if ($db->changes() === 1) {
        $result  = 'success';
        $message = 'query success';
      } elseif ($db->changes() === 0) {
        $result  = 'error';
        $message = 'Unable to delete this round.  Is it already started?';
      }
    }
  } // delete_round...

  elseif ($job == 'start_round') {
    // Start round
    if (isset($_GET['imacClass'])){ $imacClass = $_GET['imacClass'];} else $imacClass = null;
    if (isset($_GET['imacType'])){ $imacType = $_GET['imacType'];} else $imacType = null;
    if (isset($_GET['roundNum'])){ $roundNum = $_GET['roundNum'];} else $roundNum = null;
    $blOkToGo = true;
    $query = "select count(*) as flycount from round where phase = 'O';";
    if ($statement = $db->prepare($query)) {
      try {
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage(); 
        $blOkToGo = false;
      }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
        $blOkToGo = false;
    }

    if ($res === FALSE) {
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
        $round = $res->fetchArray();
        if ($round["flycount"] > 0) {
            $result  = 'error';
            $message = 'There is already an open round.';
            $blOkToGo = false;
        }
    }
    if ($blOkToGo) {
      $query = "update round set phase = 'O', startTime = strftime('%s','now') where imacClass = :imacClass and imacType = :imacType and roundNum = :roundNum and (phase ='U' or phase = 'P');";
      if ($statement = $db->prepare($query)) {
        try {
          $statement->bindValue(':imacClass',    $imacClass);
          $statement->bindValue(':imacType',     $imacType);
          $statement->bindValue(':roundNum',      $roundNum);
          $res = $statement->execute();
        } catch (Exception $e) {
          $result  = 'error';
          $message = 'query error: ' . $e->getMessage();          
        }
      } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
      }

      if ($res === FALSE){
        $result  = 'error';
        if (!isset($message)) { $message = 'query error'; }
      } else {
        // Query was OK, but let's check if we actually started it (business rule - can only start unflown or paused rounds).
        if ($db->changes() === 1) {
          $result  = 'success';
          $message = 'query success';
        } elseif ($db->changes() === 0) {
           $result  = 'error';
           $message = 'Unable to start this round.  Wrong phase?';
        }
      }
    }
  } // start_round...
  
  elseif ($job == 'pause_round') {
    // Pause round
    if (isset($_GET['imacClass'])){ $imacClass = $_GET['imacClass'];} else $imacClass = null;
    if (isset($_GET['imacType'])) { $imacType  = $_GET['imacType']; } else $imacType  = null;
    if (isset($_GET['roundNum']))  { $roundNum   = $_GET['roundNum'];  } else $roundNum   = null;
 
    $query = "update round set phase = 'P' where imacClass = :imacClass and imacType = :imacType and roundNum = :roundNum and phase ='O';";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imacClass',    $imacClass);
        $statement->bindValue(':imacType',     $imacType);
        $statement->bindValue(':roundNum',      $roundNum);
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
      $res = FALSE;
      $err = error_get_last();
      $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      // Query was OK, but let's check if we actually started it (business rule - can only start unflown or paused rounds).
      if ($db->changes() === 1) {
        $result  = 'success';
        $message = 'query success';
      } elseif ($db->changes() === 0) {
         $result  = 'error';
         $message = 'Unable to pause this round.  Wrong phase?';
      }
    }
  } // Pause round...

  elseif ($job == 'finish_round') {
    // Finish round
    if (isset($_GET['imacClass'])){ $imacClass = $_GET['imacClass'];} else $imacClass = null;
    if (isset($_GET['imacType'])) { $imacType  = $_GET['imacType']; } else $imacType  = null;
    if (isset($_GET['roundNum']))  { $roundNum   = $_GET['roundNum'];  } else $roundNum   = null;
 
    $query = "update round set phase = 'D', finishTime = strftime('%s','now') where imacClass = :imacClass and imacType = :imacType and roundNum = :roundNum and phase ='P';";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imacClass',    $imacClass);
        $statement->bindValue(':imacType',     $imacType);
        $statement->bindValue(':roundNum',      $roundNum);
        $res = $statement->execute();
      } catch (Exception $e) {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();          
      }
    } else {
      $res = FALSE;
      $err = error_get_last();
      $message = $err['message'];
    }

    if ($res === FALSE){
      $result  = 'error';
      if (!isset($message)) { $message = 'query error'; }
    } else {
      // Query was OK, but let's check if we actually started it (business rule - can only start unflown or paused rounds).
      if ($db->changes() === 1) {
        $result  = 'success';
        $message = 'query success';
      } elseif ($db->changes() === 0) {
         $result  = 'error';
         $message = 'Unable to complete this round.  Wrong phase?';
      }
    }
  } // Finish round...

  elseif ($job == 'edit_round') {
    // Edit round
    $blOkToGo = true;
    if (isset($_GET['prevclass'])){     $prevclass      = $_GET['prevclass'];   } else $blOkToGo = false;
    if (isset($_GET['prevtype'])){      $prevtype       = $_GET['prevtype'];    } else $blOkToGo = false;
    if (isset($_GET['prevroundNum'])){  $prevroundNum   = $_GET['prevroundNum'];} else $blOkToGo = false;
    if (isset($_GET['imacClass'])){    $imacClass     = $_GET['imacClass'];  } else $blOkToGo = false;
    if (isset($_GET['imacType'])){     $imacType      = $_GET['imacType'];   } else $blOkToGo = false;
    if (isset($_GET['roundNum'])){      $roundNum       = $_GET['roundNum'];    } else $blOkToGo = false;
    if (isset($_GET['schedule'])){      $sched          = $_GET['schedule'];    } else $blOkToGo = false;
    if (isset($_GET['sequences'])){     $sequences      = $_GET['sequences'];   } else $blOkToGo = false;
    
    if (!blOkToGo) {
      $result  = 'error';
      $message = 'Unable to edit this round.  Some form data was missing.';
    } else {
      $query  = "update round set imacClass = :imacClass, imacType = :imacType, roundNum = :roundNum, schedId = :schedId, sequences = :sequences ";
      $query .= "where imacClass = :prevclass and imacType = :prevtype and roundNum = :prevroundNum and phase ='U';";
      
      if ($statement = $db->prepare($query)) {
        try {
          $statement->bindValue(':prevclass',    $prevclass);
          $statement->bindValue(':prevtype',     $prevtype);
          $statement->bindValue(':prevroundNum', $prevroundNum);
          $statement->bindValue(':imacClass',   $imacClass);
          $statement->bindValue(':imacType',    $imacType);
          $statement->bindValue(':roundNum',     $roundNum);
          $statement->bindValue(':schedId',     $sched);
          $statement->bindValue(':sequences',    $sequences);
          $res = $statement->execute();
        } catch (Exception $e) {
          $result  = 'error';
          $message = 'query error: ' . $e->getMessage();          
        }
      } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
      }

      if ($res === FALSE){
        $result  = 'error';
        if (!isset($message)) { $message = 'query error'; }
      } else {
        // Query was OK, but let's check if we actually deleted it (business rule - can only delete unflown rounds).
        if ($db->changes() === 1) {
          $result  = 'success';
          $message = 'query success';
        } elseif ($db->changes() === 0) {
          $result  = 'error';
          $message = 'Unable to delete this round.  Is it already started?';
        }
      }
    }
  } // edit_round...
  
  
  // Close database connection
  $db->close();

}

// Prepare data
$data = array(
  "result"  => $result,
  "message" => $message,
  "data"    => $sqlite_data
);

// Convert PHP array to JSON array
$json_data = json_encode($data, JSON_PRETTY_PRINT);
print $json_data;
