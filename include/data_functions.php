<?php

  
function getRounds() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    // Get rounds
    $query = "select r.roundId, s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status "
           . "from round r left join schedule s on s.schedId = r.schedId order by r.imacClass, r.imacType, r.roundNum;";

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

    if ($res === FALSE) {
        $result  = 'error';
        if (!isset($message)) { $message = 'query error'; }
    } else {
        $result  = 'success';
        $message = 'query success';
        while ($round = $res->fetchArray()) {
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
                "roundId"       => $round['roundId'],
                "imacClass"     => $round['imacClass'],
                "imacType"      => $round['imacType'],
                "roundNum"      => $round['roundNum'],
                "description"   => $round['description'],
                "schedId"       => $round['schedId'],
                "sequences"     => $round['sequences'],
                "phase"         => $round['phase'],
                "status"        => $round['status'],
                "functions"     => $functions
            );
        }
    }
}

function getRound() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

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
}

function getFlightline() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

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
}

function getRoundResults() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

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
}

function getRoundPilots() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    // Get the full list of pilots for the round.
    // Note: each round has 1 flight per sequence.
    $query = "select r.*, p.pilotId, p.fullName, p.airplane, f.flightId, f.sequenceNum "
        . "from round r "
        . "left join pilot p on p.imacClass = r.imacClass "
        . "left join flight f on f.roundId = r.roundId "
        . "where p.active = 1 and r.roundId = :roundId;";
    if (isset($_GET['roundId']))  { $roundId   = $_GET['roundId'];  } else $roundId = null;
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':roundId', $roundId);
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
        while ($round = $res->fetchArray()) {
            $sequenceNum = $round['sequenceNum'];
            $functions  = '<div class="function_buttons"><ul>';
            $functions .= '<li class="function_set_next_flight_button"><a data-roundid="' . $round['roundId'] . '" data-seqnum="' . $sequenceNum . '" data-pilotid="'   . $round['pilotId'] . '" data-flightid="'   . $round['flightId'] . '">Sequence ' . $sequenceNum . '</a></li>';
            $functions .= '</ul></div>';
            $notehint = "Pilot:" . $round['pilotId'] . " Flight:" . $round['flightId'] . " Comp:" . convertClassToCompID($round["imacClass"]);
            $sqlite_data[] = array(
                "pilotId"       => $round['pilotId'],
                "fullName"      => $round['fullName'],
                "flightId"      => $round['flightId'],
                "functions"     => $functions,
                "noteHint"      => $notehint
            );
        }
    }
}

function getNextRndIds() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

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
}

function setNextFlight() {
    global $db;
    global $result;
    global $message;

    // Set the next flight (pilot, flightId)
    // Note: each round has 1 flight per sequence.
    
    $imacClass = null;
    $compId = null;
    
    // Set next flight...   
    // Checks:
    //  - Round is open.
    //  - Pilot is valid for round.
    //  
    if (isset($_GET['flightId']))  { $flightId    = $_GET['flightId'];  } else $flightId = null;
    if (isset($_GET['pilotId']))   { $pilotId     = $_GET['pilotId'];  }  else $pilotId = null;
    if (isset($_GET['roundId']))   { $roundId     = $_GET['roundId'];  }  else $roundId = null;
    if (isset($_GET['seqnum']))    { $sequenceNum = $_GET['seqnum'];  }   else $sequenceNum = null;

    $query = "select imacClass from round where roundId = :roundId and phase = 'O';";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':roundId', $roundId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            return;
        }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
        return;
    }

    if ($res === FALSE) {
        $result  = 'error';
        if (!isset($message)) { $message = 'query error'; }
    } else {
        $round = $res->fetchArray();
        if (!$round) {
            $result  = 'error';
            $message = 'Round ' . $roundId . ' is not open.';
            return;
        } else {
            $imacClass = $round["imacClass"];
            // compId is the numeric form of the class (freestyle is also a kind of class...)
            $compId = convertClassToCompID($imacClass);
        }
    }

    $query = "select freestyle, imacClass, fullName from pilot where pilotId = :pilotId and active = 1;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':pilotId', $pilotId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();
            return;
        }
    } else {
        $res = FALSE;
        $err = error_get_last();
        $message = $err['message'];
        return;
    }

    if ($res === FALSE) {
        $result  = 'error';
        if (!isset($message)) { $message = 'query error'; }
        return;
    } else {
        $pilot = $res->fetchArray();
        if (!$round) {
            $result  = 'error';
            $message = 'Pilot ' . $pilotId . ' is not active or in the ' . $imacClass . ' class.';
            return;
        } else if ( ($imacClass == "Freestyle") && ($pilot["freestyle"] != 1) ) {
            $result  = 'error';
            $message = 'Pilot ' . $pilotId . ' is not registered for freestyle.';
            return;
        } else if ($pilot["imacClass"] != $imacClass && $imacClass != "Freestyle") {
            $result  = 'error';
            $message = 'Pilot ' . $pilotId . ' is not in the ' . $imacClass . ' class.';
            return;
        }
    }
 
    // Now do the update

    $result = "";
    if (!$db->exec("BEGIN TRANSACTION;")) {
        $res = FALSE;
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_set_next_flight;
    }

    $query = "REPLACE INTO state(key, value) "
             . "VALUES('nextCompId', :compId); ";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':compId', $compId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto end_set_next_flight;
        }
    } else {
        //$res = FALSE;
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_set_next_flight;
    }

    $query = "REPLACE INTO state(key, value) "
             . "VALUES('nextFlightId', :flightId); ";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':flightId', $flightId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto end_set_next_flight;
        }
    } else {
        //$res = FALSE;
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_set_next_flight;
    }

    $query = "REPLACE INTO state(key, value) "
             . "VALUES('nextPilotId', :pilotId); ";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':pilotId', $pilotId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();          
            goto end_set_next_flight;
        }
    } else {
        //$res = FALSE;
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_set_next_flight;
    }

    if (!$db->exec("COMMIT;")) {
        //$res = FALSE;
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_set_next_flight;
    } else {
        $result  = 'success';
        $message = 'Next flight set to ' . $flightId . ' of comp ' . $compId . ' (' . $imacClass . ') with pilot ' . $pilotId . '.';
    }

    end_set_next_flight:
    if ($result == "error") {
        $result  = 'error';
        $db->exec("ROLLBACK;");
        if (!isset($message)) { $message = 'query error'; }
    }
}

function getSchedlist() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;
    
        // Get schedules
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
        while ($round = $res->fetchArray()) {
            $sqlite_data[] = array(
                "schedId"     => $round['schedId'],
                "imacClass"   => $round['imacClass'],
                "imacType"    => $round['imacType'],
                "description" => $round['description'],
            );
        }
    }
}

function addRound() {
    global $db;
    global $result;
    global $message;
    
        // Add round
    
    // Insert into two tables...   First one is the round table.
    // Second is the flight table (1 flight row per sequence).
    // 
    // First, lets get a new flight ID...
    //

    if (isset($_GET['imacClass'])) $imacClass = $_GET['imacClass'];
    if (isset($_GET['imacType'])) $imacType = $_GET['imacType'];
    if (isset($_GET['roundNum'])) $roundNum = $_GET['roundNum'];
    if (isset($_GET['schedule'])) $schedule = $_GET['schedule'];
    if (isset($_GET['sequences'])) $sequences = $_GET['sequences'];
    
    // Sanity checks.
    if ($imacType == "Freestyle") $imacClass = "Freestyle";
    if ($imacType != "Known" ) $sequences = 1;

    if (!$db->exec("BEGIN TRANSACTION;")) {
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_add_round;
    }

    $query =  "INSERT into round (imacClass, imacType, roundNum, schedId, sequences, phase) ";
    $query .= "VALUES (:imacClass, :imacType, :roundNum, :schedId, :sequences, :phase );";

    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':imacClass', $imacClass);
            $statement->bindValue(':imacType',  $imacType);
            $statement->bindValue(':roundNum',  $roundNum);
            $statement->bindValue(':schedId',   $schedule);
            $statement->bindValue(':sequences', $sequences);
            $statement->bindValue(':phase',     'U');
            error_log($query);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();   
            goto end_add_round;
        }
    } else {
        $result  = 'error';
        $err = error_get_last();
        $message = $err['message'];
        goto end_add_round;
    }

    $newRoundId = $db->lastInsertRowID();

    $result = "";

    // Get the next flight id.
    $query = "select (max(flightid) + 1) as newFlightId from flight where imacClass = :imacClass";
    
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':imacClass', $imacClass);
            $res = $statement->execute();

            $flight = $res->fetchArray();
            if (!$flight) {
                // Null?
                if ($imacClass == "Freestyle") {
                    $newFlightId = 91;
                } else {
                    $newFlightId = 1;
                }
            } else {
                $newFlightId = $flight["newFlightId"];
            }
        } catch (Exception $e) {
          $result  = 'error';
          $message = 'query error: ' . $e->getMessage(); 
          goto end_add_round;
        }
    } else {
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_add_round;
    }

    for ($i = 1; $i <= $sequences; $i++) {
        $query = "INSERT INTO flight(flightId, imacClass, roundId, sequenceNum) "
               . "VALUES(:flightId, :imacClass, :roundId, :sequenceNum); ";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':flightId', $newFlightId);
                $statement->bindValue(':imacClass', $imacClass);
                $statement->bindValue(':roundId', $newRoundId);
                $statement->bindValue(':sequenceNum', $i);
                $res = $statement->execute();
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto end_add_round;
            }
        } else {
            $err = error_get_last();
            $result  = 'error';
            $message = $err['message'];
            goto end_add_round;
        }
        $newFlightId++;
    }
    
    if (!$db->exec("COMMIT;")) {
        //$res = FALSE;
        $err = error_get_last();
        $result  = 'error';
        $message = $err['message'];
        goto end_add_round;
    } else {
        $result  = 'success';
        $message = 'Next flight set to ' . $flightId . ' of comp ' . $compId . ' (' . $imacClass . ') with pilot ' . $pilotId . '.';
    }

    end_add_round:
    if ($result == "error"){
        $result  = 'error';
        $db->exec("ROLLBACK;");
        if (!isset($message)) { $message = 'query error'; }
    }
}

function editRound() {
    global $db;
    global $result;
    global $message;

    // Edit round
    $blOkToGo = true;
    if (isset($_GET['prevclass'])){     $prevclass      = $_GET['prevclass'];   } else $blOkToGo = false;
    if (isset($_GET['prevtype'])){      $prevtype       = $_GET['prevtype'];    } else $blOkToGo = false;
    if (isset($_GET['prevroundNum'])){  $prevroundNum   = $_GET['prevroundNum'];} else $blOkToGo = false;
    if (isset($_GET['imacClass'])){     $imacClass      = $_GET['imacClass'];   } else $blOkToGo = false;
    if (isset($_GET['imacType'])){      $imacType       = $_GET['imacType'];    } else $blOkToGo = false;
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
}

function startRound() {
    global $db;
    global $result;
    global $message;

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

        if ($res === FALSE) {
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
}

function pauseRound() {
    global $db;
    global $result;
    global $message;

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
}

function finishRound() {
    global $db;
    global $result;
    global $message;

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
}

function deleteRound() {
    global $db;
    global $result;
    global $message;
    
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
}