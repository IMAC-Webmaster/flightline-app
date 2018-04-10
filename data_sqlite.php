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
  if ($job == 'get_rounds'       ||
      $job == 'get_round'        ||
      $job == 'get_rounds_json'  ||
      $job == 'get_nextrnds'     ||
      $job == 'get_schedlist'    ||
      $job == 'add_round'        ||
      $job == 'edit_round'       ||
      $job == 'start_round'      ||
      $job == 'pause_round'      ||
      $job == 'finish_round'     ||
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
    $query = "select s.description, s.schedule_id, r.imac_class, r.imac_type, r.roundnum, r.sequences, r.phase, r.status from round r left join schedule s on s.schedule_id = r.sched_id order by r.imac_class, r.imac_type, r.roundnum;";
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
                $functions .= '<li class="function_start"><a data-imac_class="'  . $round['imac_class'] . '" data-imac_type="' . $round['imac_type'] . '" data-roundnum="' . $round['roundnum'] . '" data-phase="' . $round['phase'] . '"><span>Start</span></a></li>';
                $functions .= '<li class="function_edit"><a data-imac_class="'   . $round['imac_class'] . '" data-imac_type="' . $round['imac_type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Edit</span></a></li>';
                $functions .= '<li class="function_delete"><a data-imac_class="' . $round['imac_class'] . '" data-imac_type="' . $round['imac_type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Delete</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
            case "O":
                $functions .= '<li class="function_pause"><a data-imac_class="'   . $round['imac_class'] . '" data-imac_type="' . $round['imac_type'] . '" data-roundnum="' . $round['roundnum'] . '" data-phase="' . $round['phase'] . '"><span>Pause</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
            case "P":
                $functions .= '<li class="function_start"><a data-imac_class="'   . $round['imac_class'] . '" data-imac_type="' . $round['imac_type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Start</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_finish"><a data-imac_class="'  . $round['imac_class'] . '" data-imac_type="' . $round['imac_type'] . '" data-roundnum="' . $round['roundnum'] . '" data-phase="' . $round['phase'] . '"><span>Finalise</span></a></li>';
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
          "imac_class"    => $round['imac_class'],
          "imac_type"     => $round['imac_type'],
          "roundnum"      => $round['roundnum'],
          "description"   => $round['description'],
          "schedule_id"   => $round['schedule_id'],
          "sequences"     => $round['sequences'],
          "phase"         => $round['phase'],
          "status"        => $round['status'],
          "functions"     => $functions
        );
      }
    }
  } // get_rounds...
  
  elseif ($job == 'get_rounds_json') {
    // Get rounds
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
        $all_results = array();
        while ($round = $res->fetchArray(SQLITE3_ASSOC)){
            // For each round, now we need to get the flights
            $flight_stmt = $db->prepare("select * from flight where round_id = :round_id;");
            $flight_stmt->bindValue(':round_id',   $round["round_id"]);
            $flight_res = $flight_stmt->execute();
            $flights = array();
            while ($flight = $flight_res->fetchArray(SQLITE3_ASSOC)){
                // Get the sheets to add to this flight.
                unset($flight["round_id"]);
                $sheet_stmt = $db->prepare("select * from sheet where flight_id = :flight_id;");
                $sheet_stmt->bindValue(':flight_id',   $flight["flight_id"]);
                $sheet_res = $sheet_stmt->execute();
                $sheets = array();
                while ($sheet = $sheet_res->fetchArray(SQLITE3_ASSOC)){
                    // Get the scores to add to this sheet.
                    unset($sheet["flight_id"]);
                    $score_stmt = $db->prepare("select * from score where sheet_id = :sheet_id;");
                    $score_stmt->bindValue(':sheet_id',   $sheet["sheet_id"]);
                    $score_res = $score_stmt->execute();
                    $scores = array();
                    while ($score = $score_res->fetchArray(SQLITE3_ASSOC)){
                        unset($score["sheet_id"]);
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
            $all_results[] = $round;
        }
        $res->finalize();
        $statement->close();
        echo (json_encode($all_results, JSON_PRETTY_PRINT));
        exit;
    }
  } // get_rounds...
   elseif ($job == 'get_round') {
    // Get round  (imac_class, imac_type, round number).
    if (isset($_GET['imac_class'])){ $imac_class = $_GET['imac_class'];} else $imac_class = null;
    if (isset($_GET['imac_type'])) { $imac_type  = $_GET['imac_type']; } else $imac_type  = null;
    if (isset($_GET['roundnum']))  { $roundnum   = $_GET['roundnum'];  } else $roundnum   = null;


    $query =  "select s.description, s.schedule_id, r.imac_class, r.imac_type, r.roundnum, r.sequences, r.phase, r.status from round r left join schedule s on s.schedule_id = r.sched_id ";
    $query .= "where r.imac_class = :imac_class and r.imac_type = :imac_type and r.roundnum = :roundnum";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imac_class',    $imac_class);
        $statement->bindValue(':imac_type',     $imac_type);
        $statement->bindValue(':roundnum',      $roundnum);
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
          "imac_class"    => $round['imac_class'],
          "imac_type"     => $round['imac_type'],
          "roundnum"      => $round['roundnum'],
          "description"   => $round['description'],
          "schedule_id"   => $round['schedule_id'],
          "sequences"     => $round['sequences'],
          "phase"         => $round['phase'],
          "status"        => $round['status']
        );
      }
    }
  } // get_round...

  elseif ($job == 'get_nextrnds') {
    // Get rounds
    $query = "select imac_class, imac_type, (max(roundnum) + 1) as nextroundnum from round group by imac_class, imac_type;";
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
          "imac_class"         => $round['imac_class'],
          "imac_type"          => $round['imac_type'],
          "nextroundnum"       => $round['nextroundnum'],
        );
      }
    }
  } // get_roundlist...

  elseif ($job == 'get_schedlist') {
    // Get rounds
    $query = "select * from schedule order by imac_class;";
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
          "schedule_id" => $round['schedule_id'],
          "imac_class"  => $round['imac_class'],
          "imac_type"   => $round['imac_type'],
          "description" => $round['description'],
        );
      }
    }
  } // get_schedlist...
 
  elseif ($job == 'add_round') {
    // Add round
      
    $query =  "INSERT into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) ";
    $query .= "VALUES (:imac_class, :imac_type, :roundnum, :sched_id, :sequences, :phase );";

    if ($statement = $db->prepare($query)) {
      try {
        if (isset($_GET['imac_class'])) { $statement->bindValue(':imac_class',   $_GET['imac_class']);   };
        if (isset($_GET['imac_type']))  { $statement->bindValue(':imac_type',    $_GET['imac_type']);    };
        if (isset($_GET['roundnum']))   { $statement->bindValue(':roundnum',     $_GET['roundnum']);     };
        if (isset($_GET['schedule']))   { $statement->bindValue(':sched_id',     $_GET['schedule']);     };
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
    if (isset($_GET['imac_class'])){ $imac_class = $_GET['imac_class'];} else $imac_class = null;
    if (isset($_GET['imac_type'])){ $imac_type = $_GET['imac_type'];} else $imac_type = null;
    if (isset($_GET['roundnum'])){ $roundnum = $_GET['roundnum'];} else $roundnum = null;
    $query = "delete from round where imac_class = :imac_class and imac_type = :imac_type and roundnum = :roundnum and phase ='U';";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imac_class',    $imac_class);
        $statement->bindValue(':imac_type',     $imac_type);
        $statement->bindValue(':roundnum', $roundnum);
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
    if (isset($_GET['imac_class'])){ $imac_class = $_GET['imac_class'];} else $imac_class = null;
    if (isset($_GET['imac_type'])){ $imac_type = $_GET['imac_type'];} else $imac_type = null;
    if (isset($_GET['roundnum'])){ $roundnum = $_GET['roundnum'];} else $roundnum = null;
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
      $query = "update round set phase = 'O', starttime = strftime('%s','now') where imac_class = :imac_class and imac_type = :imac_type and roundnum = :roundnum and (phase ='U' or phase = 'P');";
      if ($statement = $db->prepare($query)) {
        try {
          $statement->bindValue(':imac_class',    $imac_class);
          $statement->bindValue(':imac_type',     $imac_type);
          $statement->bindValue(':roundnum',      $roundnum);
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
    if (isset($_GET['imac_class'])){ $imac_class = $_GET['imac_class'];} else $imac_class = null;
    if (isset($_GET['imac_type'])) { $imac_type  = $_GET['imac_type']; } else $imac_type  = null;
    if (isset($_GET['roundnum']))  { $roundnum   = $_GET['roundnum'];  } else $roundnum   = null;
 
    $query = "update round set phase = 'P' where imac_class = :imac_class and imac_type = :imac_type and roundnum = :roundnum and phase ='O';";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imac_class',    $imac_class);
        $statement->bindValue(':imac_type',     $imac_type);
        $statement->bindValue(':roundnum',      $roundnum);
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
    if (isset($_GET['imac_class'])){ $imac_class = $_GET['imac_class'];} else $imac_class = null;
    if (isset($_GET['imac_type'])) { $imac_type  = $_GET['imac_type']; } else $imac_type  = null;
    if (isset($_GET['roundnum']))  { $roundnum   = $_GET['roundnum'];  } else $roundnum   = null;
 
    $query = "update round set phase = 'D', finishtime = strftime('%s','now') where imac_class = :imac_class and imac_type = :imac_type and roundnum = :roundnum and phase ='P';";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':imac_class',    $imac_class);
        $statement->bindValue(':imac_type',     $imac_type);
        $statement->bindValue(':roundnum',      $roundnum);
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
    if (isset($_GET['prevroundnum'])){  $prevroundnum   = $_GET['prevroundnum'];} else $blOkToGo = false;
    if (isset($_GET['imac_class'])){    $imac_class     = $_GET['imac_class'];  } else $blOkToGo = false;
    if (isset($_GET['imac_type'])){     $imac_type      = $_GET['imac_type'];   } else $blOkToGo = false;
    if (isset($_GET['roundnum'])){      $roundnum       = $_GET['roundnum'];    } else $blOkToGo = false;
    if (isset($_GET['schedule'])){      $sched          = $_GET['schedule'];    } else $blOkToGo = false;
    if (isset($_GET['sequences'])){     $sequences      = $_GET['sequences'];   } else $blOkToGo = false;
    
    if (!blOkToGo) {
      $result  = 'error';
      $message = 'Unable to edit this round.  Some form data was missing.';
    } else {
      $query  = "update round set imac_class = :imac_class, imac_type = :imac_type, roundnum = :roundnum, sched_id = :sched_id, sequences = :sequences ";
      $query .= "where imac_class = :prevclass and imac_type = :prevtype and roundnum = :prevroundnum and phase ='U';";
      
      if ($statement = $db->prepare($query)) {
        try {
          $statement->bindValue(':prevclass',    $prevclass);
          $statement->bindValue(':prevtype',     $prevtype);
          $statement->bindValue(':prevroundnum', $prevroundnum);
          $statement->bindValue(':imac_class',   $imac_class);
          $statement->bindValue(':imac_type',    $imac_type);
          $statement->bindValue(':roundnum',     $roundnum);
          $statement->bindValue(':sched_id',     $sched);
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
$json_data = json_encode($data);
print $json_data;
