<?php

function beginTrans($failureMsg = "") {
    global $db;
    global $result;
    global $message;

    $message = null;
    if (!$db->exec("BEGIN TRANSACTION;")) {
        $db->enableExceptions(true);
        $result  = 'error';
        $message = $failureMsg . "Error was: " . $db->lastErrorMsg();
        return false;
    } else {
        return true;
    }
}

function commitTrans($failureMsg = "") {
    global $db;
    global $result;
    global $message;

    $message = null;
    if (!$db->exec("COMMIT;")) {
        $result  = 'error';
        $message = $failureMsg . "Error was: " . $db->lastErrorMsg();
        return false;
    } else {
        return true;
    }
}

function getRounds() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;
    $sqlite_data = null;

    // Get rounds
    $query = "select r.roundId, s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status "
           . "from round r left join schedule s on s.schedId = r.schedId order by r.imacClass, r.imacType, r.roundNum;";

    if ($statement = $db->prepare($query)) {
        try {
            if (!$res = $statement->execute()) {            
                $result  = 'error';
                $message = "Could not get round data. Err: " . $db->lastErrorMsg();
                error_log($message);
                goto db_rollback;
            }
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = 'query error: ' . $e->getMessage();
        goto db_rollback;
    }

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
    db_rollback:
}

function getRound() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;
    $sqlite_data = null;

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
            if (!$res = $statement->execute()) {            
                $result  = 'error';
                $message = "Could not get round data. Err: " . $db->lastErrorMsg();
                error_log($message);
                goto db_rollback;
            }
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();          
            error_log($message);
            goto db_rollback;
        }
    } else {
        $err = error_get_last();
        $message = $err['message'];
        error_log($message);
        goto db_rollback;
    }

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
    db_rollback:
}

function getFlightStatus($noteFlightId, $compId, $scheduleId, $pilotId) {
    global $db;
    global $result;
    global $message;

    $message = null;

    $query = "select r.roundId, r.phase from round r inner join "
            ."flight f on r.roundId = f.roundId and r.imacClass = :imacClass and f.noteFlightId = :noteFlightId";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':noteFlightId', $noteFlightId);
            $statement->bindValue(':imacClass', convertCompIDToClass($compId));
            $res = $statement->execute();
        } catch (Exception $e) {
            return "ERROR:X:DB Error.";
        }
    } else {
        return "ERROR:X:DB Error.";
    }
    
    if ($round = $res->fetchArray()){
        return "OK:" . $round["phase"] . ":Round found";
    } else {
        return "ERROR:X:Could not find round.";
    }
}

function getFlightsForRound($roundId) {
    global $db;
    global $result;
    global $message;

    $message = null;

    error_log("Getting flights for round " . $roundId);
    $query = "select * from flight where roundId = :roundId;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':roundId', $roundId);
            $res = $statement->execute();
        } catch (Exception $e) {
            return null;
        }
    } else {
        return null;
    }

    $flightArray = array();
    
    while ($flight = $res->fetchArray()){
        $thisFlight = array(
            "flightId"     => $flight["flightId"],
            "noteFlightId" => $flight["noteFlightId"],
            "sequenceNum"  => $flight["sequenceNum"],
            "sheets"       => getSheetsForFlight($flight["flightId"])
        );
        array_push($flightArray, $thisFlight);
    }
    return $flightArray;
}

function getSheetsForFlight($flightId) {
    global $db;
    global $result;
    global $message;

    $message = null;

    $query = "select * from sheet where flightId = :flightId;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':flightId', $flightId);
            $res = $statement->execute();
        } catch (Exception $e) {
            return null;
        }
    } else {
        return null;
    }

    $sheetArray = array();
    
    while ($sheet = $res->fetchArray()){
        $thisSheet = array(
            "sheetId"      => $sheet["sheetId"],
            "pilot"        => getPilot($sheet["pilotId"]),
            "judgeNum"     => $sheet["judgeNum"],
            "judgeName"    => $sheet["judgeName"],
            "scribeName"   => $sheet["scribeName"],
            "comment"      => $sheet["comment"],
            "mppFlag"      => $sheet["mppFlag"],
            "flightZeroed" => $sheet["flightZeroed"],
            "zeroReason"   => $sheet["zeroReason"],
            "scores"       => getScoresForSheet($sheet["sheetId"])
        );
        array_push($sheetArray, $thisSheet);
    }
    return $sheetArray;
}

function getScoresForSheet($sheetId) {
    global $db;
    global $result;
    global $message;

    $message = null;

    $query = "select * from score where sheetId = :sheetId;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':sheetId', $sheetId);
            $res = $statement->execute();
        } catch (Exception $e) {
            return null;
        }
    } else {
        return null;
    }

    $scoreArray = array();
    
    while ($score = $res->fetchArray()){
        $thisScore = array(
            "figureNum"    => $score["figureNum"],
            "scoreTime"    => $score["scoreTime"],
            "breakFlag"    => $score["breakFlag"],
            "score"        => $score["score"],
            "comment"      => $score["comment"]
        );
        array_push($scoreArray, $thisScore);
    }
    return $scoreArray;
}

function getPilots() {
    global $db;
    global $result;
    global $message;

    $message = null;

    $query = "select * from pilot;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            return null;
        }
    } else {
        return null;
    }

    $pilotArray = array();
    
    while ($pilot = $res->fetchArray()){
        $thisPilot = array(
            "pilotId"         => $pilot["pilotId"],
            "primaryId"       => $pilot["primaryId"],
            "secondaryId"     => $pilot["secondaryId"],
            "fullName"        => $pilot["fullName"],
            "airplane"        => $pilot["airplane"],
            "freestyle"       => $pilot["freestyle"],
            "imacClass"       => $pilot["imacClass"],
            "in_customclass1" => $pilot["in_customclass1"],
            "in_customclass2" => $pilot["in_customclass2"],
            "active"          => $pilot["active"]
        );
        array_push($pilotArray, $thisPilot);
    }
    return $pilotArray;
}


function getUsers() {
    global $db;
    global $result;
    global $message;

    $message = null;

    $query = "select * from user;";
    if ($statement = $db->prepare($query)) {
        try {
            if (!$res = $statement->execute()) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    } else {
        return null;
    }

    $userArray = array();
    
    while ($user = $res->fetchArray()){
        $thisUser = array(
            "userId"          => $user["userId"],
            "fullName"        => $user["fullName"],
            "password"        => $user["password"],
            "address"         => $user["address"]
        );
        array_push($userArray, $thisUser);
    }
    return $userArray;
}

function getPilot($pilotId) {
    global $db;
    global $result;
    global $message;

    $message = null;

    $query = "select * from pilot where pilotId = :pilotId;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':pilotId', $pilotId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            return null;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        return null;
    }

    if ($pilot = $res->fetchArray()){
        $sqlite_data = array(
            "pilotId"         => $pilot["pilotId"],
            "primaryId"       => $pilot["primaryId"],
            "secondaryId"     => $pilot["secondaryId"],
            "fullName"        => $pilot["fullName"],
            "airplane"        => $pilot["airplane"],
            "freestyle"       => $pilot["freestyle"],
            "imacClass"       => $pilot["imacClass"],
            "in_customclass1" => $pilot["in_customclass1"],
            "in_customclass2" => $pilot["in_customclass2"],
            "active"          => $pilot["active"]
        );
    } else {
        $sqlite_data = null;
    }

    $result  = 'success';
    $message = 'query success';
    
    return $sqlite_data;
}

function getFlownRounds() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;

    // Get the flown rounds as one big JSON object.
    // Keep as much of the non Score! like data out of it....

    $query = "select r.*, s.description from round r inner join schedule s on r.schedId = s.schedId where phase = 'D';";
    $query = "select * from round where phase = 'D';";
    //$query = "select r.*, s.description from round r inner join schedule s on r.schedId = s.schedId;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();
            return;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        return;
    }

    $roundArray = array();

    while ($round = $res->fetchArray()){
        $thisRound = array(
            "roundId"       => $round["roundId"],
            "flightLine"    => $round["flightLine"],
            "imacType"      => $round["imacType"], // Known, Unknown, Freestyle
            "imacClass"     => $round["imacClass"],
            "roundNum"      => $round["roundNum"],
            "compRoundNum"  => $round["compRoundNum"],
            "startTime"     => $round["startTime"],
            "finishTime"    => $round["finishTime"],
            "schedId"       => $round["schedId"],
            "sequences"     => $round["sequences"],
            "phase"         => $round["phase"],
            "status"        => $round["status"],
            "flights"       => getFlightsForRound($round["roundId"])
        );
        array_push($roundArray, $thisRound);
    }
    return $roundArray;
}

function getFlightLineData() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;
    $sqlite_data = null;

    // Get everything we have.    Send it back to the requestor as JSON.
    // Keep as much of the non Score! like data out of it....

    $sqlite_data = array(
        "flightLineId" => getStateValue("flightLineId"),
        "flightLineAPIVersion" => getFlightLineAPIVersion(),
        "flightLineName" => getStateValue("flightLineName"),
        "flightLineUrl" => getStateValue("flightLineUrl"),
        "users" => array(),
        "pilots" => array(),
        "rounds" => array()
    );

    $sqlite_data["users"] = getUsers();
    $sqlite_data["pilots"] = getPilots();
    $sqlite_data["rounds"] = getFlownRounds();

    return $sqlite_data;
}

function getRoundResults() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;
    $sqlite_data = null;

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
      if ($message == null) { $message = 'query error'; }
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
                $sheet_stmt = $db->prepare("select * from sheet where noteFlightId = :noteFlightId;");
                $sheet_stmt->bindValue(':noteFlightId',   $flight["noteFlightId"]);
                $sheet_res = $sheet_stmt->execute();
                $sheets = array();
                while ($sheet = $sheet_res->fetchArray(SQLITE3_ASSOC)){
                    // Get the scores to add to this sheet.
                    unset($sheet["noteFlightId"]);
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

    $message = null;
    $sqlite_data = null;

    // Get the full list of pilots for the round.
    // Note: each round has 1 flight per sequence.
    $query = "select r.*, p.pilotId, p.fullName, p.airplane, f.noteFlightId, f.sequenceNum "
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
        if ($message == null) { $message = 'query error'; }
    } else {
        $result  = 'success';
        $message = 'query success';
        while ($round = $res->fetchArray()) {
            $sequenceNum = $round['sequenceNum'];
            $btnId = $round['roundId'] . "_" . $round['pilotId'] . "_" . $round['noteFlightId'] . "_" . convertClassToCompID($round["imacClass"]);
            $functions  = '<div class="function_buttons"><ul>';
            $functions .= '<li class="function_set_next_flight_button"><a id="' . $btnId . '" data-pilotname="' . $round['fullName'] . '" data-roundid="' . $round['roundId'] . '" data-seqnum="' . $sequenceNum . '" data-pilotid="'   . $round['pilotId'] . '" data-noteflightid="'   . $round['noteFlightId'] . '">Sequence ' . $sequenceNum . '</a></li>';
            $functions .= '</ul></div>';
            $notehint = "Pilot:" . $round['pilotId'] . " Flight:" . $round['noteFlightId'] . " Comp:" . convertClassToCompID($round["imacClass"]) . " Schedule:" . $round["schedId"];
            $sqlite_data[] = array(
                "pilotId"       => $round['pilotId'],
                "fullName"      => $round['fullName'],
                "noteFlightId"  => $round['noteFlightId'],
                "functions"     => $functions,
                "noteHint"      => $notehint,
                "compId"        => convertClassToCompID($round["imacClass"])
            );
        }
    }
}

function getNextRndIds() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;
    $sqlite_data = null;

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
        if ($message == null) { $message = 'query error'; }
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

    $message = null;
    $sqlite_data = null;

    // Set the next flight (pilot, noteFlightId)
    // Note: each round has 1 flight per sequence.
    
    $imacClass = null;
    $compId = null;
    
    // Set next flight...   
    // Checks:
    //  - Round is open.
    //  - Pilot is valid for round.
    //  
    if (isset($_GET['noteFlightId']))  { $noteFlightId = $_GET['noteFlightId'];  } else $noteFlightId = null;
    if (isset($_GET['pilotId']))       { $pilotId      = $_GET['pilotId'];  }      else $pilotId = null;
    if (isset($_GET['roundId']))       { $roundId      = $_GET['roundId'];  }      else $roundId = null;
    if (isset($_GET['seqnum']))        { $sequenceNum  = $_GET['seqnum'];  }       else $sequenceNum = null;

    $query = "select imacClass from round where roundId = :roundId and phase = 'O';";
    $compId = 0;
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
        $result  = 'error';
        $message = $db->lastErrorMsg();
        return;
    }

    if ($res === FALSE) {
        $result  = 'error';
        if ($message == null) { $message = 'query error'; }
    } else {
        $round = $res->fetchArray();
        if (!$round) {
            $result  = 'error';
            $message = 'This round is not open.';
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
        $result  = 'error';
        $message = $db->lastErrorMsg();
        return;
    }

    $pilot = $res->fetchArray();
    if (!$pilot) {
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

 
    if (!beginTrans())
        goto db_rollback;

    // Now do the update

    $result = "";
    $query = "DELETE FROM nextFlight; ";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':compId', $compId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    $query = "INSERT INTO nextFlight(nextNoteFlightId, nextCompId, nextPilotId) "
             . "VALUES(:noteFlightId, :compId, :pilotId); ";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':noteFlightId', $noteFlightId);
            $statement->bindValue(':compId', $compId);
            $statement->bindValue(':pilotId', $pilotId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    if (commitTrans("There was a problem setting the next flight. ") ) {
        $result  = 'success';
        $message = 'Next flight set to ' . $noteFlightId . ' of comp ' . $compId . ' (' . $imacClass . ') with pilot ' . $pilotId . '.';
    }
    
    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }    
}

function getNextFlight() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;
    $sqlite_data = null;

    // Get the next flight data (seq, pilotname etc)
    // Note: each round has 1 flight per sequence.
    
    $imacClass = null;
    $compId = null;

    if (isset($_GET['roundId']))   { $roundId     = $_GET['roundId'];  }  else $roundId = null;
    
    $query = "select * from nextFlight nf inner join pilot p on nf.nextPilotId = p.pilotId where p.active = 1";
    // Make sure nf.compId = round.imacClass (convert)
    // Make sure pilot is active and in freestyle (if need be) or in imacClass.
    // Return pilot data, round data...
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();
            return;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        return;
    }

    $pilot = $res->fetchArray();
    if (!$pilot) {
        $result  = 'error';
        $message = 'There is no valid next flight scheduled.';
        return;
    }
    $nextNoteFlightId = $pilot["nextNoteFlightId"];
    $nextFlightClass = convertCompIDToClass($pilot["nextCompId"]);
    $pilotInFreestyle = $pilot["freestyle"];
    

    $query = "select * from flight f inner join round r on f.roundId = r.roundId where r.roundId = :roundId and f.noteFlightId = :nextNoteFlightId;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':roundId', $roundId);
            $statement->bindValue(':nextNoteFlightId', $nextNoteFlightId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();
            return;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        return;
    }

    $flight = $res->fetchArray();
    if (!$flight) {
        $result  = 'error';
        $message = 'There is no valid next flight scheduled.';
        return;
    }

    if ($nextFlightClass == $flight["imacClass"] || ($nextFlightClass == "Freestyle" && $pilotInFreestyle == 1)) {
        // All good!
    } else {
        $result  = 'error';
        $message = 'This pilot is not in the correct class for the next flight.';
        return;
    }

    // I think we have all we need!   Lets send it back.
    $result  = 'success';
    $message = 'query success';
    $sqlite_data["nextNoteFlightId"]      = $pilot['nextNoteFlightId'];
    $sqlite_data["nextPilotId"]           = $pilot['nextPilotId'];
    $sqlite_data["nextCompId"]            = $pilot['nextCompId'];
    $sqlite_data["nextPilotName"]         = $pilot['fullName'];
    $sqlite_data["nextSchedId"]           = $flight['schedId'];
    $sqlite_data["nextSequenceNum"]       = $flight['sequenceNum'];
    $sqlite_data["nextRoundId"]           = $flight['roundId'];
}

function getSchedlist() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;
    
    $message = null;
    $sqlite_data = null;

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
      if ($message == null) { $message = 'query error'; }
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

function getStateValue($key) {
    global $db;
    global $result;
    global $message;

    $message = null;

    $query = "select value from state where key = :key;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':key', $key);
            if (!$res = $statement->execute()) {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    } else {
        return null;
    }

    $state = $res->fetchArray();
    if (!$state) {
        // Null.   
        return null;
    } else {
        return $state["value"];
    }
}

function getFlightLineAPIVersion() {
    return "1.00";
}

function addRound() {
    global $db;
    global $result;
    global $message;
    
    $message = null;

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

    $flightLineId = getStateValue("flightLineId");
    if ($flightLineId < 1) {
        $flightLineId = null;
    }
    if (!beginTrans())
        goto db_rollback;

    $query =  "INSERT into round (flightLine, imacClass, imacType, roundNum, schedId, sequences, phase) ";
    $query .= "VALUES (:flightLine, :imacClass, :imacType, :roundNum, :schedId, :sequences, :phase );";

    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':flightLine', $flightLineId);
            $statement->bindValue(':imacClass',  $imacClass);
            $statement->bindValue(':imacType',   $imacType);
            $statement->bindValue(':roundNum',   $roundNum);
            $statement->bindValue(':schedId',    $schedule);
            $statement->bindValue(':sequences',  $sequences);
            $statement->bindValue(':phase',      'U');
            error_log($query);
            $res = $statement->execute();
            if (!$res || $db->lastErrorCode() != 0) {
                $result  = 'error';
                $message = 'query error: ' . $db->lastErrorMsg();
                goto db_rollback;
            }
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();   
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = "1:" . $db->lastErrorMsg();;
        goto db_rollback;
    }
    $newRoundId = $db->lastInsertRowID();

    $result = "";

    // Get the next flight id.
    $query = "select (max(noteFlightId) + 1) as newNoteFlightId "
           . "from flight f inner join round r on f.roundId = r.roundId "
           . "where r.imacClass = :imacClass";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':imacClass', $imacClass);
            $res = $statement->execute();

            $flight = $res->fetchArray();
            if (!$flight || $flight["newNoteFlightId"] == null) {
                // Null?
                if (($imacClass == "Freestyle") || ($imacType == "Freestyle"))  {
                    $newNoteFlightId = 91;
                } else {
                    $newNoteFlightId = 1;
                }
            } else {
                $newNoteFlightId = $flight["newNoteFlightId"];
            }
        } catch (Exception $e) {
          $result  = 'error';
          $message = 'query error: ' . $e->getMessage(); 
          goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = "2:" . $db->lastErrorMsg();;
        goto db_rollback;
    }

    for ($i = 1; $i <= $sequences; $i++) {
        $query = "INSERT INTO flight(noteFlightId, roundId, sequenceNum) "
               . "VALUES(:noteFlightId, :roundId, :sequenceNum); ";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':noteFlightId', $newNoteFlightId);
                $statement->bindValue(':roundId', $newRoundId);
                $statement->bindValue(':sequenceNum', $i);
                $res = $statement->execute();
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $result  = 'error';
            $message = "3:" . $db->lastErrorMsg();;
            goto db_rollback;
        }
        $newNoteFlightId++;
    }
    
    if (commitTrans("There was a problem adding the round. ") ) {
        $result  = 'success';
        $message = 'Inserted new round (' . $newRoundId . ') into class ' . $imacClass . '.';
    }

    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }
}

function editRound() {
    global $db;
    global $result;
    global $message;

    $message = null;

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

    // Sanity checks
    if ($imacType == "Freestyle") $imacClass = "Freestyle";
    if ($imacType != "Known" ) $sequences = 1;
    
    if (!blOkToGo) {
        $result  = 'error';
        $message = 'Unable to edit this round.  Some form data was missing.';
        return;
    }

    if (!beginTrans())
        goto db_rollback;
    
    $roundId = null;
    $query  = "select * from round ";
    $query .= "where imacClass = :prevclass and imacType = :prevtype and roundNum = :prevroundNum;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':prevclass',    $prevclass);
            $statement->bindValue(':prevtype',     $prevtype);
            $statement->bindValue(':prevroundNum', $prevroundNum);
            $res = $statement->execute();

            $round = $res->fetchArray();
            if (!$round || $round["roundId"] == null) {
                // Null?
                $result  = 'error';
                $message = 'Could not find the right round to edit.'; 
                goto db_rollback;
            } else {
                $roundId = $round["roundId"];
                if ($round["phase"] != "U") {
                    $result  = 'error';
                    $message = 'Cannot edit a round that is already open.'; 
                    goto db_rollback; 
                }
            }
        } catch (Exception $e) {
          $result  = 'error';
          $message = 'query error: ' . $e->getMessage(); 
          goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    $query  = "update round set imacClass = :imacClass, imacType = :imacType, roundNum = :roundNum, schedId = :schedId, sequences = :sequences ";
    $query .= "where imacClass = :prevclass and imacType = :prevtype and roundNum = :prevroundNum and phase ='U';";

    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':prevclass',    $prevclass);
            $statement->bindValue(':prevtype',     $prevtype);
            $statement->bindValue(':prevroundNum', $prevroundNum);
            $statement->bindValue(':imacClass',    $imacClass);
            $statement->bindValue(':imacType',     $imacType);
            $statement->bindValue(':roundNum',     $roundNum);
            $statement->bindValue(':schedId',      $sched);
            $statement->bindValue(':sequences',    $sequences);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();   
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    $query  = "delete from flight ";
    $query .= "where roundId = :roundId;";

    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':roundId', $roundId);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();   
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    $newNoteFlightId = 1;
    // Get the next flight id.
    $query = "select (max(noteFlightId) + 1) as newNoteFlightId "
           . "from flight f inner join round r on f.roundId = r.roundId "
           . "where imacClass = :imacClass";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':imacClass', $imacClass);
            $res = $statement->execute();

            $flight = $res->fetchArray();
            if (!$flight || $flight["newNoteFlightId"] == null) {
                // Null?
                if (($imacClass == "Freestyle") || ($imacType == "Freestyle")) {
                    $newNoteFlightId = 91;
                } else {
                    $newNoteFlightId = 1;
                }
            } else {
                $newNoteFlightId = $flight["newNoteFlightId"];
            }
        } catch (Exception $e) {
          $result  = 'error';
          $message = 'query error: ' . $e->getMessage(); 
          goto db_rollback;
        }
    } else {
        $err = $db->lastErrorMsg();
        $result  = 'error';
        $message = $err;
        goto db_rollback;
    }
    //echo "NFID: $newNoteFlightId\nSEQ:$sequences\nRnd:$roundId\n";

    for ($i = 1; $i <= $sequences; $i++) {
        $query = "INSERT INTO flight (noteFlightId, roundId, sequenceNum) "
               . "VALUES(:noteFlightId, :roundId, :sequenceNum); ";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':noteFlightId', $newNoteFlightId);
                $statement->bindValue(':roundId', $roundId);
                $statement->bindValue(':sequenceNum', $i);
                $res = $statement->execute();
                //echo "NFID: $newNoteFlightId\nSEQ:$i\nRnd:$roundId\n\n";
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $err = $db->lastErrorMsg();
            $result  = 'error';
            $message = $err;
            goto db_rollback;
        }
        $newNoteFlightId++;
    }

    if (commitTrans("There was a problem editing the round. ") ) {
        $result  = 'success';
        $message = 'Edited round (' . $roundId . ') sucessfully.';
    }
    
    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }    
    
}

function startRound() {
    global $db;
    global $result;
    global $message;

    $message = null;

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
        if ($message == null) { $message = 'query error'; }
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
            if ($message == null) { $message = 'query error'; }
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

    $message = null;

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
        if ($message == null) { $message = 'query error'; }
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

    $message = null;

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
        if ($message == null) { $message = 'query error'; }
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
    
    $message = null;

    // Delete round
    if (isset($_GET['imacClass'])){ $imacClass = $_GET['imacClass'];} else $imacClass = null;
    if (isset($_GET['imacType'])){ $imacType = $_GET['imacType'];} else $imacType = null;
    if (isset($_GET['roundNum'])){ $roundNum = $_GET['roundNum'];} else $roundNum = null;
    //$query = "delete from round where imacClass = :imacClass and imacType = :imacType and roundNum = :roundNum and phase ='U';";
    $query = "select * from round where imacClass = :imacClass and imacType = :imacType and roundNum = :roundNum;";
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
        $round = $res->fetchArray();
        if ($round["phase"] != 'U') {
            $message = "This round has already been opened.   It cannot be deleted.";
            $result  = 'error';
            return;
        }
    } else {
        $result  = 'error';
        $message = "Unknown DB error";
        return;
    }

    $db->exec("BEGIN TRANSACTION;");
    $query = "delete from round where roundId = :roundId;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':roundId',     $round["roundId"]);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = "Unknown DB error";
        goto db_rollback;
    }
    
    $query = "delete from flight where roundId = :roundId;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':roundId',     $round["roundId"]);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage();
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = "Unknown DB error";
        goto db_rollback;
    }
    
    db_rollback:
    if ($result == "error") {
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    } else {
        if (!$db->exec("COMMIT;")) {
            $err = $db->lastErrorMsg();
            $result  = 'error';
            $message = "There was a problem deleting round " . $round["roundId"] . ".  Error was: " . $err;
        } else {
            $result  = 'success';
            $message = 'Round ' . $round["roundId"] . ' has been deleted.';
        }
    }
}

function clearResults() {
    global $db;
    global $result;
    global $message;

    $message = null;

    if (!beginTrans())
        goto db_rollback;
    
    $query = "delete from score;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    $query = "delete from sheet;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }
    
    $query = "update round set phase = 'U';";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }
    
    if (commitTrans("Could not clear result data. ") ) {
        $result  = 'success';
        $message = 'The result data has been cleared.';
    }

    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }       
    return $sqlite_data;
}

function clearPilots() {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;

    $message = null;
    $sqlite_data = null;

    if (!beginTrans())
        goto db_rollback;
    
    $query = "delete from nextFlight;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    $query = "select count(*) as sheetCount from sheet;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    /*****
     * Allow this for now.   But after testing we really want to enforce the integrity.
     **/
    error_log("Checking for sheets." . $result);
    if ($sheet = $res->fetchArray()) {
        if ($sheet["sheetCount"] > 0) {
            $result  = 'error';
            $message = 'Cannot clear pilots while scores have been entered.'; 
            goto db_rollback;
        }
    }
    error_log("Checking for sheets." . $sheet["sheetCount"]);
    /**/

    $query = "delete from pilot ;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    if (commitTrans("Could not clear pilots. ") ) {
        $result  = 'success';
        $message = 'The pilots have been cleared.';
    }
    
    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }       
    return $sqlite_data;
}

function clearSchedules() {
    global $db;
    global $result;
    global $message;

    $message = null;
    $sqlite_data = null;
    
    if (!beginTrans())
        goto db_rollback;
    if (isset($_GET['scheduleType'])){ $schedType = $_GET['scheduleType'];} else $schedType = null;

    $query = "select schedId from schedule where imacType = :schedType;";
    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':schedType', $schedType);
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }
    
    while ($sched = $res->fetchArray()) {
        $query = "delete from figure where schedId = :schedId;";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':schedType', $sched["schedId"]);
                $res = $statement->execute();
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $result  = 'error';
            $message = $db->lastErrorMsg();
            goto db_rollback;
        }
    }

    $query = "delete from schedule where imacType = :schedType;";
    if ($statement = $db->prepare($query)) {
        try {
            $res = $statement->execute();
        } catch (Exception $e) {
            $result  = 'error';
            $message = 'query error: ' . $e->getMessage(); 
            goto db_rollback;
        }
    } else {
        $result  = 'error';
        $message = $db->lastErrorMsg();
        goto db_rollback;
    }

    if (commitTrans("Could not clear schedule data. ") ) {
        $result  = 'success';
        $message = 'Schedules have been cleared.';
    }
    
    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }       
    return $sqlite_data;
}

function postPilots($pilotsArray = null) {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;
    global $verboseMsgs;

    $message = null;
    $sqlite_data = null;
    if (is_null($pilotsArray))
        $pilotsArray = @json_decode(($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");

    if (!beginTrans())
        goto db_rollback;

    $result = "success";
    $verboseMsgs = array();


    if (is_null($pilotsArray)) {
        error_log("Could not decode JSON: " . json_last_error_msg());
        $result  = 'error';
        $message = "Could not decode JSON: " . json_last_error_msg();
        goto db_rollback;
    }

    error_log("Ready to upload pilots..." . ($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    foreach($pilotsArray as $pilotId => $pilot) {
        error_log("Inserting Pilot:$pilotId " . print_r($pilot, true));
        $query = "INSERT into pilot (pilotId, primaryId, secondaryId, fullName, airplane, freestyle, imacClass, in_customclass1, in_customclass2, active) "
                ."VALUES(:pilotId, :primaryId, :secondaryId, :fullName, :airplane, :freestyle, :imacClass, :in_customclass1, :in_customclass2, :active)";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':pilotId', $pilot->pilotId);
                $statement->bindValue(':primaryId', $pilot->primaryId);
                $statement->bindValue(':secondaryId', $pilot->secondaryId);
                $statement->bindValue(':fullName', $pilot->fullName);
                $statement->bindValue(':airplane', $pilot->airplane);
                $statement->bindValue(':freestyle', $pilot->freestyle);
                $statement->bindValue(':imacClass', $pilot->imacClass);
                $statement->bindValue(':in_customclass1', $pilot->in_customclass1);
                $statement->bindValue(':in_customclass2', $pilot->in_customclass2);
                $statement->bindValue(':active', $pilot->active);
                if (!$res = $statement->execute()) {            
                    $result  = 'error';
                    $message = "Could not insert Pilot: " . $pilot->fullName . " Err: " . $db->lastErrorMsg();
                    error_log($message);
                    goto db_rollback;
                } else {
                    $verboseMsgs[] = "Inserted Pilot Id: " . $pilot->pilotId . " Name: " . $pilot->fullName;
                    error_log("Inserted Pilot: " . $pilot->fullName);
                }
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $result  = 'error';
            $message = $db->lastErrorMsg();
            goto db_rollback;
        }
    }
    if (commitTrans("Could not add the pilots. ") ) {
        $result  = 'success';
        $message = 'Pilots have been added.';
    }
    
    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }       
    return $sqlite_data;
}

function getFlightScores($flightId, $pilotId) {
    // select s.*, f.sequenceNum 
    // from sheet s inner join flight f on s.flightId = f.flightId
    // where s.flightId = 2 and s.pilotId = 3;
    
    // Gets all of the sheets and scores from the given flight for the given pilot.
    
    global $db;
    global $result;
    global $message;
    global $sqlite_data;
    
    $message = null;
    $sqlite_data = null;

    // Get sheets
    $query = "select s.*, f.sequenceNum " .
             "from sheet s inner join flight f on s.flightId = f.flightId " .
             "where s.flightId = :flightId and s.pilotId = :pilotId;";

    if ($statement = $db->prepare($query)) {
        try {
            $statement->bindValue(':flightId', $flightId);
            $statement->bindValue(':pilotId', $pilotId);
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
      if ($message == null) { $message = 'query error'; }
    } else {
        $result  = 'success';
        $message = 'query success';
        while ($sheet = $res->fetchArray()) {
            
            $sheetArr = array(
                "sheetId"      => $sheet['sheetId'],
                "roundId"      => $sheet['roundId'],
                "flightId"     => $sheet['flightId'],
                "pilotId"      => $sheet['pilotId'],
                "judgeNum"     => $sheet['judgeNum'],
                "judgeName"    => $sheet['judgeName'],
                "scribeName"   => $sheet['scribeName'],
                "mppFlag"      => $sheet['mppFlag'],
                "flightZeroed" => $sheet['flightZeroed'],                
                "zeroReason"   => $sheet['zeroReason'],
                "sequenceNum"  => $sheet['sequenceNum'],
                "scores"       => array()
            );
                // Get sheets
            $query = "select * from score ".
                "where sheetId = :sheetId;";

            if ($score_statement = $db->prepare($query)) {
                try {
                    $score_statement->bindValue(':sheetId', $sheet['sheetId']);
                    $score_res = $score_statement->execute();
                } catch (Exception $e) {
                    $result  = 'error';
                    $message = 'query error: ' . $e->getMessage();          
                }
            } else {
                $score_res = FALSE;
                $err = error_get_last();
                $message = $err['message'];
            }

            if ($score_res === FALSE){
              $result  = 'error';
              if ($message == null) { $message = 'query error'; }
            } else {
                $result  = 'success';
                $message = 'query success';
                while ($score = $score_res->fetchArray()) {
                    $scoreArr = array(
                        "figureNum"    => $score['figureNum'],
                        "scoreTime"    => $score['scoreTime'],
                        "breakFlag"    => $score['breakFlag'],
                        "score"        => $score['score'],
                        "comment"      => $score['comment']
                    );
                    //error_log("Fig: " . print_r($scoreArr, true));
                    $sheetArr['scores'][] = $scoreArr;
                }
            }
            $sqlite_data[] = $sheetArr;
        }
    }
}

function postSequences($sequenceArray = null) {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;
    global $verboseMsgs;

    $message = null;
    $sqlite_data = null;
    if (is_null($sequenceArray)) {
        $sequenceArray = @json_decode(($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    }

    if (!beginTrans())
        goto db_rollback;

    $result = "success";
    $verboseMsgs = array();

    if (is_null($sequenceArray)) {
        error_log("Could not decode JSON: " . json_last_error_msg());
        $result  = 'error';
        $message = "Could not decode JSON: " . json_last_error_msg();
        goto db_rollback;
    }

    error_log("Ready to upload sequences...");
    
    //error_log("Ready to upload pilots..." . ($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    foreach($sequenceArray as $sequence) {


        // If there are rounds defined using this sequence, we must abort the delete, but can still replace later,...

        $query = "SELECT count(*) as roundcount FROM round where schedId = :schedId";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':schedId', $sequence->schedId);
                $res = $statement->execute();
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $message = $db->lastErrorMsg();
            goto db_rollback;
        }

        if ($res === FALSE) {
            $result  = 'error';
            if ($message == null) { $message = 'query error'; }
            goto db_rollback;
        } else {
            if (!$round = $res->fetchArray()) {
                $result  = 'error';
                $message = 'could not count the rounds!';
                goto db_rollback;

                error_log("Deleting sequence: " . $sequence->schedId);

                $query = "DELETE FROM figure WHERE schedId = schedId; ";
                if ($statement = $db->prepare($query)) {
                    try {
                        $statement->bindValue(':schedId', $sequence->schedId);
                        $res = $statement->execute();
                    } catch (Exception $e) {
                        $result  = 'error';
                        $message = 'query error: ' . $e->getMessage(); 
                        goto db_rollback;
                    }
                } else {
                    $result  = 'error';
                    $message = $db->lastErrorMsg();
                    goto db_rollback;
                }

                $query = "DELETE FROM schedule WHERE schedId = schedId; ";
                if ($statement = $db->prepare($query)) {
                    try {
                        $statement->bindValue(':schedId', $sequence->schedId);
                        $res = $statement->execute();
                    } catch (Exception $e) {
                        $result  = 'error';
                        $message = 'query error: ' . $e->getMessage(); 
                        goto db_rollback;
                    }
                } else {
                    $result  = 'error';
                    $message = $db->lastErrorMsg();
                    goto db_rollback;
                }
            } else {
                // The round is there, abort the delete.
                error_log("Skipping delete because a round exists for sequence: " . $sequence->schedId);
            }
        }
        
        error_log("Inserting/Updating Sequence: " . $sequence->schedId);
        $query = "INSERT or REPLACE into schedule (schedId, imacClass, imacType, description) "
                ."VALUES(:schedId, :imacClass, :imacType, :description)";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':schedId', $sequence->schedId);
                $statement->bindValue(':imacClass', $sequence->imacClass);
                $statement->bindValue(':imacType', $sequence->imacType);
                $statement->bindValue(':description', $sequence->description);

                if (!$res = $statement->execute()) {            
                    $result  = 'error';
                    $message = "Could not insert Sequence: " . $sequence->schedId . " Err: " . $db->lastErrorMsg();
                    error_log($message);
                    goto db_rollback;
                } else {
                    $verboseMsgs[] = "Inserted Sequence: " . $sequence->schedId;
                    error_log("Inserted Sequence: " . $sequence->schedId);
                }
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $result  = 'error';
            $message = $db->lastErrorMsg();
            goto db_rollback;
        }
        
        foreach($sequence->figures as $figure) {
            error_log("Inserting/Updating figure: " . $figure->figureNum . " for sequence: " . $sequence->schedId);
            $query = "INSERT or REPLACE into figure (figureNum, schedId, shortDesc, longDesc, spokenText, rule, k) "
                    ."VALUES(:figureNum, :schedId, :shortDesc, :longDesc, :spokenText, :rule, :k)";
            if ($statement = $db->prepare($query)) {
                try {
                    $statement->bindValue(':figureNum', $figure->figureNum);
                    $statement->bindValue(':schedId', $sequence->schedId);
                    $statement->bindValue(':shortDesc', $figure->shortDesc);
                    $statement->bindValue(':longDesc', $figure->longDesc);
                    $statement->bindValue(':spokenText', $figure->spokenText);
                    $statement->bindValue(':rule', $figure->rule);
                    $statement->bindValue(':k', $figure->k);

                    if (!$res = $statement->execute()) {            
                        $result  = 'error';
                        $message = "Could not insert figure: " . $figure->figureNum . " for sequence: " . $sequence->schedId . " Err: " . $db->lastErrorMsg();
                        error_log($message);
                        goto db_rollback;
                    } else {
                        $verboseMsgs[] = "Inserted figure: " . $figure->figureNum . " for sequence: " . $sequence->schedId;
                        error_log("Inserted figure: " . $figure->figureNum . " for sequence: " . $sequence->schedId);
                    }
                } catch (Exception $e) {
                    $result  = 'error';
                    $message = 'query error: ' . $e->getMessage(); 
                    goto db_rollback;
                }
            } else {
                $result  = 'error';
                $message = $db->lastErrorMsg();
                goto db_rollback;
            }
        }

    }
    if (commitTrans("Could not add the sequences. ") ) {
        $result  = 'success';
        $message = 'Sequences have been added.';
    }
    
    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        if ($message == null) { $message = 'query error'; }
    }       
    return $sqlite_data;
}

function postSheets($sheetJSON = null) {
    global $db;
    global $result;
    global $message;
    global $sqlite_data;
    global $verboseMsgs;

    $message = null;
    $sqlite_data = null;
    $result = "success";
    if (is_null($sheetJSON)) {
        $sheetArray = @json_decode(($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    } else {
        $sheetArray = @json_decode($sheetJSON);
    }
        
    //error_log("postSheets -> " . print_r($sheetArray, true));
    /***********/
    if (!beginTrans())
        goto db_rollback;

    $verboseMsgs = array();
    foreach($sheetArray as $sheet) {
        //error_log("Processing sheet: " . print_r($sheet, true));

        $query = "select r.roundId, f.flightId from round r inner join flight f on r.roundId = f.roundId and r.imacClass = :imacClass and f.noteFlightId = :noteFlightId";
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':imacClass', convertCompIDToClass($sheet->compId));
                $statement->bindValue(':noteFlightId', $sheet->noteFlightId);
                $res = $statement->execute();
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $message = $db->lastErrorMsg();
            goto db_rollback;
        }

        if ($res === FALSE) {
            $result  = 'error';
            if ($message == null) { $message = 'query error'; }
            goto db_rollback;
        } else {
            if (!$round = $res->fetchArray()) {
                $result  = 'error';
                $message = 'could not find the round!';
                goto db_rollback;
            }
        }

        $query = "select sheetId from sheet where roundId = :roundId and flightId = :flightId and pilotId = :pilotId and judgeNum = :judgeNum";
        //error_log ($query . " " . $round["roundId"] . " " . $round["flightId"] . " " . $sheet->pilotId . " ". $sheet->judgeNum);
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':roundId', $round["roundId"]);
                $statement->bindValue(':flightId', $round["flightId"]);
                $statement->bindValue(':pilotId', $sheet->pilotId);
                $statement->bindValue(':judgeNum', $sheet->judgeNum);
                $res = $statement->execute();
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $message = $db->lastErrorMsg();
            goto db_rollback;
        }

        if ($res === FALSE) {
            $result  = 'error';
            if ($message == null) { $message = 'query error'; }
            goto db_rollback;
        } else {
            $oldsheet = $res->fetchArray();
            
            if (isset($oldsheet["sheetId"])) {
                $sheetId = $oldsheet["sheetId"];
                $query =    "update sheet set roundId = :roundId, flightId = :flightId, pilotId = :pilotId, judgeNum = :judgeNum "
                            . "where sheetId = :sheetId;";
            } else {
                $sheetId = null;
                $query =    "insert into sheet (roundId, flightId, pilotId, judgeNum) "
                            . "values (:roundId, :flightId, :pilotId, :judgeNum);";
            }
            //error_log ($query . " " . $round["roundId"] . " " . $round["flightId"] . " " . $sheet->pilotId . " ". $sheet->judgeNum);
        }
        
        
        
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':pilotId',  $sheet->pilotId);
                $statement->bindValue(':roundId',  $round["roundId"]);
                $statement->bindValue(':flightId', $round["flightId"]);
                $statement->bindValue(':judgeNum', $sheet->judgeNum);
                if (!is_null($sheetId)) {
                    $statement->bindValue(':sheetId', $sheetId);                
                }
                if (!$res = $statement->execute()) {            
                    $result  = 'error';
                    $message = "Could not insert sheet. Err: " . $db->lastErrorMsg();
                    error_log($message);
                    goto db_rollback;
                } else {
                    if (is_null($sheetId)) {
                        $sheetId = $db->lastInsertRowID();
                    }
                    $verboseMsgs[] = "Inserted sheet with Id: " . $sheetId;
                    error_log("Inserted sheet with Id: " . $sheetId);
                }
            } catch (Exception $e) {
                $result  = 'error';
                $message = 'query error: ' . $e->getMessage(); 
                goto db_rollback;
            }
        } else {
            $result  = 'error';
            $message = $db->lastErrorMsg();
            goto db_rollback;
        }

        
        // Now insert the scores!
        foreach($sheet->scores as $score) {
            $query =    "replace into score (sheetId, figureNum, scoreTime, breakFlag, score, comment) "
                        . "values (:sheetId, :figureNum, :scoreTime, :breakFlag, :score, :comment);";

            //error_log ($query . " " . $sheetId . " " . $round["roundId"] . " " . $round["flightId"] . " " . $sheet->pilotId . " ". $sheet->judgeNum);

            if ($statement = $db->prepare($query)) {
                try {
                    $statement->bindValue(':sheetId',   $sheetId);
                    $statement->bindValue(':figureNum', $score->figureNum);
                    $statement->bindValue(':scoreTime', $score->scoreTime);
                    $statement->bindValue(':breakFlag', $score->breakFlag);
                    $statement->bindValue(':score',     $score->score);
                    $statement->bindValue(':comment',   $score->comment);
                    if (!$res = $statement->execute()) {            
                        $result  = 'error';
                        $message = "Could not insert score for figure " . $score->figureNum . " of sheet " . $sheetId . " Err: " . $db->lastErrorMsg();
                        error_log($message);
                        goto db_rollback;
                    } else {
                        $verboseMsgs[] = "Inserted score for figure " . $score->figureNum . " of sheet " . $sheetId;
                        error_log("Inserted score for figure " . $score->figureNum . " of sheet " . $sheetId);
                    }
                } catch (Exception $e) {
                    $result  = 'error';
                    $message = 'query error: ' . $e->getMessage(); 
                    goto db_rollback;
                }
            } else {
                $result  = 'error';
                $message = $db->lastErrorMsg();
                goto db_rollback;
            }
        }        
    }

    if (commitTrans("Could not add the sheet.") ) {
        $result  = 'success';
        $message = 'Sheet has been added.';
    }
    
    db_rollback:
    if ($result == "error"){
        $db->exec("ROLLBACK;");
        error_log("Rolling back sheet insert: " . $message);
        return false;
    }
    /***********/
    return true;
}