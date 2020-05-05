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

include_once  __DIR__ . '/../../include/functions.php';

function createEmptyResultObject() {
    return array(
      "result"          => null,
      "message"         => null,
      //"requestId"       => null,
      "requestTime"     => null,
      "verboseMsgs"     => array(),
      //"source"          => null,
      "data"            => null
    );
}

function mergeResultMessages(&$resultObj, $resultObjToAppend) {
    if (!empty($resultObjToAppend["verboseMsgs"])) {
        $resultObj["verboseMsgs"] = array_merge ($resultObj["verboseMsgs"], $resultObjToAppend["verboseMsgs"]);
    }
    if ($resultObjToAppend["message"] != null) {
        $resultObj["verboseMsgs"][] = $resultObjToAppend["message"];
    }
    switch ($resultObjToAppend["result"]) {
        case "error":
            $resultObj["result"] = "error";
            $resultObj["message"] = $resultObjToAppend["message"];
            break;

        case "warn":
        case "warning":
            if ($resultObj["result"] === "success") {
                $resultObj["result"] = "warn";
                $resultObj["message"] = $resultObjToAppend["message"];
            }
            break;
    }
}

function beginTrans(&$resultObj, $failureMsg = "") {
    global $db;

    if (!$db->exec("BEGIN TRANSACTION;")) {
        $db->enableExceptions(true);
        $resultObj["result"] = 'error';
        $resultObj["message"] = $failureMsg . "Error was: " . $db->lastErrorMsg();
        return false;
    } else {
        return true;
    }
}

function commitTrans(&$resultObj, $failureMsg = "") {
    global $db;
    if (!$db->exec("COMMIT;")) {
        $resultObj["result"] = 'error';
        $resultObj["message"] = $failureMsg . "Error was: " . $db->lastErrorMsg();
        return false;
    } else {
        return true;
    }
}

function rollbackTrans(&$resultObj, $failureMsg = "") {
    global $db;
    if (!$db->exec("ROLLBACK;")) {
        $resultObj["result"] = 'error';
        $resultObj["message"] = $failureMsg . "Error was: " . $db->lastErrorMsg();
        return false;
    } else {
        return true;
    }
}

function doSQL (&$resultObj, $query, $paramArr = null) {
    global $db, $logger;

    try {
        if ($statement = $db->prepare($query)) {
            if (isset($paramArr) && is_array($paramArr)) {
                foreach ($paramArr as $key => $value) {
                    $statement->bindValue(":$key", $value);
                }
            }
            if (!$res = $statement->execute()) {
                $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
                $resultObj["result"]  = 'error';
                $resultObj["message"] = "There was an error executing the database call in function " . $bt[1]["function"] . ".  See logs for more detailed info.";
                array_push($resultObj["verboseMsgs"], ("In " . $bt[1]["function"] . ": Could not get data. Err: " . $db->lastErrorMsg()));
                $logger->error($resultObj["message"]);
                return false;
            }
        } else {
            $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
            $resultObj["result"]  = 'error';
            $resultObj["message"] = "There was an error executing the database call in function " . $bt[1]["function"] . ".  See logs for more detailed info.";
            array_push($resultObj["verboseMsgs"], ("In " . $bt[1]["function"] . ": Could not get data. Err: " . $db->lastErrorMsg()));
            return false;
        }
    } catch (Exception $e) {
        $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $resultObj["result"]  = 'error';
        $resultObj["message"] = "There was an error executing the database call in function " . $bt[1]["function"] . ".  See logs for more detailed info.";
        array_push($resultObj["verboseMsgs"], ("In " . $bt[1]["function"] . ": query error: " . $e->getMessage()));
        $logger->error($resultObj["message"]);
        return false;
    }
    return $res;
}

function apiJSONTest(&$resultObj, $qs) {
    global $logger;
    // Test API...   Just send back the JSON Object that was sent to us, plus auth data...
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    // Lets get the auth data to add...

    global $jwtkey;
    $token = (isset($_COOKIE['FlightlineAuthToken']) ? $_COOKIE['FlightlineAuthToken'] : null);

    if (!is_null($token)) {
        require_once('jwt.php');
        try {
            $payload = JWT::decode($token, $jwtkey, array('HS256'));
            $resultObj['data']['token'] = $token;
            $resultObj['data']['authdata'] = $payload;
        }
        catch(Exception $e) {
            $resultObj["verboseMsgs"][] = 'There was an error decoding the token: ' . $e->getMessage();
        }
    } else {
        $resultObj["data"]["authdata"] = 'Unauthorised';
    }

    $resultObj["data"]["payload"] = @json_decode((($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}"), true);
    $resultObj["data"]["method"] = $_SERVER['REQUEST_METHOD'];
    $resultObj["data"]["querystring"] = $qs;
    $resultObj["result"]  = 'success';
    $resultObj["message"]  = 'API JSON Test - you sent me the payload';
    $logger->debug("In apiJSONTest.", $resultObj);
}

function getRounds(&$resultObj) {
    // Get rounds
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select r.roundId, s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status "
        . "from round r left join schedule s on s.schedId = r.schedId order by r.imacClass, r.imacType, r.roundNum;";

    $res = doSQL($resultObj, $query);
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();
    while ($round = $res->fetchArray()) {
        $functions  = '<div class="function_buttons"><ul>';
        switch($round["phase"]) {
            case "U":
                $functions .= '<li class="function_start"><a data-imacclass="'  . $round['imacClass'] . '" data-schedid="'  . $round['schedId'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '" data-phase="' . $round['phase'] . '"><i class="fas fa-play"></i></a></li>';
                $functions .= '<li class="function_edit"><a data-imacclass="'   . $round['imacClass'] . '" data-schedid="'  . $round['schedId'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '"><i class="fas fa-edit"></i></a></li>';
                $functions .= '<li class="function_delete"><a data-imacclass="' . $round['imacClass'] . '" data-schedid="'  . $round['schedId'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '"><i class="fas fa-trash"></i></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
            case "O":
                $functions .= '<li class="function_pause"><a data-imacclass="'   . $round['imacClass'] . '" data-schedid="'  . $round['schedId'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '" data-phase="' . $round['phase'] . '"><i class="fas fa-pause"></i></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_scores"><a data-roundid="'  . $round['roundId'] . '"><i class="fas fa-poll"></i></a></li>';
                break;
            case "P":
                $functions .= '<li class="function_start"><a data-imacclass="'   . $round['imacClass'] . '" data-schedid="'  . $round['schedId'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '"><i class="fas fa-play"></i></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_finish"><a data-imacclass="'  . $round['imacClass'] . '" data-schedid="'  . $round['schedId'] . '" data-imactype="' . $round['imacType'] . '" data-roundnum="' . $round['roundNum'] . '" data-phase="' . $round['phase'] . '"><i class="fas fa-check"></i></a></li>';
                $functions .= '<li class="function_scores"><a data-roundid="'  . $round['roundId'] . '"><i class="fas fa-poll"></i></a></li>';
                break;
            case "D":
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_scores"><a data-roundid="'  . $round['roundId'] . '"><i class="fas fa-poll"></i></a></li>';
                break;
        }
        $functions .= '</ul></div>';

        $resultObj["data"][] = array(
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

    $resultObj["result"]  = 'success';
    $resultObj["message"]  = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

function getRound(&$resultObj, $paramArray = null) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();
    if ($paramArray === null) {
        // Go old school...   With the query string...
        if (isset($_GET['imacClass'])) { $imacClass = $_GET['imacClass'];}  else $imacClass = null;
        if (isset($_GET['imacType']))  { $imacType  = $_GET['imacType']; }  else $imacType  = null;
        if (isset($_GET['roundNum']))  { $roundNum  = $_GET['roundNum'];  } else $roundNum   = null;
        if (isset($_GET['roundId']))   { $roundId   = $_GET['roundId'];  }  else $roundId   = null;
    } else {
        $roundId = isset($paramArray["roundId"]) ? $paramArray["roundId"] : null;
        $imacType = isset($paramArray["imacType"]) ? $paramArray["imacType"] : null;
        $imacClass = isset($paramArray["imacClass"]) ? $paramArray["imacClass"] : null;
        $roundNum = isset($paramArray["roundNum"]) ? $paramArray["roundNum"] : null;
    }

    if ($roundId === null) {
        if ($imacType === "Freestyle") {
            $query = "select r.roundId, s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status from round r left join schedule s on s.schedId = r.schedId "
                . "where r.imacType like :imacType and r.roundNum = :roundNum";
        } else {
            $query = "select r.roundId, s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status from round r left join schedule s on s.schedId = r.schedId "
                . "where r.imacClass like :imacClass and r.imacType like :imacType and r.roundNum = :roundNum";
        }
    } else {
        $query =  "select r.roundId, s.description, s.schedId, r.imacClass, r.imacType, r.roundNum, r.sequences, r.phase, r.status from round r left join schedule s on s.schedId = r.schedId "
                . "where r.roundId = :roundId";
    }

    $pArr = array(
        "imacClass" => $imacClass,
        "imacType" => $imacType,
        "roundNum" => $roundNum,
        "roundId" => $roundId
    );
    $res = doSQL($resultObj, $query, $pArr);
    if ($res === false)
        goto db_rollback;

    while ($round = $res->fetchArray()){
        $resultObj["data"] = array(
            "roundId"       => $round['roundId'],
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
    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

function getPilotsForRound(&$resultObj, $paramArr) {
    //$roundId = null, $pilotId = null, $blIsFreestyleRound = false) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();


    if (isset($paramArr) && is_array($paramArr)) {
        $roundId = isset($paramArr["roundId"]) ? $paramArr["roundId"] : null;
        $pilotId = isset($paramArr["pilotId"]) ? $paramArr["pilotId"] : null;
        $blIsFreestyleRound = isset($paramArr["blIsFreestyleRound"]) ? $paramArr["blIsFreestyleRound"] : false;
    } else {
        $resultObj["result"]  = 'error';
        $resultObj["message"] = "Please supply the parameter array.";
        array_push($resultObj["verboseMsgs"], "ERROR: Please supply the parameter array.");
        goto db_rollback;
    }

    // Get the full list of pilots for the round.
    // If it's freestyle, use a special query..

    if ($blIsFreestyleRound) {
        $query = "select p.* "
            . "from pilot p "
            . "where p.freestyle = 1 and p.active = 1 ";
    } else {
        // Note: each round has 1 flight per sequence.
        $query = "select p.* "
            . "from pilot p inner join round r on p.imacClass = r.imacClass "
            . "where p.active = 1 and r.roundId = :roundId ";
    }
    
    if ($pilotId !== null) {
        $query .= "and p.pilotId = :pilotId;";
    } else {
        $query .= ";";
    }

    $pArr = array(
        "roundId" => $roundId,
        "pilotId" => $pilotId
    );
    $res = doSQL($resultObj, $query, $pArr);
    if ($res === false)
        goto db_rollback;

    $pilotCount = 0;
    while ($row = $res->fetchArray()) {
        $pilotCount++;
        $resultObj["data"][] = array(
            "pilotId"           => $row['pilotId'],
            "primaryId"         => $row['primaryId'],
            "fullName"          => $row['fullName'],
            "airplane"          => $row['airplane'],
            "freestyle"         => $row['freestyle'],
            "imacClass"         => $row['imacClass'],
            "active"            => $row['active'],
            "in_customclass1"   => $row['in_customclass1'],
            "in_customclass2"   => $row['in_customclass2']
        );
    }
    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $resultObj["verboseMsgs"][] = "There are " . $pilotCount . " pilots for this round.";
    $res->finalize();
    db_rollback:
}

/**
 * @param $resultObj
 * @param $roundId
 * @param $pilotId
 * @param null $flightId
 * @param null $sequenceNum
 * @return array
 */
function getPilotSheetsForRound(&$resultObj, $roundId, $pilotId, $flightId = null, $sequenceNum = null) {
    // getPilotSheetsForRound..
    // We must have pilotId AND roundId
    // We can then optionally have:
    //    flightId - if we only want one flight data.
    //    sequenceNum - Again, only sequence X from the round.

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    if ($flightId !== null) {
        $query = " select s.*, f.noteFlightId, f.sequenceNum from sheet s inner join flight f on s.flightId = f.flightId "
               . " where s.pilotId = :pilotId and s.roundId = :roundId and s.flightId = :flightId;";
    } else if ($sequenceNum !== null) {
        $query = " select s.*, f.noteFlightId, f.sequenceNum from sheet s inner join flight f on s.flightId = f.flightId "
               . " where s.pilotId = :pilotId and s.roundId = :roundId and f.sequenceNum = :sequenceNum;";
    } else {
        $query = " select s.*, f.noteFlightId, f.sequenceNum from sheet s inner join flight f on s.flightId = f.flightId "
               . " where s.pilotId = :pilotId and s.roundId = :roundId;";
    }

    $pArr = array(
        "roundId" => $roundId,
        "pilotId" => $pilotId,
        "flightId" => $flightId,
        "sequenceNum" => $sequenceNum
    );
    $res = doSQL($resultObj, $query, $pArr);
    if ($res === false)
        goto db_rollback;


    $resultObj['data'] = array();
    while ($row = $res->fetchArray()){
        $sheet_data = array(
            "pilotId"           => $row['pilotId'],
            "flightId"          => $row['flightId'],
            "sheetId"           => $row['sheetId'],
            "judgeNum"          => $row['judgeNum'],
            "judgeName"         => $row['judgeName'],
            "scribeName"        => $row['scribeName'],
            "comment"           => $row['comment'],
            "mppFlag"           => $row['mppFlag'],
            "flightZeroed"      => $row['flightZeroed'],
            "zeroReason"        => $row['zeroReason'],
            "phase"             => $row['phase'],
            "flags"             => $row['flags'],
            "noteFlightId"      => $row['noteFlightId'],
            "sequenceNum"       => $row['sequenceNum']
        );
        array_push($resultObj['data'], $sheet_data);
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return ($resultObj['data']);
}

function getScoresForRound(&$resultObj, $paramArray = null) {

    // getScoresForRound..
    // We must have roundId or imacClass+imacType+roundNum
    // We can then optionally have:
    //    flightId - if we only want one flight data.
    //    pilotId - if we only want one pilots data.

    global $logger;

    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $roundId = isset($paramArray["roundId"]) ? $paramArray["roundId"] : null;
    $imacType = isset($paramArray["imacType"]) ? $paramArray["imacType"] : null;
    $imacClass = isset($paramArray["imacClass"]) ? $paramArray["imacClass"] : null;
    $roundNum = isset($paramArray["roundNum"]) ? $paramArray["roundNum"] : null;
    $flightId = isset($paramArray["flightId"]) ? $paramArray["flightId"] : null;
    $sequenceNum = isset($paramArray["sequenceNum"]) ? $paramArray["sequenceNum"] : null;
    $pilotId = isset($paramArray["pilotId"]) ? $paramArray["pilotId"] : null;

    $roundResultObj = createEmptyResultObject();
    getRound($roundResultObj, array(
        "roundId" => $roundId,
        "imacType" => $imacType,
        "imacClass" => $imacClass,
        "roundNum" => $roundNum
    ));
    mergeResultMessages($resultObj, $roundResultObj);

    $round_data = $roundResultObj["data"];
    if (count($round_data) == 0) {
        // Round does not exist...
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'query success';
        $resultObj["data"] = array();
        array_push($resultObj["verboseMsgs"], ("INFO: Round does not exist."));
        goto db_rollback;
    } else {
        $schedResultObj = createEmptyResultObject();
        $round_data['schedule'] = getSchedule($schedResultObj, $round_data['schedId'], true);
        mergeResultMessages($resultObj, $schedResultObj);
    }

    $pArr = array(
        "roundId" => $roundId,
        "pilotId" => $pilotId,
        "blIsFreestyleRound" => true
        // ToDo: Fix this!!!!    Who cares if it is a freestyle round..  Let getPilotsForRound fix it...
    );

    if ($round_data['imacType'] === "Freestyle") {
        $pArr["blIsFreestyleRound"] = true;
    } else {
        $pArr["blIsFreestyleRound"] = false;
    }
    $pilotsResultObj = createEmptyResultObject();
    getPilotsForRound($pilotsResultObj, $pArr);
    mergeResultMessages($resultObj, $pilotsResultObj);

    $pilot_data = $pilotsResultObj["data"];


    foreach ($pilot_data as &$pilot) {
        // Now, we depending on our parameters, we might only want one pilot's data...
        if (isset($pilotId) && $pilotId != $pilot['pilotId']) {
            continue;
        }
        $sheetResultObj = createEmptyResultObject();
        getPilotSheetsForRound($sheetResultObj, $roundId, $pilot['pilotId'], $flightId, $sequenceNum);
        $pilot['sheets'] = $sheetResultObj["data"];
        mergeResultMessages($resultObj, $sheetResultObj);
        foreach ($pilot['sheets'] as &$sheet) {
            // We know the pilot ID...  Remove it.
            unset ($sheet['pilotId']);
            $scoresResultObj = createEmptyResultObject();
            getScoresForSheet($scoresResultObj, $sheet['sheetId']);
            $sheet['scores'] = $scoresResultObj["data"];
            mergeResultMessages($resultObj, $scoresResultObj);

            // Find the mpp (if it's there) and set it in the sheet.   Also remove it from the results.
            foreach ($sheet['scores'] as $idx => &$score) {
                if (isset($score['mppFlag'])) {
                    $sheet['mppFlag'] = $score['mppFlag'];
                    unset ($sheet['scores'][$idx]);
                }
            }
        }
    }

    $round_data["pilots"] = $pilot_data;
    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $resultObj["data"] = $round_data;
    $logger->debug("Returning from getScoresForRound.", $resultObj);

    db_rollback:
}

function getFlightOrderForRound(&$resultObj, $roundId) {
    // After the scores are entered, we know what order they flew in...
    // This will give us the list of pilot Ids in order from first to last.
    // Pilots who did not fly will not be included.

    // If we don't have a pilot ID, just choose the one with the most recent data entered...
    // This is a todo...

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query  = "select distinct f.flightId, f.sequenceNum, sh.pilotId, p.fullName from score sc inner join sheet sh on sh.sheetId = sc.sheetId "
            . "    inner join pilot p on sh.pilotId = p.pilotId "
            . "    inner join flight f on sh.flightId = f.flightId "
            . "    where sh.roundId = :roundId "
            . "    order by scoreTime;";

    $pArr = array(
        "roundId" => $roundId
    );
    $res = doSQL($resultObj, $query, $pArr);
    if ($res === false)
        goto db_rollback;

    $resultObj['data'] = array();
    $i = 1;
    while ($row = $res->fetchArray()){
        $thisPilot = array(
            "flightOrder"   => $i++,
            "flightId"      => $row["flightId"],
            "pilotId"       => $row["pilotId"],
            "sequenceNum"   => $row["sequenceNum"],
            "fillName"      => $row["fullName"]
        );
        array_push($resultObj['data'], $thisPilot);
    }
    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

function getSheetIdsForRound(&$resultObj, $roundId) {
    // After the scores are entered, we know what order they flew in...
    // This will give us the list of sheet Ids in order from first to last.
    // Pilots who did not fly will not be included.


    // If we don't have a pilot ID, just choose the one with the most recent data entered...
    // This is a todo...

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query  = "select distinct f.sequenceNum, sh.pilotId, p.fullName from score sc inner join sheet sh on sh.sheetId = sc.sheetId "
            . "    inner join pilot p on sh.pilotId = p.pilotId "
            . "    inner join flight f on sh.flightId = f.flightId "
            . "    where sh.roundId = :roundNum "
            . "    order by scoreTime;";

    $pArr = array(
        "roundId" => $roundId
    );
    $res = doSQL($resultObj, $query, $pArr);
    if ($res === false)
        goto db_rollback;

    $resultObj['data'] = array();

    $i = 1;
    while ($row = $res->fetchArray()){
        $thisPilot = array(
            "flightOrder"   => $i++,
            "pilotId"       => $row["pilotId"],
            "sequenceNum"   => $row["sequenceNum"],
            "fillName"      => $row["fullName"]
        );
        array_push($resultObj['data'], $thisPilot);
    }
    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

/**********
 * This is for import-notaumatic...   It's also in data_functions, which this file will eventually replace.
 * Just comment it out for now.
 *

function getFlightStatus($noteFlightId, $compId, $scheduleId, $pilotId) {
    global $db;
    global $result;
    global $message;

    $message = null;
    $imacClass = convertCompIDToClass($compId);
    
    if ($imacClass === "Freestyle" ) {
        // For now these are the same...
        $query = "select r.roundId, r.phase, r.imacType, r.schedId from round r inner join "
               . "flight f on r.roundId = f.roundId and r.imacClass = :imacClass and f.noteFlightId = :noteFlightId ";
    } else {
        $query = "select r.roundId, r.phase, r.imacType, r.schedId from round r inner join "
               . "flight f on r.roundId = f.roundId and r.imacClass = :imacClass and f.noteFlightId = :noteFlightId ";
    }
    
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
        // Round found...  Lets check the details!
        // Is the schedule OK?
        if ($round["schedId"] != $scheduleId) {
            return "ERROR:X:Incorrect schedule.";
        }
        
        // Is the pilot in there?
        $resultObj = createEmptyResultObject();
        getRoundPilotFlights($resultObj, $round["roundId"], $round["imacType"]);
        $blFoundPilot = false;
        foreach($resultObj["data"] as $pilot) {
            if ($pilot["pilotId"] == $pilotId) {
                $blFoundPilot = true;
            }
        }
        if ($blFoundPilot) {
            return "OK:" . $round["phase"] . ":Round found";
        } else {
            return "ERROR:X:Pilot " . $pilotId . " not in this round.";
        }
    } else {
        return "ERROR:X:Could not find round.";
    }
}
*******/
/*******
 * @param $resultObj - The result object.
 * @param $roundId   - The ID of the round that we wish to get the results from.
 * @return array     - The array of data to be sent back.
 */

function getFlightsForRound(&$resultObj, $roundId) {
    // Get all of the flights associated with this round.   Including the sheets.
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    error_log("Getting flights for round " . $roundId);
    $query = "select * from flight where roundId = :roundId;";

    $res = doSQL($resultObj, $query,  array("roundId" => $roundId));
    if ($res === false)
        goto db_rollback;

    $resultObj['data'] = array();
    $sheetResultObj = createEmptyResultObject();

    while ($flight = $res->fetchArray()){
        $thisFlight = array(
            "flightId"     => $flight["flightId"],
            "noteFlightId" => $flight["noteFlightId"],
            "sequenceNum"  => $flight["sequenceNum"],
            "sheets"       => getSheetsForFlight($sheetResultObj, $flight["flightId"])
        );
        array_push($resultObj['data'], $thisFlight);
        mergeResultMessages($resultObj, $sheetResultObj);
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj['data'];
}

/**
 * @param $resultObj
 * @param $flightId
 * @return array
 */
function getSheetsForFlight(&$resultObj, $flightId) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from sheet where flightId = :flightId;";
    $res = doSQL($resultObj, $query, array("flightId" => $flightId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();
    $scoreResultObj = createEmptyResultObject();
    
    while ($sheet = $res->fetchArray()){
        getScoresForSheet($scoreResultObj, $sheet["sheetId"]);
        mergeResultMessages($resultObj, $scoreResultObj);
        $sanitisedScoreArray = array();
        foreach ($scoreResultObj["data"] as $score) {
            if (isset($score["mppFlag"])) {
                $sheet["mppFlag"] = $score["mppFlag"];
                // ToDo: Danger Will Robinson...   We really should persist this back to the DB.
                // It should be checked and set in postSheets!
                // When we see an MPP score there we should save it to the mppFlag and remove the figure....
            } else {
                array_push($sanitisedScoreArray, $score);
            }
        }
        // Now sanitised score array does not include the MPP 'score'.
        $pilotResultObj = createEmptyResultObject();
        $thisSheet = array(
            "sheetId"      => $sheet["sheetId"],
            "pilot"        => getPilot($pilotResultObj, $sheet["pilotId"]),
            "judgeNum"     => $sheet["judgeNum"],
            "judgeName"    => $sheet["judgeName"],
            "scribeName"   => $sheet["scribeName"],
            "comment"      => $sheet["comment"],
            "mppFlag"      => $sheet["mppFlag"],
            "flightZeroed" => $sheet["flightZeroed"],
            "zeroReason"   => $sheet["zeroReason"],
            "phase"        => $sheet["phase"],
            "scores"       => $sanitisedScoreArray
        );
        mergeResultMessages($resultObj, $pilotResultObj);


        // If pilot ID is null then what todo?   Lets make a note of it in the verboseMsgs messages...
        // For now, lets put the wrong pilotId into the comments section.
        if ($thisSheet["pilot"] == null) {
            $resultObj["result"]  = 'warn';
            array_push($resultObj["verboseMsgs"], ("WARN: Pilot " . $sheet["pilotId"] . " does not exist."));
            if ($thisSheet["comment"] == "" || $thisSheet["comment"] == null) {
                $thisSheet["comment"] = "ERROR: Pilot " . $sheet["pilotId"] . " does not exist.";
            } else {
                $thisSheet["comment"] = $thisSheet["comment"] . "\nERROR: Pilot " . $sheet["pilotId"] . " does not exist.";
            }
        } else {
            // Pilot is ok...
            if (isset($resultObj["debug"]) && $resultObj["debug"] === true) {
                error_log("DEBUG: Pilot " . $sheet["pilotId"] . " is OK.");
                array_push($resultObj["verboseMsgs"], ("INFO: Pilot " . $sheet["pilotId"] . " is OK."));
            }
        }

        array_push($resultObj["data"], $thisSheet);
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

/**
 * @param $resultObj
 * @param $sheetId
 * @return int|string
 */

function getMppFigNumForSheet(&$resultObj, $sheetId) {

    // This should not be needed anymore.   The data should already be right..

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query  = "select figureNum, shortDesc from figure where schedId = "
            . "( select schedId from round where roundId = "
            . "( select roundId from flight where flightId = "
            . "( select flightId from sheet where sheetId = :sheetId ) ) );";

    $mppFigNum = "";
    $res = doSQL($resultObj, $query, array("sheetId" => $sheetId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    $maxFigNum = 0;  

    while ($figure = $res->fetchArray()){
        if ($maxFigNum < $figure["figureNum"] ) {
            $maxFigNum = $figure["figureNum"];
        }
        if ( ($figure["shortDesc"] == "MPP Penalty") || ($figure["shortDesc"] == "Pilot & Panel?")) {
            $mppFigNum = $figure["shortDesc"];
        }
    }
    
    if ($mppFigNum === "")  // Definitely count it if it's called MPP.
        $mppFigNum = ( $maxFigNum + 1 ); // Only count it if it's 1 more than what we have defined in score.

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $mppFigNum;
}

function getMppFigNumForNotaumaticFlight(&$resultObj, $noteFlightId) {

    // Ok, now we need to know what the sequence looks like.
    // If a figure is numbered 1 more than what the sequence has defined, or
    // if the figure short description in the schedule is 'MPP' then this
    // is the slot for MPP and we do NOT want to record it in the JSON array.
    // Rather, we set the MPP flag if it is true...   This function
    // will get the figure number for us based on the notaumatic flight id.

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query  = "select figureNum, shortDesc from figure where schedId = "
        . "( select schedId from round where roundId = "
        . "( select roundId from flight where noteFlightId = :noteFlightId ) );";

    $mppFigNum = "";
    $res = doSQL($resultObj, $query, array("noteFlightId" => $noteFlightId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    $maxFigNum = 0;

    while ($figure = $res->fetchArray()){
        if ($maxFigNum < $figure["figureNum"] ) {
            $maxFigNum = $figure["figureNum"];
        }
        if ( ($figure["shortDesc"] == "MPP Penalty") || ($figure["shortDesc"] == "Pilot & Panel?")) {
            $mppFigNum = $figure["shortDesc"];
        }
    }

    if ($mppFigNum === "")  // Definitely count it if it's called MPP.
        $mppFigNum = ( $maxFigNum + 1 ); // Only count it if it's 1 more than what we have defined in score.

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $mppFigNum;
}

function getScoresForSheet(&$resultObj, $sheetId, $includeFigureDescription = false) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $mppResultObj = createEmptyResultObject();
    $mppFigureNum = getMppFigNumForSheet($mppResultObj, $sheetId);
    mergeResultMessages($resultObj, $mppResultObj);

    if ($includeFigureDescription) {
        $schedResultObj = createEmptyResultObject();
        $schedule = getScheduleForSheet($schedResultObj, $sheetId, true);
        $figures = $schedule["figures"];
        mergeResultMessages($resultObj, $schedResultObj);
    }

    $query = "select * from score where sheetId = :sheetId;";

    $res = doSQL($resultObj, $query, array("sheetId" => $sheetId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    while ($score = $res->fetchArray()){
        if ($score["figureNum"] == $mppFigureNum) {
            // Process this as an MPP.
            $thisScore = array(
                "figureNum"    => $score["figureNum"],
                "figureDesc"   => "Missing Pilot Penalty",
                "mppFlag"      => $score["score"]
            );
            array_push($resultObj["data"], $thisScore);
            error_log("Manually fixing mpp score.   This should no longer be necessary!");
            // Todo: This should not be needed anymore.    Only for databases with old data.   Remove it..
        } else {
            // Process this score as a normal sequence figure.
            $query = "select * from scoredelta where sheetId = :sheetId and figureNum = :figureNum;";

            $scoreres = doSQL($scoreResultObj, $query, array("sheetId" => $sheetId, "figureNum" => $score["figureNum"]));
            if ($scoreres === false) {
                $scoredelta = null;
            } else {
                $scoredelta = $scoreres->fetchArray();
                if ($scoredelta[0] == null) {
                    $scoredeltaobj = null;
                } else {
                    $scoredeltaobj["deleted"]   = $scoredelta["deleted"];
                    $scoredeltaobj["scoreTime"] = $scoredelta["scoreTime"];
                    $scoredeltaobj["breakFlag"] = $scoredelta["breakFlag"];
                    $scoredeltaobj["flags"]     = $scoredelta["flags"];
                    $scoredeltaobj["score"]     = $scoredelta["score"];
                    $scoredeltaobj["comment"]   = $scoredelta["comment"];
                    $scoredeltaobj["cdcomment"] = $scoredelta["cdcomment"];
                }
            }
            $thisScore = array(
                "figureNum"    => $score["figureNum"],
                "scoreTime"    => $score["scoreTime"],
                "breakFlag"    => $score["breakFlag"],
                "flags"        => $score["flags"],
                "score"        => $score["score"],
                "comment"      => $score["comment"],
                "scoredelta"   => $scoredeltaobj
            );
            if ($includeFigureDescription) {
                $thisScore["longDesc"] = "No description";
                foreach ($figures as $figure) {
                    if ( $figure["figureNum"] == $score["figureNum"] ) {
                        $thisScore["longDesc"] = $figure["longDesc"];
                    }
                }
            }
            array_push($resultObj["data"], $thisScore);
        }
    }
    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

function getPilots(&$resultObj) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from pilot;";
    $res = doSQL($resultObj, $query);
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

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
        array_push($resultObj["data"], $thisPilot);
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

/**
 * @param $resultObj
 * @param $creds
 *
 * Using JWT.   Checks supplied credentials against known users and then returns a token (or reason for unauth).
 *
 */
function authLogon(&$resultObj, $credentials) {

    global $jwtkey;

    $resultObj["result"]  = 'unauthorised';
    $resultObj["message"] = 'auth failure';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    if (!isset($credentials["username"]))
        $credentials["username"] = "";
    if (!isset($credentials["password"]))
        $credentials["password"] = "";


    $usersResultObj = createEmptyResultObject();
    $users = getUsers($usersResultObj);
    mergeResultMessages($resultObj, $usersResultObj);

    $token = null;

    foreach ($users as $user) {
        if ( ($user["username"] === $credentials["username"]) && ($user["password"] === $credentials["password"])) {
            // Create the token!
            require_once('jwt.php');

            /**
             * Uncomment the following line and add an appropriate date to enable the
             * "not before" feature.
             */
            // $nbf = strtotime('2021-01-01 00:00:01');

            /**
             * Uncomment the following line and add an appropriate date and time to enable the
             * "expire" feature.
             */
            //$exp = strtotime('2021-05-05 00:00:01');
            $exp = time()+60*60*24*30;

            // create a token
            $payloadArray = array();
            $payloadArray['username'] = $user['username'];
            $payloadArray['name'] = $user['fullName'];
            if (isset($nbf)) {$payloadArray['nbf'] = $nbf;}
            if (isset($exp)) {$payloadArray['exp'] = $exp;}
            $payloadArray['roles'] = explode(",", $user['roles']);
            $token = JWT::encode($payloadArray, $jwtkey);
            $resultObj["result"]  = 'success';
            $resultObj["message"] = 'auth success';
        }
    }
    // Now, set the cookie!   A failed login will log out anyone already logged in!
    setCookie("FlightlineAuthToken", $token, 0, "/", "", false, true);

    $resultObj["data"] = $token;
}

/**
 * @param $resultObj
 *
 * Using JWT.   Logs the user off and destroys the token.
 *
 */
function authLogoff(&$resultObj) {
    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'de-auth success';
    $resultObj["data"] = null;
    setCookie("FlightlineAuthToken", null, 0, "/", "", false, true);
}

/**
 * @param $resultObj
 *
 * Using JWT.   Checks the token is OK and has not been modified.   Makes sure the principal has access to the requested role.
 *
 */
function authHasRole(&$resultObj, $roles) {

    global $jwtkey;
    $blClearCookie = true;
    $blAuthorised = false;
    $token = (isset($_COOKIE['FlightlineAuthToken']) ? $_COOKIE['FlightlineAuthToken'] : null);
    $resultObj["result"]  = 'unauthorised';
    $resultObj["message"] = 'auth failure';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    switch (getType($roles)) {
        case "string":
            $rolesArray = explode(',', $roles);
            break;
        case "array":
            $rolesArray = $roles;
            break;
        default:
            $rolesArray = array();
    }

    if (!is_null($token)) {
        require_once('jwt.php');
        try {
            $payload = JWT::decode($token, $jwtkey, array('HS256'));
            $resultObj['data']['username'] = $payload->username;
            if (isset($payload->exp)) {
                $resultObj['data']['exp'] = $payload->exp;
                $resultObj['data']['expires'] = date(DateTime::ISO8601, $payload->exp);
            }
            if (isset($payload->name)) {
                $resultObj['data']['name'] = $payload->name;
            }
            if (isset($payload->roles)) {
                $resultObj['data']['roles'] = $payload->roles;
                foreach ($payload->roles as $tokenRole) {
                    foreach ($rolesArray as $role) {
                        if ($role === $tokenRole) {
                            $blAuthorised = true;
                        }
                    }
                }
            }
            if (isset($payload->exp) && time() <= $payload->exp) {
                $blClearCookie = false;
            }

        }
        catch(Exception $e) {
            $resultObj["verboseMsgs"][] = 'There was an error decoding the token: ' . $e->getMessage();
        }
    } else {
        $resultObj["verboseMsgs"][] = 'Invalid token: ' . $token;
    }

    // return to caller
    if ($blClearCookie)
        setCookie("FlightlineAuthToken", "", 0, "/", "", false, true);

    if ($blAuthorised) {
        $resultObj["result"] = "success";
        $resultObj["message"] = "user is authorised for role " . $role;
    }
    return $blAuthorised;
}

/**
 * @param $resultObj
 *
 * Using JWT.   Just return the payload.  Not the whole .
 *
 */
function authGetPayload(&$resultObj) {

    global $jwtkey;
    $blClearCookie = true;
    $blAuthorised = false;
    $token = (isset($_COOKIE['FlightlineAuthToken']) ? $_COOKIE['FlightlineAuthToken'] : null);
    $resultObj["result"]  = 'unauthorised';
    $resultObj["message"] = 'auth failure';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();


    if (!is_null($token)) {
        require_once('jwt.php');
        try {
            $payload = JWT::decode($token, $jwtkey, array('HS256'));
            $resultObj['data']['username'] = $payload->username;
            if (isset($payload->exp)) {
                $resultObj['data']['exp'] = $payload->exp;
                $resultObj['data']['expires'] = date(DateTime::ISO8601, $payload->exp);
            }
            if (isset($payload->name)) {
                $resultObj['data']['name'] = $payload->name;
            }
            if (isset($payload->roles)) {
                $resultObj['data']['roles'] = $payload->roles;
            }
            if (isset($payload->exp) && time() <= $payload->exp) {
                $blClearCookie = false;
            }
            $resultObj["result"] = "success";
            $resultObj["message"] = "User has a valid token.";

        }
        catch(Exception $e) {
            $resultObj["message"] = 'There was an error decoding the token: ' . $e->getMessage();
        }
    } else {
        $resultObj["message"] = 'Not logged in.';
    }

    // return to caller
    if ($blClearCookie)
        setCookie("FlightlineAuthToken", "", 0, "/", "", false, true);

    return $resultObj["data"];
}


/**
 * @param $resultObj
 * @return array
 */
function getUsers(&$resultObj) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from user;";
    $res = doSQL($resultObj, $query);
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    while ($user = $res->fetchArray()){
        $thisUser = array(
            "username"          => $user["username"],
            "fullName"        => $user["fullName"],
            "password"        => $user["password"],
            "address"         => $user["address"],
            "roles"           => $user["roles"]
        );
        array_push($resultObj["data"], $thisUser);
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

function getPilot(&$resultObj, $pilotId) {

    // Fix me!
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from pilot where pilotId = :pilotId;";
    $res = doSQL($resultObj, $query, array("pilotId" => $pilotId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();
    $pilot = $res->fetchArray();

    if ($pilot){
        $resultObj["data"] = array(
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
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'query success';
    } else {
        $resultObj["result"]  = 'warn';
        $resultObj["data"] = null;
        $resultObj["message"] = "Could not find pilot with ID:" . $pilotId;
    }

    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

function getMostRecentPilotAndRound(&$resultObj) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query  = "select max(sc.scoreTime) as latestScoreTime, sh.roundId, sh.pilotId "
            . "from score sc inner join sheet sh on sh.sheetId = sc.sheetId";

    $res = doSQL($resultObj, $query);
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    if ($row = $res->fetchArray()){
        $resultObj["data"]["latestScoreTime"] = $row["latestScoreTime"];
        $resultObj["data"]["roundId"] = $row["roundId"];
        $resultObj["data"]["pilotId"] = $row["pilotId"];
    } else {
        $resultObj["data"] = null;
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

function getScheduleForSheet(&$resultObj, $sheetId, $includeFigures = false) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from schedule where schedId = (select schedId from round where roundId = ( select roundId from sheet where sheetId = :sheetId));";
    $res = doSQL($resultObj, $query, array("sheetId" => $sheetId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();
    if ($row = $res->fetchArray()){
        $resultObj["data"] = array(
            "schedId"       => $row["schedId"],
            "imacClass"     => $row["imacClass"],
            "imacType"      => $row["imacType"],
            "description"   => $row["description"]
        );
        if ($includeFigures) {
            $tmpResultObj = createEmptyResultObject();
            $figures = getFiguresForSchedule($tmpResultObj, $row["schedId"]);
            mergeResultMessages($resultObj, $tmpResultObj);
            $resultObj["data"]["figures"] = $figures;
        }
    } else {
        $resultObj["data"] = null;
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

function getSchedule(&$resultObj, $schedId, $includeFigures = false) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from schedule where schedId = :schedId;";
    $res = doSQL($resultObj, $query, array("schedId" => $schedId));
    if ($res === false)
        goto db_rollback;

    if ($includeFigures) {
        $tmpResultObj = createEmptyResultObject();
        $figures = getFiguresForSchedule($tmpResultObj, $schedId);
        mergeResultMessages($resultObj, $tmpResultObj);
    }

    $resultObj["data"] = array();
    if ($row = $res->fetchArray()){
        $resultObj["data"] = array(
            "schedId"       => $row["schedId"],
            "imacClass"     => $row["imacClass"],
            "imacType"      => $row["imacType"],
            "description"   => $row["description"]
        );
        if ($includeFigures)
            $resultObj["data"]["figures"] = $figures;
    } else {
        $resultObj["data"] = null;
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

function getFiguresForSchedule(&$resultObj, $schedId) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from figure where schedId = :schedId;";
    $res = doSQL($resultObj, $query, array("schedId" => $schedId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();
    while ($row = $res->fetchArray()){
        $fig_data = array(
            "figureNum"       => $row["figureNum"],
            "schedId"         => $row["schedId"],
            "longDesc"        => $row["longDesc"],
            "spokenText"      => $row["spokenText"],
            "rule"            => $row["rule"],
            "k"               => $row["k"]
        );
        array_push($resultObj["data"], $fig_data);
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

/**
 * @param $resultObj
 * @return array
 */
function getFlownRounds(&$resultObj) {
    // Get the flown rounds as one big JSON object.
    // Keep as much of the non Score! like data out of it....

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from round where phase = 'D';";

    $res = doSQL($resultObj, $query);
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();
    $flightResultObj = createEmptyResultObject();
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
            "flights"       => getFlightsForRound($flightResultObj, $round["roundId"])
        );
        array_push($resultObj["data"], $thisRound);
        $flightResultObj = null;
    }
    mergeResultMessages($resultObj, $flightResultObj);

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

/**
 * @param $resultObj
 * @param $roundId
 * @return array
 */
function getFlownRound(&$resultObj, $roundId) {
    // Get the flown rounds as one big JSON object.
    // Keep as much of the non Score! like data out of it....

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from round where phase = 'D' and roundId = :roundId;";

    $res = doSQL($resultObj, $query, array("roundId" => $roundId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();
    $flightResultObj = createEmptyResultObject();
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
            "flights"       => getFlightsForRound($flightResultObj, $round["roundId"])
        );
        array_push($resultObj["data"], $thisRound);
        $flightResultObj = null;
    }
    mergeResultMessages($resultObj, $flightResultObj);

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}

/**
 * @param $resultObj
 * @return array
 */
function getFlightLineData(&$resultObj) {

    // Get everything we have.    Send it back to the requestor as JSON.
    // Keep as much of the non Score! like data out of it....

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $tmpResultObj = createEmptyResultObject();
    $resultObj["data"] = array(
        "flightLineId" => getStateValue($tmpResultObj,"flightLineId"),
        "flightLineAPIVersion" => getFlightLineAPIVersion(),
        "flightLineName" => getStateValue($tmpResultObj,"flightLineName"),
        "flightLineUrl" => getStateValue($tmpResultObj,"flightLineUrl"),
        "flightLineAvailableAPIs" => getFlightLineAPIsAvailable(),
        "users" => array(),
        "pilots" => array(),
        "rounds" => array()
    );
    mergeResultMessages($resultObj, $tmpResultObj);

    $userResultObj = createEmptyResultObject();
    $resultObj["data"]["users"] = getUsers($userResultObj);
    // Remove the passwords...
    foreach ($resultObj["data"]["users"] as &$user) {
        unset ($user["password"]);
    }
    mergeResultMessages($resultObj, $userResultObj);

    $pilotResultObj = createEmptyResultObject();
    $resultObj["data"]["pilots"] = getPilots($pilotResultObj);
    mergeResultMessages($resultObj, $pilotResultObj);

    $roundResultObj = createEmptyResultObject();
    $resultObj["data"]["rounds"] = getFlownRounds($roundResultObj);
    mergeResultMessages($resultObj, $roundResultObj);

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    return $resultObj["data"];
}

/**
 * @param $resultObj
 * @param $roundId
 * @return array
 */
/* This function is broken!
 *
 * It tries to use noteFlightId when it really needs to check flightId + seqnum
function getRoundResults(&$resultObj, $roundId) {
    global $db;
    // Get the full data for a round.
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from round where roundId = :roundId;";

    $res = doSQL($resultObj, $query, array("roundId" => $roundId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    $round = $res->fetchArray(SQLITE3_ASSOC);

    if (!$round) {
        error_log("WARN: Round " . $roundId . " does not exist.");
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
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'query success';
        $resultObj["data"] = $round;
    }

    $resultObj["result"]  = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    return $resultObj["data"];
}
*/
/**
 * @param $resultObj
 * @param null $roundId
 * @param null $imacType
 */
function getRoundPilotFlights(&$resultObj, $roundId = null, $imacType = null) {

    // Get the full list of pilots for the round.
    // If it's freestyle, use a special query..
    // It's messy but efficient to do it this way.
    // ToDo: Look at a better way.

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    if ($imacType === null) {
        // No idea what the round type is, grab it and see..
        $roundResultObj = createEmptyResultObject();
        getRound($roundResultObj, array("roundId" => $roundId));
        mergeResultMessages($resultObj, $roundResultObj);
        $imacType = $roundResultObj["data"]["imacType"];
    }

    if ($imacType === "Freestyle") {
        $query = "select r.*, p.pilotId, p.fullName, p.airplane, f.noteFlightId, f.sequenceNum "
            . "from round r "
            . "left join pilot p on p.freestyle = 1 "
            . "left join flight f on f.roundId = r.roundId "
            . "where p.active = 1 and r.roundId = :roundId;";
    } else {
        // Note: each round has 1 flight per sequence.
        $query = "select r.*, p.pilotId, p.fullName, p.airplane, f.noteFlightId, f.sequenceNum "
            . "from round r "
            . "left join pilot p on p.imacClass = r.imacClass "
            . "left join flight f on f.roundId = r.roundId "
            . "where p.active = 1 and r.roundId = :roundId;";
    }

    $res = doSQL($resultObj, $query, array("roundId" => $roundId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    while ($row = $res->fetchArray()) {
        $sequenceNum = $row['sequenceNum'];
        $btnId = $row['roundId'] . "_" . $row['pilotId'] . "_" . $row['noteFlightId'] . "_" . convertClassToCompID($row["imacClass"]);
        $functions  = '<div class="function_buttons"><ul>';
        $functions .= '<li class="function_set_next_flight_button"><a id="' . $btnId . '" data-pilotname="' . $row['fullName'] . '" data-roundid="' . $row['roundId'] . '" data-seqnum="' . $sequenceNum . '" data-pilotid="'   . $row['pilotId'] . '" data-noteflightid="'   . $row['noteFlightId'] . '">Sequence ' . $sequenceNum . '</a></li>';
        $functions .= '</ul></div>';
        $noteHint = "Pilot:" . $row['pilotId'] . " Flight:" . $row['noteFlightId'] . " Comp:" . convertClassToCompID($row["imacClass"]) . " Schedule:" . $row["schedId"];
        $resultObj["data"][] = array(
            "pilotId"       => $row['pilotId'],
            "fullName"      => $row['fullName'],
            "noteFlightId"  => $row['noteFlightId'],
            "functions"     => $functions,
            "noteHint"      => $noteHint,
            "compId"        => convertClassToCompID($row["imacClass"])
        );
    }

    $resultObj["result"] = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    // So far have not needed it.
    //return $resultObj["data"];
}

/**
 * @param $resultObj
 * @param $roundId
 */
function getRoundFlightStatus(&$resultObj, $roundId) {

    // Check out what flights we have for this round...   And their 'phase'.
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select r.imacClass, s.roundId, s.pilotId, f.noteFlightId, s.judgeNum, s.phase "
            ."from round r inner join sheet s on r.roundId = s.roundId "
            ."left join flight f on s.flightId = f.flightId where r.roundId = :roundId;";
    
    /****** 
     *      The following data comes back for round 5 sportsman where we have 1 pilot (2),
     *      2 sequences (8 and 9) and 2 judges (1, 2).   All sheets are done (D).
     * 
     * Sportsman|5|2|8|2|D
     * Sportsman|5|2|8|1|D
     * Sportsman|5|2|9|2|D
     * Sportsman|5|2|9|1|D
     *******/

    $res = doSQL($resultObj, $query, array("roundId" => $roundId));
    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    while ($sheet = $res->fetchArray()) {
        $btnId = $sheet['roundId'] . "_" . $sheet['pilotId'] . "_" . $sheet['noteFlightId'] . "_" . convertClassToCompID($sheet["imacClass"]);

        $resultObj["data"][] = array(
            "buttonID"      => $btnId,
            "judgeNum"      => $sheet['judgeNum'],
            "phase"         => $sheet['phase']
        );
    }

    $resultObj["result"] = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

/**
 * @param $resultObj
 */
function getNextRoundIds(&$resultObj) {

    // Get rounds
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select imacClass, imacType, (max(roundNum) + 1) as nextroundNum from round group by imacClass, imacType;";

    $res = doSQL($resultObj, $query);
    if ($res === false)
        goto db_rollback;

    while ($row = $res->fetchArray()){
        $resultObj["data"][] = array(
            "imacClass"          => $row['imacClass'],
            "imacType"           => $row['imacType'],
            "nextroundNum"       => $row['nextroundNum'],
        );
    }

    $resultObj["result"] = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

function setNextFlight(&$resultObj) {
    // setNextFlight..
    // If we have the round, pilot and flight number, we can set the next flight...

    global $logger;
    $logger->info("In: setNextFlight");

    $nextrndrequest = null;
    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    // Start transaction.
    $transResult = createEmptyResultObject();
    if (!beginTrans($transResult)) {
        mergeResultMessages($resultObj, $transResult);
        goto db_rollback;
    }

    //$logger->debug("Got: " . (($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}"));
    // Get request payload.
    if (is_null($nextrndrequest)) {
        $nextrndrequest = @json_decode((($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}"), true);
    }

    // Get the round data and check it's open..
    $roundResult = createEmptyResultObject();
    getRound($roundResult, array(
        "roundId" => $nextrndrequest["roundId"],
        "imacClass" => null,
        "imacType" => null,
        "roundNum" => null
    ));

    if ($roundResult["result"] === 'success') {
        $roundData = $roundResult["data"];
    } else {
        mergeResultMessages($resultObj, $roundResult);
        goto db_rollback;
    }

    if ($roundData["phase"] !== "O") {
        // Round is not open...   Lets bail.
        $resultObj["result"]  = 'error';
        $resultObj["message"] = 'This round is not open.';
        $resultObj["data"] = array();
        $resultObj["verboseMsgs"] = ["Round is in phase " . $roundData["phase"]];
        goto db_rollback;
    }


    $pilotResult = createEmptyResultObject();
    getPilotsForRound($pilotResult, array(
        "roundId" => $nextrndrequest["roundId"]
    ));

    if ($pilotResult["result"] === 'success') {
        $pilotData = $pilotResult["data"];
    } else {
        mergeResultMessages($resultObj, $pilotResult);
        goto db_rollback;
    }


    if (is_null($pilotData)) {
        // Error getting pilots for round.   Lets bail.
        $resultObj["result"]  = 'error';
        $resultObj["message"] = $pilotResult["message"];
        $resultObj["data"] = array();
        goto db_rollback;
    }

    $foundPilot = null;
    foreach ( $pilotData as $pilot) {
        $logger->debug("Looking for pilot " . $nextrndrequest["pilotId"] . ", found pilot: ", $pilot);
        if ($pilot["pilotId"] != $nextrndrequest["pilotId"])
            continue;
        else
            $foundPilot = $pilot;
    }

    if (is_null($foundPilot)) {
        // Round is not the pilot's class.   Lets bail.
        $resultObj["result"]  = 'error';
        $resultObj["message"] = "Pilot is not in this round.";
        $resultObj["data"] = array();
        goto db_rollback;
    }

    $query = "DELETE FROM nextFlight; ";
    $nextFlightDeleteResult = createEmptyResultObject();
    $res = doSQL($nextFlightDeleteResult, $query);
    if ($res === false) {
        mergeResultMessages($resultObj, $nextFlightDeleteResult);
        goto db_rollback;
    }

    $compId = convertClassToCompID($roundData["imacClass"]);
    $query = "INSERT INTO nextFlight(nextNoteFlightId, nextCompId, nextPilotId) "
        . "VALUES(:nextNoteFlightId, :nextCompId, :nextPilotId); ";

    $pArr = array(
        "nextNoteFlightId" => $nextrndrequest["noteFlightId"],
        "nextCompId" => $compId,
        "nextPilotId" => $nextrndrequest["pilotId"]
    );

    $nextFlightResult = createEmptyResultObject();

    $res = doSQL($nextFlightResult, $query, $pArr);
    if ($res === false) {
        mergeResultMessages($resultObj, $nextFlightResult);
        goto db_rollback;
    }

    $res->finalize();

    if (commitTrans($adjustmentResult,"Could not set next flight.") ) {
        mergeResultMessages($resultObj, $adjustmentResult);
        $resultObj["result"]  = 'success';
        $resultObj["message"] = "Next flight is set.";
        $logger->info("Next flight is set.", $nextrndrequest);
    }

    db_rollback:
    if ($resultObj["result"] !== "success"){
        rollbackTrans($resultObj);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
        $logger->warning("Could not set the nextflight.", $resultObj);
    }

    return null;
}

function getNextFlight(&$resultObj, $roundId) {

    // Get the next flight data (seq, pilotname etc)
    // Note: each round has 1 flight per sequence.

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $imacClass = null;
    $compId = null;

    //if (isset($_GET['roundId']))   { $roundId     = $_GET['roundId'];  }  else $roundId = null;
    
    $query = "select * from nextFlight nf inner join pilot p on nf.nextPilotId = p.pilotId where p.active = 1";
    // Make sure nf.compId = round.imacClass (convert)
    // Make sure pilot is active and in freestyle (if need be) or in imacClass.
    // Return pilot data, round data...

    $res = doSQL($resultObj, $query, array("roundId" => $roundId));
    if ($res === false)
        goto db_rollback;

    $pilot = $res->fetchArray();
    if (!$pilot) {
        $resultObj["result"] = 'error';
        $resultObj["message"] = 'There is no valid next flight scheduled.';
        goto db_rollback;
    }
    $nextNoteFlightId = $pilot["nextNoteFlightId"];
    $nextFlightClass = convertCompIDToClass($pilot["nextCompId"]);
    $pilotInFreestyle = $pilot["freestyle"];


    $flightResultObj = createEmptyResultObject();

    $query = "select * from flight f inner join round r on f.roundId = r.roundId where r.roundId = :roundId and f.noteFlightId = :nextNoteFlightId;";
    $res = doSQL($resultObj, $query, array(
        "roundId" => $roundId,
        "nextNoteFlightId" => $nextNoteFlightId
    ));
    if ($res === false)
        goto db_rollback;

    $flight = $res->fetchArray();
    if (!$flight) {
        $resultObj["result"] = 'error';
        $resultObj["message"] = 'There is no valid next flight scheduled.';
        goto db_rollback;
    }
    mergeResultMessages($resultObj, $flightResultObj);


    if ($nextFlightClass == $flight["imacClass"] || ($nextFlightClass == "Freestyle" && $pilotInFreestyle == 1)) {
        // All good!
    } else {
        $resultObj["result"] = 'error';
        $resultObj["message"] = 'This pilot is not in the correct class for the next flight.';
        goto db_rollback;
    }

    // I think we have all we need!   Lets send it back.

    $resultObj["data"]["nextNoteFlightId"]      = $pilot['nextNoteFlightId'];
    $resultObj["data"]["nextPilotId"]           = $pilot['nextPilotId'];
    $resultObj["data"]["nextCompId"]            = $pilot['nextCompId'];
    $resultObj["data"]["nextPilotName"]         = $pilot['fullName'];
    $resultObj["data"]["nextSchedId"]           = $flight['schedId'];
    $resultObj["data"]["nextSequenceNum"]       = $flight['sequenceNum'];
    $resultObj["data"]["nextRoundId"]           = $flight['roundId'];

    $resultObj["result"] = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
    // So far have not needed it.
    //return $resultObj["data"];
}

function getSchedlist(&$resultObj) {

    // Get schedules
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from schedule order by imacClass;";

    $res = doSQL($resultObj, $query);
    if ($res === false)
        goto db_rollback;

    while ($round = $res->fetchArray()) {
        $resultObj["data"][] = array(
            "schedId"     => $round['schedId'],
            "imacClass"   => $round['imacClass'],
            "imacType"    => $round['imacType'],
            "description" => $round['description'],
        );
    }

    $resultObj["result"] = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

function getStateValue(&$resultObj, $key) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select value from state where key = :key;";
    $res = doSQL($resultObj, $query, array("key" => $key));
    if ($res === false)
        return null;

    $state = $res->fetchArray();
    if (!$state) {
        // Null.   
        return null;
    } else {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'query success';
        $resultObj["data"][$key] = $state["value"];
        return $state["value"];
    }
}

function getFlightSheets(&$resultObj, $flightId) {
    return getSheets($resultObj, null, $flightId);
}

function getRoundSheets(&$resultObj, $roundId) {
    return getSheets($resultObj, $roundId, null);
}

function getSheets(&$resultObj, $roundId = null, $flightId = null) {

    if (isset($_GET['scores'])) { $getScores = true; }  else $getScores = false;

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    if ($flightId !== null && $flightId !== '') {
        $query = "select * from sheet where flightId = :flightId;";
        $res = doSQL($resultObj, $query, array(
            "flightId" => $flightId
        ));
    } else if ($roundId !== null && $roundId !== '') {
        $query = "select * from sheet where roundId = :roundId;";
        $res = doSQL($resultObj, $query, array(
            "roundId" => $roundId
        ));
    } else {
        $query = "select * from sheet order by sheetId;";
        $res = doSQL($resultObj, $query);
    }

    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    while ($sheet = $res->fetchArray()) {
        if ($getScores) {
            $scoresResultObj = createEmptyResultObject();
            $scores = getScoresForSheet($scoresResultObj, $sheet['sheetId'], true);
            mergeResultMessages($resultObj, $scoresResultObj);
        } else {
            $scores = null;
        }

        $sheetArr = array(
            "sheetId"       => $sheet['sheetId'],
            "roundId"       => $sheet['roundId'],
            "flightId"      => $sheet['flightId'],
            "pilotId"       => $sheet['pilotId'],
            "judgeNum"      => $sheet['judgeNum'],
            "judgeName"     => $sheet['judgeName']  ,
            "scribeName"    => $sheet['scribeName'],
            "comment"       => $sheet['comment'],
            "mppFlag"       => $sheet['mppFlag'],
            "flightZeroed"  => $sheet['flightZeroed'],
            "zeroReason"    => $sheet['zeroReason'],
            "phase"         => $sheet['phase'],
            "scores"        => $scores
        );


        if (!$getScores) {
            unset ($sheetArr["scores"]);
        }

        $resultObj["data"][] = $sheetArr;
    }

    $resultObj["result"] = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

function getSheet(&$resultObj, $sheetId) {

    if (isset($_GET['scores'])) { $getScores = true; }  else $getScores = false;

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $query = "select * from sheet where sheetId = :sheetId;";
    $res = doSQL($resultObj, $query, array(
        "sheetId" => $sheetId
    ));


    if ($res === false)
        goto db_rollback;

    $resultObj["data"] = array();

    while ($sheet = $res->fetchArray()) {
        if ($getScores) {
            $scoresResultObj = createEmptyResultObject();
            $scores = getScoresForSheet($scoresResultObj, $sheet['sheetId'], true);
            mergeResultMessages($resultObj, $scoresResultObj);
        } else {
            $scores = null;
        }
        $resultObj["data"] = array(
            "sheetId"       => $sheet['sheetId'],
            "roundId"       => $sheet['roundId'],
            "flightId"      => $sheet['flightId'],
            "pilotId"       => $sheet['pilotId'],
            "judgeNum"      => $sheet['judgeNum'],
            "judgeName"     => $sheet['judgeName']  ,
            "scribeName"    => $sheet['scribeName'],
            "comment"       => $sheet['comment'],
            "mppFlag"       => $sheet['mppFlag'],
            "flightZeroed"  => $sheet['flightZeroed'],
            "zeroReason"    => $sheet['zeroReason'],
            "phase"         => $sheet['phase'],
            "scores"        => $scores
        );
    }

    if (!$getScores) {
        unset ($resultObj["data"]["scores"]);
    }

    $resultObj["result"] = 'success';
    $resultObj["message"] = 'query success';
    $res->finalize();
    db_rollback:
}

function getFlightLineAPIVersion() {
    return "1";
}

function getFlightLineAPIsAvailable() {
    $directory = dirname(__DIR__, 1);
    $scanned_directory = array_diff(scandir($directory), array('..', '.'));
    $retval = array();
    foreach ($scanned_directory as $item) {
        if (is_dir($directory . DIRECTORY_SEPARATOR . $item) && file_exists($directory . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR . "api_functions.php"))
            array_push($retval, $item);
    }
    return $retval;
}

function getFlightLineDetails(&$resultObj) {
    $detailsResultObj = createEmptyResultObject();
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();


    $resultObj["data"]['flightLineId'] = getStateValue($detailsResultObj, "flightLineId");
    $resultObj["data"]['flightLineName'] = getStateValue($detailsResultObj, "flightLineName");
    $resultObj["data"]['flightLineUrl'] = getStateValue($detailsResultObj, "flightLineUrl");
    $resultObj["data"]['flightLineAPIVersion'] = getFlightLineAPIVersion();
    $resultObj["data"]['flightLineAvailableAPIs'] = getFlightLineAPIsAvailable();
    if ($resultObj["data"]['flightLineUrl'] === null) {
        unset ($resultObj["data"]['flightLineUrl']);
    }
    mergeResultMessages($resultObj, $detailsResultObj);
    return $resultObj["data"];
}

function resetCompetition (&$resultObj) {
    // Delete all results, rounds, pilots and sequences, only user accounts remain.
    global $logger;
    $logger->info("In: resetFlightLine");

    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    unset($resultObj["data"]);
    $resultObj["verboseMsgs"] = array();

    $resultsResult = createEmptyResultObject();
    clearResults($resultsResult);
    mergeResultMessages($resultObj, $resultsResult);

    if ($resultsResult["result"] !== "success") {
        goto db_rollback;
    }

    $transResult = createEmptyResultObject();
    if (!beginTrans($transResult))
        goto db_rollback;

    mergeResultMessages($resultObj, $transResult);

    $deleteRounds = createEmptyResultObject();
    $query = "delete from round;";
    $res = doSQL($deleteRounds, $query);
    mergeResultMessages($resultObj, $deleteRounds);
    $res->finalize();

    $deletePilots = createEmptyResultObject();
    $query = "delete from pilot;";
    $res = doSQL($deletePilots, $query);
    mergeResultMessages($resultObj, $deletePilots);
    $res->finalize();

    $deleteFigures = createEmptyResultObject();
    $query = "delete from figure;";
    $res = doSQL($deleteFigures, $query);
    mergeResultMessages($resultObj, $deleteFigures);

    $deleteSchedules = createEmptyResultObject();
    $query = "delete from schedule;";
    $res = doSQL($deleteSchedules, $query);
    mergeResultMessages($resultObj, $deleteSchedules);
    $res->finalize();

    $resultObj["verboseMsgs"][] = "Competition is reset.";

    if (commitTrans($transResult,"Could not reset the competition data. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'Competition is reset.';
    }

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($transResult);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }
    return null;

}

function addRound(&$resultObj, $newRound = null) {
    global $db, $logger;
    // Add round
    
    // Insert into three tables...   First one is the round table.
    // Second is the flight table (1 flight row per sequence).
    // Third one is the flightOrder table, to set up the round order.
    // 
    // First, lets get a the flight ID...
    //
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    if (is_null($newRound)) {
        $newRound = @json_decode((($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}"), true);
    }

    $transResult = createEmptyResultObject();

    // Sanity checks.
    if ($newRound["imacType"] == "Freestyle") $newRound["imacClass"] = "Freestyle";
    if ($newRound["imacType"] != "Known" ) $newRound["sequences"] = 1;
    if (!isset($newRound["phase"])) $newRound["phase"] = 'U';

    $flightLineId = getStateValue($transResult, "flightLineId");
    if ($flightLineId < 1) {
        $flightLineId = null;
    }
    if (!beginTrans($transResult))
        goto db_rollback;

    $query =  "INSERT into round (flightLine, imacClass, imacType, roundNum, schedId, sequences, phase) "
           .  "VALUES (:flightLine, :imacClass, :imacType, :roundNum, :schedId, :sequences, :phase );";

    // We should check on our values...
    $res = doSQL($resultObj, $query, array(
        "flightLine" => $flightLineId,
        "imacClass" => $newRound["imacClass"],
        "imacType" => $newRound["imacType"],
        "roundNum" => $newRound["roundNum"],
        "schedId" => $newRound["schedule"],
        "sequences" => $newRound["sequences"],
        "phase" => $newRound["phase"]
    ));
    if ($res === false)
        goto db_rollback;

    $newRoundId = $db->lastInsertRowID();

    // Get the next flight id.
    $query = "select (max(noteFlightId) + 1) as newNoteFlightId "
           . "from flight f inner join round r on f.roundId = r.roundId "
           . "where r.imacClass = :imacClass";

    $res->finalize();
    $nextFlightResult = createEmptyResultObject();
    $res = doSQL($nextFlightResult, $query, array("imacClass" => $newRound["imacClass"]));
    mergeResultMessages($resultObj, $nextFlightResult);

    if ($res === false)
        goto db_rollback;

    $row = $res->fetchArray();
    if (!$row || $row["newNoteFlightId"] == null) {
        // Null?
        if (($newRound["imacClass"] == "Freestyle") || ($newRound["imacType"] == "Freestyle"))  {
            $newNoteFlightId = 1;   // At one stage we were starting freestyle flights at 91..  Not sure why,..
        } else {
            $newNoteFlightId = 1;
        }
    } else {
        $newNoteFlightId = $row["newNoteFlightId"];
    }
    $res->finalize();

    $usedNoteFlightIds = array();
    for ($i = 1; $i <= $newRound["sequences"]; $i++) {
        $query = "INSERT INTO flight(noteFlightId, roundId, sequenceNum) "
               . "VALUES(:noteFlightId, :roundId, :sequenceNum); ";

        $newFlightResult = createEmptyResultObject();
        $res = doSQL($newFlightResult, $query, array(
            "noteFlightId" => $newNoteFlightId,
            "roundId" => $newRoundId,
            "sequenceNum" => $i
        ));
        mergeResultMessages($resultObj, $newFlightResult);

        if ($res === false)
            goto db_rollback;

        $usedNoteFlightIds[] = $newNoteFlightId;
        $newNoteFlightId++;
    }

    // Now do flightOrder.
    // One per sequence, per pilot.

    $pilotResult = createEmptyResultObject();
    getPilotsForRound($pilotResult, array(
        "roundId" => $newRoundId
    ));

    if ($pilotResult["result"] === 'success') {
        $pilotData = $pilotResult["data"];
    } else {
        mergeResultMessages($resultObj, $pilotResult);
        goto db_rollback;
    }


    $foundPilot = null;
    shuffle($pilotData);
    $i = 0;
    foreach ( $pilotData as $pilot) {
        foreach ($usedNoteFlightIds as $flightId) {
            $i++;
            $query = "INSERT INTO flightOrder(roundId, pilotId, noteFlightId, flightOrder) "
                . "VALUES(:roundId, :pilotId, :noteFlightId, :flightOrder); ";

            $newFlightOrderResult = createEmptyResultObject();
            $res = doSQL($newFlightOrderResult, $query, array(
                "roundId" => $newRoundId,
                "pilotId" => $pilot["pilotId"],
                "noteFlightId" => $flightId,
                "flightOrder" => $i
            ));
            mergeResultMessages($resultObj, $newFlightOrderResult);

            if ($res === false || $newFlightOrderResult["result"] == "error")
                goto db_rollback;
        }
    }

    if (commitTrans($transResult,"There was a problem adding the round. ") ) {
        $resultObj["result"] = 'success';
        $resultObj["message"] = 'Inserted new round (' . $newRoundId . ') into class ' . $newRound["imacClass"] . '.';
    }

    $res->finalize();
    db_rollback:
    if ($resultObj["result"] == "error"){
        $db->exec("ROLLBACK;");
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }
}

function editRound() {
    global $db;
    global $result;
    global $message;
    $transResult = createEmptyResultObject();

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

    if (!beginTrans($transResult))
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

    $query  = "delete from flight "
            . "where roundId = :roundId;";

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

    if (commitTrans($transResult,"There was a problem editing the round. ") ) {
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

function deleteRound(&$resultObj, $paramArray = null) {
    global $logger;
    $logger->info("In: deleteRound");

    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    // Delete round.
    $imacClass = isset($paramArray["imacClass"]) ? $paramArray["imacClass"] : null;
    $imacType = isset($paramArray["imacType"]) ? $paramArray["imacType"] : null;
    $roundNum = isset($paramArray["roundNum"]) ? $paramArray["roundNum"] : null;
    $roundId = isset($paramArray["roundId"]) ? $paramArray["roundId"] : null;

    $transResult = createEmptyResultObject();

    if (!beginTrans($transResult)) {
        mergeResultMessages($resultObj, $transResult);
        goto db_rollback;
    }

    // Get the round data and check it's ok to delete..
    $roundResult = createEmptyResultObject();
    if ($roundId === null) {
        getRound($roundResult, array(
            "roundId" => null,
            "imacClass" => $imacClass,
            "imacType" => $imacType,
            "roundNum" => $roundNum
        ));
        $logger->debug("Roundsearch:", $roundResult);
    } else {
        getRound($roundResult, array(
            "roundId" => $roundId,
            "imacClass" => null,
            "imacType" => null,
            "roundNum" => null
        ));
    }
    mergeResultMessages($resultObj, $roundResult);

    if ($roundResult["result"] === 'success') {
        $roundData = $roundResult["data"];
        if ( empty($roundData) ) {
            // Round does not exist...
            $resultObj["message"] = 'There is no such round.';
            goto db_rollback;
        } else
            $roundId = $roundData["roundId"];
    } else {
        goto db_rollback;
    }

    $pArr = array(
        "roundId" => $roundId
    );

    // Now to clear all of the results for this round.....
    // Get the flights...
    // For each flight get the sheets.
    // For each sheet delete the scores and scoredeltas.

    $flightsResult = createEmptyResultObject();
    $flights = getFlightsForRound($flightsResult, $roundId);
    mergeResultMessages($resultObj, $flightsResult);

    if (is_null($flights)) {
        // Error - it came back null.
        goto db_rollback;
    }

    foreach ($flights as $flight) {
        $sheetResult = createEmptyResultObject();
        $flightSheets = $flight["sheets"];
        mergeResultMessages($resultObj, $sheetResult);

        if (is_array($flightSheets)) {
            foreach ($flightSheets as $sheet) {

                $sheetId = $sheet["sheetId"];
                $delScoreDeltaResultObj = createEmptyResultObject();
                $query = "delete from scoredelta where sheetId = :sheetId;";
                $res = doSQL($delScoreDeltaResultObj, $query, array("sheetId" => $sheet["sheetId"]));
                mergeResultMessages($resultObj, $delScoreDeltaResultObj);

                if ($res === false)
                    goto db_rollback;
                $res->finalize();
                $resultObj["verboseMsgs"][] = "Deleted score adjustments for sheet $sheetId.";

                $delScoreResultObj = createEmptyResultObject();
                $query = "delete from score where sheetId = :sheetId;";
                $res = doSQL($delScoreResultObj, $query, array("sheetId" => $sheetId));

                mergeResultMessages($resultObj, $delScoreResultObj);

                if ($res === false)
                    goto db_rollback;
                $res->finalize();
                $resultObj["verboseMsgs"][] = "Deleted scores for sheet $sheetId..";

                $delSheetResultObj = createEmptyResultObject();
                $query = "delete from sheet where sheetId = :sheetId;";
                $res = doSQL($delSheetResultObj, $query, array("sheetId" => $sheetId));
                mergeResultMessages($resultObj, $delSheetResultObj);

                if ($res === false)
                    goto db_rollback;
                $res->finalize();
                $resultObj["verboseMsgs"][] = "Deleted sheet $sheetId..";
            }
        }

        $query = "delete from flight where flightId = :flightId;";
        $res = doSQL($delSheetResultObj, $query, array("flightId" => $flight["flightId"]));
        mergeResultMessages($resultObj, $delSheetResultObj);

        if ($res === false)
            goto db_rollback;
        $res->finalize();
        $resultObj["verboseMsgs"][] = "Deleted flight " . $flight["flightId"] . ".";
    }

    $query = "delete from flightOrder where roundId = :roundId;";

    $delFlightOrderResult = createEmptyResultObject();

    $logger->debug("Query: $query", $pArr);
    $res = doSQL($delFlightOrderResult, $query, $pArr);
    mergeResultMessages($resultObj, $delFlightOrderResult);

    if ($res === false) {
        $resultObj["message"] = "Could not delete round $roundId from the flightOrder table.";
        goto db_rollback;
    }

    $query = "delete from round where roundId = :roundId;";

    $delRoundResult = createEmptyResultObject();

    $logger->debug("Query: $query", $pArr);
    $res = doSQL($delRoundResult, $query, $pArr);
    mergeResultMessages($resultObj, $delRoundResult);

    if ($res === false) {
        $resultObj["message"] = "Could not delete round $roundId from the round table.";
        goto db_rollback;
    }

    $res->finalize();

    if (commitTrans($adjustmentResult,"Could not delete round" . isset($roundId) ? " $roundId." : ".") ) {
        mergeResultMessages($resultObj, $adjustmentResult);
        $resultObj["result"]  = 'success';
        $resultObj["message"] = isset($roundId) ? 'Round has ' . $roundId . ' been deleted.' : 'Round has been deleted.';
        $logger->info('Round has been deleted.', $paramArray);
    }

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($resultObj);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
        $logger->warning("Could not delete round" . isset($roundId) ? " $roundId." : ".", $resultObj);
    }
    return null;

}

function deleteAllRounds (&$resultObj) {
    // Delete all rounds, including the results.
    global $logger;
    $logger->info("In: deleteRound");

    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $resultsResult = createEmptyResultObject();
    clearResults($resultsResult);
    mergeResultMessages($resultObj, $resultsResult);

    $transResult = createEmptyResultObject();
    if (!beginTrans($transResult))
        goto db_rollback;

    mergeResultMessages($resultObj, $transResult);

    $deleteRounds = createEmptyResultObject();
    $query = "delete from round;";
    $res = doSQL($deleteRounds, $query);
    mergeResultMessages($resultObj, $deleteRounds);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Deleted rounds.";

    if (commitTrans($transResult,"Could not delete rounds. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'All rounds deleted.';
    }

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($transResult);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }
    return null;

}

function clearResults(&$resultObj) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = null;
    $resultObj["verboseMsgs"] = array();

    $transResult = createEmptyResultObject();
    if (!beginTrans($transResult))
        goto db_rollback;

    mergeResultMessages($resultObj, $transResult);

    $delScoreDeltaResultObj = createEmptyResultObject();
    $query = "delete from scoredelta;";
    $res = doSQL($delScoreDeltaResultObj, $query);
    mergeResultMessages($resultObj, $delScoreDeltaResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Deleted score adjustments.";

    $delScoreResultObj = createEmptyResultObject();
    $query = "delete from score;";
    $res = doSQL($delScoreResultObj, $query);
    mergeResultMessages($resultObj, $delScoreResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Deleted scores.";


    $delSheetResultObj = createEmptyResultObject();
    $query = "delete from sheet;";
    $res = doSQL($delSheetResultObj, $query);
    mergeResultMessages($resultObj, $delSheetResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Deleted judge sheets.";


    $delSheetResultObj = createEmptyResultObject();
    $query = "delete from sqlite_sequence where name = 'sheet'";
    $res = doSQL($delSheetResultObj, $query);
    mergeResultMessages($resultObj, $delSheetResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Reset sheet index.";


    $delFlightResultObj = createEmptyResultObject();
    $query = "delete from flight;";
    $res = doSQL($delFlightResultObj, $query);
    mergeResultMessages($resultObj, $delFlightResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Deleted flight records.";

    $delFlightOrderResultObj = createEmptyResultObject();
    $query = "delete from flightorder;";
    $res = doSQL($delFlightOrderResultObj, $query);
    mergeResultMessages($resultObj, $delFlightOrderResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Deleted flight ordering.";

    $delFlightResultObj = createEmptyResultObject();
    $query = "delete from sqlite_sequence where name = 'flight'";
    $res = doSQL($delFlightResultObj, $query);
    mergeResultMessages($resultObj, $delFlightResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Reset flight index.";


    $delNextFlightResultObj = createEmptyResultObject();
    $query = "delete from nextFlight;";
    $res = doSQL($delNextFlightResultObj, $query);
    mergeResultMessages($resultObj, $delNextFlightResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Nextflight reset.";


    $resetRoundResultObj = createEmptyResultObject();
    $query = "update round set phase = 'U';";
    $res = doSQL($resetRoundResultObj, $query);
    mergeResultMessages($resultObj, $resetRoundResultObj);

    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Rounds reset to phase unflown.";

    if (commitTrans($transResult,"Could not clear result data. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'The result data has been cleared.';
    }

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($transResult);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }
    return null;

}

function clearResultsForRound(&$resultObj, $paramArray = null, $atomic = true)
{
    // We're not using this right now!!!
    // It would be useful if we could have nested transactions but until I refactor to allow that, it's a no go...

    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = null;
    $resultObj["verboseMsgs"] = array();

    // paramArray is a round object.   So clear all tables...

    if ($atomic) {
        $transResult = createEmptyResultObject();
        if (!beginTrans($transResult))
            goto db_rollback;

        mergeResultMessages($resultObj, $transResult);
    }

    $roundId = isset($paramArray["roundId"]) ? $paramArray["roundId"] : null;
    // Get the flights...
    // For each flight get the sheets.
    // For each sheet delete the scores and scoredeltas.

    $flightsResult = createEmptyResultObject();
    $flights = getFlightsForRound($flightsResult, $roundId);
    mergeResultMessages($resultObj, $flightsResult);

    if (is_null($flights)) {
        // Error - it came back null.
        goto db_rollback;
    }

    foreach ($flights as $flight) {
        $sheetResult = createEmptyResultObject();
        $flightSheets = $flight["sheets"];
        mergeResultMessages($resultObj, $sheetResult);

        if (is_array($flightSheets)) {
            foreach ($flightSheets as $sheet) {

                $sheetId = $sheet["sheetId"];
                $delScoreDeltaResultObj = createEmptyResultObject();
                $query = "delete from scoredelta where sheetId = :sheetId;";
                $res = doSQL($delScoreDeltaResultObj, $query, array("sheetId" => $sheet["sheetId"]));
                mergeResultMessages($resultObj, $delScoreDeltaResultObj);

                if ($res === false)
                    goto db_rollback;
                $res->finalize();
                $resultObj["verboseMsgs"][] = "Deleted score adjustments for sheet $sheetId.";

                $delScoreResultObj = createEmptyResultObject();
                $query = "delete from score where sheetId = :sheetId;";
                $res = doSQL($delScoreResultObj, $query, array("sheetId" => $sheetId));

                mergeResultMessages($resultObj, $delScoreResultObj);

                if ($res === false)
                    goto db_rollback;
                $res->finalize();
                $resultObj["verboseMsgs"][] = "Deleted scores for sheet $sheetId..";

                $delSheetResultObj = createEmptyResultObject();
                $query = "delete from sheet where sheetId = :sheetId;";
                $res = doSQL($delSheetResultObj, $query, array("sheetId" => $sheetId));
                mergeResultMessages($resultObj, $delSheetResultObj);

                if ($res === false)
                    goto db_rollback;
                $res->finalize();
                $resultObj["verboseMsgs"][] = "Deleted sheet $sheetId..";
            }
        }

        $query = "delete from flight where flightId = :flightId;";
        $res = doSQL($delSheetResultObj, $query, array("flightId" => $flight["flightId"]));
        mergeResultMessages($resultObj, $delSheetResultObj);

        if ($res === false)
            goto db_rollback;
        $res->finalize();
        $resultObj["verboseMsgs"][] = "Deleted flight " . $flight["flightId"] . ".";
    }


    if (commitTrans($transResult,"Could not clear result for round $roundId. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = "The results for round $roundId are cleared.";
    }
    $res->finalize();

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($transResult);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }
    return null;
}

function clearPilots(&$resultObj) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $transResult = createEmptyResultObject();

    if (!beginTrans($transResult))
        goto db_rollback;

    mergeResultMessages($resultObj, $transResult);

    $delNFResult = createEmptyResultObject();

    $query = "delete from nextFlight;";
    $res = doSQL($delNFResult, $query);
    mergeResultMessages($resultObj, $delNFResult);
    if ($res === false)
        goto db_rollback;
    $res->finalize();

    $sheetResult = createEmptyResultObject();
    $query = "select count(*) as sheetCount from sheet;";
    $res = doSQL($sheetResult, $query);
    mergeResultMessages($resultObj, $sheetResult);
    if ($res === false)
        goto db_rollback;

    if ($sheet = $res->fetchArray()) {
        if ($sheet["sheetCount"] > 0) {
            $resultObj["result"]  = 'error';
            $resultObj["message"] = 'Cannot clear pilots while scores have been entered.';
            goto db_rollback;
        }
    }
    $res->finalize();

    $delPilotResult = createEmptyResultObject();
    $query = "delete from pilot ;";
    $res = doSQL($delPilotResult, $query);
    mergeResultMessages($resultObj, $delPilotResult);
    if ($res === false)
        goto db_rollback;

    if (commitTrans($transResult,"Could not clear pilots. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'The pilots have been cleared.';
    }

    $res->finalize();

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($transResult);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }       
    return null;
}

function clearSchedules(&$resultObj) {
    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $transResult = createEmptyResultObject();

    if (!beginTrans($transResult))
        goto db_rollback;
    if (isset($_GET['scheduleType'])){ $schedType = $_GET['scheduleType'];} else $schedType = null;

    $delSchedResult = createEmptyResultObject();
    $query = "select schedId from schedule where imacType = :schedType;";
    $res = doSQL($delSchedResult, $query, array(
        "schedType"           => $schedType
    ));
    mergeResultMessages($resultObj, $delSchedResult);

    if ($res === false) {
        $resultObj["verboseMsgs"][] = "Could not get details for " . $schedType . " schedules.";
        goto db_rollback;
    }

    
    while ($sched = $res->fetchArray()) {
        /*****
         * Iterate through schedules.   If a round exists for it, then abort (for that schedule).
         * Delete the rest and tell the user which schedules were aborted...
         * 
         * Or: If a round exists that is *not unflown* then abort for that schedule.
         *  Unflown rounds can have there schedules deleted...   We just need to update the status to reflect
         *  this and disallow the opening of such a round...
         */
        $delSchedResult = createEmptyResultObject();
        $query = "delete from figure where schedId = :schedId;";
        $res = doSQL($delSchedResult, $query, array(
            "schedId"           => $sched["schedId"]
        ));
        mergeResultMessages($resultObj, $delSchedResult);

        if ($res === false) {
            $resultObj["verboseMsgs"][] = "Could not clear figures for schedule: " . $sched["schedId"];
            goto db_rollback;
        }

    }

    $delSchedResult = createEmptyResultObject();
    $query = "delete from schedule where schedId = :schedId;";
    $res = doSQL($delSchedResult, $query, array(
        "schedId"           => $sched["schedId"]
    ));
    mergeResultMessages($resultObj, $delSchedResult);

    if ($res === false) {
        $resultObj["verboseMsgs"][] = "Could not clear schedule: " . $sched["schedId"];
        goto db_rollback;
    }

    if (commitTrans($transResult,"Could not clear schedule data. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'Schedules have been cleared.';
    }
    
    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($resultObj, "Could not clear schedule data. ");
        if ($resultObj["result"] == null) { $resultObj["message"] = 'query error'; }
    }       
    return $resultObj["data"];
}

function postPilots(&$resultObj, $pilotsArray = null) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $transResult = createEmptyResultObject();

    if (is_null($pilotsArray))
        $pilotsArray = @json_decode(($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");

    if (!beginTrans($transResult))
        goto db_rollback;

    mergeResultMessages($resultObj, $transResult);

    $resultObj["result"] = "success";
    $resultObj["verboseMsgs"] = array();


    if (is_null($pilotsArray)) {
        error_log("Could not decode JSON: " . json_last_error_msg());
        $resultObj["result"]  = 'error';
        $resultObj["message"] = "Could not decode JSON: " . json_last_error_msg();
        goto db_rollback;
    }

    error_log("Ready to upload pilots..." . ($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    foreach($pilotsArray as $pilotId => $pilot) {
        error_log("Inserting Pilot:$pilotId " . print_r($pilot, true));

        $insPilotResult = createEmptyResultObject();

        $query = "INSERT into pilot (pilotId, primaryId, secondaryId, fullName, airplane, freestyle, imacClass, in_customclass1, in_customclass2, active) "
                ."VALUES(:pilotId, :primaryId, :secondaryId, :fullName, :airplane, :freestyle, :imacClass, :in_customclass1, :in_customclass2, :active)";

        $res = doSQL($insPilotResult, $query, array(
            "pilotId"           => $pilot->pilotId,
            "primaryId"         => $pilot->primaryId,
            "secondaryId"       => $pilot->secondaryId,
            "fullName"          => $pilot->fullName,
            "airplane"          => $pilot->airplane,
            "freestyle"         => $pilot->freestyle,
            "imacClass"         => $pilot->imacClass,
            "in_customclass1"   => $pilot->in_customclass1,
            "in_customclass2"   => $pilot->in_customclass2,
            "active"            => $pilot->active
        ));
        mergeResultMessages($resultObj, $insPilotResult);
        if ($res === false) {
            $resultObj["verboseMsgs"][] = "Could not insert Pilot Id: " . $pilot->pilotId . " Name: " . $pilot->fullName;
            goto db_rollback;
        } else {
            $resultObj["verboseMsgs"][] = "Uploaded Pilot: " . $pilot->pilotId . " Name: " . $pilot->fullName;
            error_log("Inserted Pilot: " . $pilot->fullName);
        }

    }
    if (commitTrans($transResult, "Could not add the pilots. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'Pilots have been added.';
    }
    
    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($resultObj);
        if ( $resultObj["message"] == null) {  $resultObj["message"] = 'query error'; }
    }       
    return null;
}

function deleteSequences (&$resultObj) {
    // Delete all sequences, including the results.
    global $logger;
    $logger->info("In: deleteSequences");

    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    unset($resultObj["data"]);
    $resultObj["verboseMsgs"] = array();

    // Don't delete if rounds exist...
    $roundResult = createEmptyResultObject();
    $rounds = getRounds($roundResult);
    if (is_array($rounds) && sizeof($rounds) > 0) {
        // We have data...
        $resultObj["message"] = 'Cannot delete sequences when rounds exist.';
        return null;
    }

    $transResult = createEmptyResultObject();
    if (!beginTrans($transResult))
        goto db_rollback;
    mergeResultMessages($resultObj, $transResult);

    $deleteFigures = createEmptyResultObject();
    $query = "delete from figure;";
    $res = doSQL($deleteFigures, $query);
    mergeResultMessages($resultObj, $deleteFigures);

    $deleteSchedules = createEmptyResultObject();
    $query = "delete from schedule;";
    $res = doSQL($deleteSchedules, $query);
    mergeResultMessages($resultObj, $deleteSchedules);


    if ($res === false)
        goto db_rollback;
    $res->finalize();
    $resultObj["verboseMsgs"][] = "Deleted sequences.";

    if (commitTrans($transResult,"Could not delete sequences. ") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'All sequences deleted.';
    }

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($transResult);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }
    return null;

}

function postSequences(&$resultObj, $sequenceArray = null) {

    $resultObj["result"]  = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $transResult = createEmptyResultObject();

    $message = null;
    $sqlite_data = null;
    if (is_null($sequenceArray)) {
        $sequenceArray = @json_decode(($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    }

    if (!beginTrans($transResult))
        goto db_rollback;

    $resultObj["result"] = "success";
    $resultObj["verboseMsgs"] = array();

    if (is_null($sequenceArray)) {
        error_log("Could not decode JSON: " . json_last_error_msg());
        $resultObj["result"]  = 'error';
        $resultObj["message"] = "Could not decode JSON: " . json_last_error_msg();
        goto db_rollback;
    }

    error_log("Ready to upload sequences...");

    //error_log("Ready to upload pilots..." . ($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    foreach($sequenceArray as $sequence) {

        // If there are rounds defined using this sequence, we must abort the delete, but can still replace later,...

        $query = "SELECT count(*) as roundcount FROM round where schedId = :schedId";
        $countRoundsResult = createEmptyResultObject();
        $res = doSQL($countRoundsResult, $query, array(
            "schedId"           => $sequence->schedId
        ));
        mergeResultMessages($resultObj, $countRoundsResult);
        if ($res === false || (!$round = $res->fetchArray())) {
            $resultObj["verboseMsgs"][] = "Could not get round count for sequence: " . $sequence->schedId;
            goto db_rollback;
        } else {
            $resultObj["verboseMsgs"][] = "Sequence: " . $sequence->schedId . " has " . $round["roundcount"] . " rounds defined.";
            if ($round["roundcount"] == 0) {

                error_log("Deleting sequence: " . $sequence->schedId);

                $query = "DELETE FROM figure WHERE schedId = :schedId; ";
                $deleteSeqResult = createEmptyResultObject();
                $res = doSQL($deleteSeqResult, $query, array(
                    "schedId"           => $sequence->schedId
                ));
                mergeResultMessages($resultObj, $deleteSeqResult);

                if ($res === false) {
                    $resultObj["verboseMsgs"][] = "Could not delete figures for sequence: " . $sequence->schedId;
                    goto db_rollback;
                }

                $query = "DELETE FROM schedule WHERE schedId = :schedId; ";
                $deleteFigResult = createEmptyResultObject();
                $res = doSQL($deleteFigResult, $query, array(
                    "schedId"           => $sequence->schedId
                ));
                mergeResultMessages($resultObj, $deleteFigResult);

                if ($res === false) {
                    $resultObj["verboseMsgs"][] = "Could not delete sequence: " . $sequence->schedId;
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

        $insSeqResult = createEmptyResultObject();
        $res = doSQL($insSeqResult, $query, array(
            "schedId"           => $sequence->schedId,
            "imacClass"         => $sequence->imacClass,
            "imacType"          => $sequence->imacType,
            "description"       => $sequence->description
        ));
        mergeResultMessages($resultObj, $insSeqResult);

        if ($res === false) {
            $resultObj["verboseMsgs"][] = "Could not insert sequence: " . $sequence->schedId;
            goto db_rollback;
        } else {
            $resultObj["verboseMsgs"][] = "Uploaded sequence: " . $sequence->schedId;
            error_log("Inserted Sequence: " . $sequence->schedId);
        }

        foreach($sequence->figures as $figure) {
            $query = "INSERT or REPLACE into figure (figureNum, schedId, shortDesc, longDesc, spokenText, rule, k) "
                ."VALUES(:figureNum, :schedId, :shortDesc, :longDesc, :spokenText, :rule, :k)";

            $insFigsResult = createEmptyResultObject();
            $res = doSQL($insFigsResult, $query, array(
                "figureNum"     => $figure->figureNum,
                "schedId"       => $sequence->schedId,
                "shortDesc"     => $figure->shortDesc,
                "longDesc"      => $figure->longDesc,
                "spokenText"    => $figure->spokenText,
                "rule"          => $figure->rule,
                "k"             => $figure->k
            ));
            mergeResultMessages($resultObj, $insFigsResult);

            if ($res === false) {
                $resultObj["verboseMsgs"][] = "Could not insert figure: " . $figure->figureNum . " for sequence: " . $sequence->schedId;
                goto db_rollback;
            } else {
                error_log("Inserted figure: " . $figure->figureNum . " for sequence: " . $sequence->schedId);
            }
        }

    }
    if (commitTrans($transResult, "Could not add the sequences.") ) {
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'Sequences have been added.';
    }

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($resultObj);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
    }
    return $sqlite_data;
}

function getFlightScores(&$resultObj, $flightId, $pilotId) {
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
                "phase"        => $sheet['phase'],
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

function postSheets($sheetJSON = null) {

    // This function only used in import-notaumatic...

    global $db;
    global $result;
    global $message;
    global $sqlite_data;
    global $verboseMsgs;
    $transResult = createEmptyResultObject();

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
    if (!beginTrans($transResult))
        goto db_rollback;

    $verboseMsgs = array();
    foreach($sheetArray as $sheet) {
        //error_log("Processing sheet: " . print_r($sheet, true));
        // First Off, check the phase.   If it is 'D' for done, then adjust MPP...
        if ($sheet->phase == 'D') {
            $figNumResult = createEmptyResultObject();
            $mppFigNum = getMPPFignumForNotaumaticFlight($figNumResult, $sheet->noteFlightId);

            foreach ($sheet->scores as $idx => $score) {
                if ($score->figureNum == $mppFigNum) {
                    //error_log("Idx: " . $idx . " is mpp val: " . $score->score);
                    $sheet->mppFlag = $score->score;
                    unset($sheet->scores[$idx]);
                }
            }
        }

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
                $query =    "update sheet set roundId = :roundId, flightId = :flightId, pilotId = :pilotId, judgeNum = :judgeNum, phase = :phase, mppFlag = :mppFlag "
                            . "where sheetId = :sheetId;";
            } else {
                $sheetId = null;
                $query =    "insert into sheet (roundId, flightId, pilotId, judgeNum, phase, mppFlag) "
                            . "values (:roundId, :flightId, :pilotId, :judgeNum, :phase, :mppFlag);";
            }
            //error_log ($query . " " . $round["roundId"] . " " . $round["flightId"] . " " . $sheet->pilotId . " ". $sheet->judgeNum);
        }
        
        
        
        if ($statement = $db->prepare($query)) {
            try {
                $statement->bindValue(':pilotId',  $sheet->pilotId);
                $statement->bindValue(':roundId',  $round["roundId"]);
                $statement->bindValue(':flightId', $round["flightId"]);
                $statement->bindValue(':judgeNum', $sheet->judgeNum);
                $statement->bindValue(':phase',    $sheet->phase);
                $statement->bindValue(':mppFlag',  $sheet->mppFlag);
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

    if (commitTrans($transResult, "Could not add the sheet.") ) {
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

function deleteScoreAdjustment(&$resultObj, $paramArray = null) {

    // deleteScoreAdjustment..
    // We must have sheetId and figureNum
    // Delete it, even if it does not exist...

    global $logger;

    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $sheetId = isset($paramArray["sheetId"]) ? $paramArray["sheetId"] : null;
    $figureNum = isset($paramArray["figureNum"]) ? $paramArray["figureNum"] : null;

    $query = "delete from scoredelta where sheetId = :sheetId and figureNum = :figureNum";

    $pArr = array(
        "sheetId" => $sheetId,
        "figureNum" => $figureNum
    );

    $transResult = createEmptyResultObject();

    if (!beginTrans($transResult)) {
        mergeResultMessages($resultObj, $transResult);
        goto db_rollback;
    }

    $delAdjustmentResult = createEmptyResultObject();

    $logger->debug("Query: $query", $pArr);
    $res = doSQL($delAdjustmentResult, $query, $pArr);
    if ($res === false) {
        mergeResultMessages($resultObj, $delAdjustmentResult);
        goto db_rollback;
    }

    $res->finalize();

    if (commitTrans($delAdjustmentResult,"Could not delete adjustment for figure $figureNum of sheet $sheetId.") ) {
        mergeResultMessages($resultObj, $delAdjustmentResult);
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'The adjustment was deleted.';
        $logger->info("Deleted adjustment for figure $figureNum of sheet $sheetId.");
    }

    db_rollback:
    if ($resultObj["result"] == "error"){
        rollbackTrans($resultObj);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
        $logger->warning("Could not delete adjustment for figure $figureNum of sheet $sheetId.", $resultObj);
    }
    return null;
}

function postScoreAdjustment(&$resultObj, $paramArray = null) {

    // postScoreAdjustment..
    // We must have sheetId and figureNum
    // Adjust it, even if it does not exist...

    global $logger;
    $logger->info("In: postScoreAdjustment");

    $adjustment = null;
    $resultObj["result"] = 'error';
    $resultObj["message"] = 'query error';
    $resultObj["data"] = array();
    $resultObj["verboseMsgs"] = array();

    $sheetId = isset($paramArray["sheetId"]) ? $paramArray["sheetId"] : null;
    $figureNum = isset($paramArray["figureNum"]) ? $paramArray["figureNum"] : null;

    if (is_null($adjustment)) {
        $adjustment = @json_decode((($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}"), true);
    }
    // Copy form data accross.
    $button = isset($adjustment["button"]) ? $adjustment["button"] : "save";
    if ($button === "delete") {
        $adjustment["deleted"] = 1;
    } else {
        $adjustment["deleted"] = 0;
    }
    $adjustment["sheetId"] = isset($adjustment["sheetId"]) ? $adjustment["sheetId"] : null;
    $adjustment["figureNum"] = isset($adjustment["figureNum"]) ? $adjustment["figureNum"] : null;
    $adjustment["scoreTime"] = isset($adjustment["scoreTime"]) ? $adjustment["scoreTime"] : 1;
    $adjustment["breakFlag"] = isset($adjustment["breakFlag"]) ? $adjustment["breakFlag"] : null;
    $adjustment["flags"] = isset($adjustment["flags"]) ? $adjustment["flags"] : null;
    $adjustment["score"] = isset($adjustment["score"]) ? $adjustment["score"] : null;
    $adjustment["comment"] = isset($adjustment["comment"]) ? $adjustment["comment"] : null;
    $adjustment["cdcomment"] = isset($adjustment["cdcomment"]) ? $adjustment["cdcomment"] : null;

    $logger->info("Data: ", $adjustment);

    $query = "INSERT or REPLACE INTO scoredelta (sheetId, deleted, figureNum, scoreTime, breakFlag, flags, score, comment, cdcomment) "
           . "VALUES (:sheetId, :deleted, :figureNum, :scoreTime, :breakFlag, :flags, :score, :comment, :cdcomment)";

    $pArr = array(
        "sheetId" => $adjustment["sheetId"],
        "figureNum" => $adjustment["figureNum"],
        "deleted" => $adjustment["deleted"],
        "scoreTime" => $adjustment["scoreTime"],
        "breakFlag" => $adjustment["breakFlag"],
        "flags" => $adjustment["flags"],
        "score" => $adjustment["score"],
        "comment" => $adjustment["comment"],
        "cdcomment" => $adjustment["cdcomment"]
    );

    $transResult = createEmptyResultObject();

    if (!beginTrans($transResult)) {
        mergeResultMessages($resultObj, $transResult);
        goto db_rollback;
    }

    $adjustmentResult = createEmptyResultObject();

    $logger->debug("Query: $query", $pArr);
    $res = doSQL($adjustmentResult, $query, $pArr);
    if ($res === false) {
        mergeResultMessages($resultObj, $adjustmentResult);
        goto db_rollback;
    }

    $res->finalize();

    if (commitTrans($adjustmentResult,"Could not adjust score for figure $figureNum of sheet $sheetId.") ) {
        mergeResultMessages($resultObj, $adjustmentResult);
        $resultObj["result"]  = 'success';
        $resultObj["message"] = 'The score was adjusted.';
        $logger->info("Adjusted score for figure $figureNum of sheet $sheetId.");
    }

    db_rollback:
    if ($resultObj["result"] == "error") {
        rollbackTrans($resultObj);
        if ($resultObj["message"] == null) { $resultObj["message"] = 'query error'; }
        $logger->warning("Could not adjust score for figure $figureNum of sheet $sheetId.", $resultObj);
    }
    return null;
}