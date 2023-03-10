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

//********************************************************************************
// Author: Dan Carroll
// date:    03/2018
//
//********************************************************************************
// This file is based on the Notauscore file of the same name.
// It is meant to impliment notaumatic interaction with the flightline DB.
//********************************************************************************
// 

include_once ("include/functions.php");
include_once ("api/1/api_functions.php");
// URL
//server/import-notaumatic.php?OPT=U&F=1&J=8&D=58&N1=0&N2=0&N3=0&N4=0&N5=0&N6=0&N7=0&N8=0&N9=0&N10=0&N11=0
//http://192.168.100.200/import-notaumatic.php/?OPT=U&F=1&J=8&D=58&N1=1&N2=1&N3=1&N4=1&N5=0&N6=0&N7=0&N8=0&N9=0&N10=0&N11=1&N12=1&N13=1&N14=1&N15=1&N16=1&N17=1&N18=1
//http://192.168.1.220/import-notaumatic.php?OPT=U&F=1&J=2&D=1&N1=1&N2=2&N3=3&N4=4&N5=5&N6=6&N7=7&N8=8&N9=9&N10=10&N11=11
//http://localhost/html/import-notaumatic.php?OPT=P&C=1&F=1


// OPT = Option (U for update - i.e. save entire flight, T for Test - to check if the pilot/round/comp/seq is ok, 
//              H for time??, N for note - the current and previous score, P for next pilot data.)
// C            = Comp number i.e which class - enum(Basic, Sortsman, Intermediate etc,....
// F		= Flight number (each sequence is a flight)
// J		= Judge number
// L		= Line number (new option...  not sure how it works yet)
// D		= Pilot Number
// Nx		= Scores.
/*
Error code
900 --> Could not get flight.
901 --> Could not get pilot.
902 --> Could not get figure.
903 --> Could not update score.
904 --> Could not update log.

100 --> Erreur No flight or notaumatic flag defined
101 --> Erreur Judge not allowed for this flight or do not exist
102 --> Erreur No pilot found with this id
103 --> Erreur Nombre de note envoy?? non coh??rent
104 --> Erreur Mauvais code option
105 --> Could not identified a unique competition match
-1  --> Could not find the next pilot
0   --> Fin Ok
*/
$logRequests = true;
$timezone = 'UTC';
ini_set('date.timezone', $timezone);
$dbfile = "db/flightline.db";
date_default_timezone_set($timezone);
global $logger;


if (isset($_GET['OPT'])) $nautoption      = $_GET['OPT'];  else $nautoption = "";
if (isset($_GET['F']))   $nautoflightid   = $_GET['F'];    else $nautoflightid = "";
if (isset($_GET['C']))   $nautocompid     = $_GET['C'];    else $nautocompid = "";
if (isset($_GET['J']))   $nautojugeid     = $_GET['J'];    else $nautojugeid = "";
if (isset($_GET['D']))   $nautopilotid    = $_GET['D'];    else $nautopilotid = "";
if (isset($_GET['P']))   $nautoSchedId    = $_GET['P'];    else $nautoSchedId = "";

// Connect to database - We need it so bail early if it is not there.
try {
    $db = new SQLite3($dbfile);
} catch (Exception $e) {
    $logger->error("Could not connect to DB - " . $e->getMessage());
    echo "return:900&H:".date('YmdHis', time());
    exit;
}

// Get the scores if they exist.
$i = 0;
$nautScores = array();
foreach($_GET as $reqOpt => $reqVal)	{
    if ($reqOpt[0] === "N") {
	$figureNumber = explode("N", $reqOpt);
	$nautScores[$i]['figpos'] = $figureNumber[1];
        $nautScores[$i]['breakFlag'] = false;
        switch ($reqVal) {
            case "-1":
                $nautScores[$i]['score'] = null;
                break;
            case "-2":
                // It's a break.
                $nautScores[$i]['score'] = 0;
                $nautScores[$i]['breakFlag'] = true;
                break;
            default:
                $nautScores[$i]['score'] = $reqVal;
                break;
        }
	$i++;
    }
}

// Log the request:
if ($logRequests == true ) {
    // forward the result to redundancy IP address
    // we are checking REDUNDANCY parameter in conf/conf.ffam.php to save sql queries here
    // 
    // set URL and other appropriate options
    // Not used for IMAC!
    $url = "http://imac.lan/import-notaumatic.php"
	    . "?OPT=".$nautoption
	    . "&F=".$nautoflightid
	    . "&D=".$nautopilotid
	    . "&J=".$nautojugeid
	    . "&C=".$nautocompid;
    foreach($nautScores as $score)	{
	$url .= "&N".$score['figpos']."=".$score['score'];
    }

    $fp = fopen('log/request.log', 'a');
    fwrite($fp, '['.date("c").'] ' . $url . "\n");
    fclose($fp);
    $logger->info("Request: " . $url);
    //error_log("Received: " . $url);
}

switch ($nautoption) {
    case "H":
        // What's this operation?
        echo "return:0&H:".date('YmdHis', time());
        break;

    /** @noinspection PhpMissingBreakStatementInspection */
    case "N":
        // Update a single flight score.
        // Fall through to 'U' since it really is the same code only the array is small.
        $logger->info("Single score update");
    case "U":
         /************
         * Update the flight scores and save them.	
         * Get each score from the array and insert it.
         * Create a JSON object and pass it to postSheet..
         * 
         *  {
         *      "pilotId": 6,
         *      "judgeNum": 2,
         *      "compId": 1,
         *      "flightId": 2,
         *      "scores": [
         *          {
         *              "figureNum": 1,
         *              "scoreTime": 1234567,
         *              "breakFlag": 0,
         *              "score": 5,
         *              "comment": null
         *          }.....
         *      ]
         *  }
         */
        if ($nautoption == 'U') $logger->info("Full score sheet update");

        $sheets = array();
        $sheet = array(
            "pilotId"       => $nautopilotid,
            "judgeNum"      => $nautojugeid,
            "compId"        => $nautocompid,
            "noteFlightId"  => $nautoflightid,
            "phase"         => "S",   // Started...
            "mppFlag"       => "0",
            "scores"        => array()
        );
        foreach ($nautScores as $score) {
            $s = array();
            $s["figureNum"] = $score['figpos'];
            $s["scoreTime"] = time();
            $s["breakFlag"] = $score['breakFlag'];
            $s["score"]     = $score['score'];
            $s["comment"]   = null;

            array_push($sheet['scores'], $s);
        }
        if ($nautoption === "U") {
            // This is saving the sheet...  Mark it as done!
            $sheet['phase'] = "D";
        }
        array_push($sheets, $sheet);
        if (postSheets(json_encode($sheets, JSON_PRETTY_PRINT)))
            echo "return:0&H:".date('YmdHis', time());
        else
            echo "return:902";
            
        break;
    case "T":  
        // Test...   Check the flight exists, and is open.   This gets done before a pilot is scored.
        // 1. Check if we have a schedule open for a specific class..
        //    The key is...   CompId (This is flightline), NoteFlightId, SequenceId
        //    i.e.  1,1,BASIC-K1
        //          1,1,SPORT-K1
        //          1,1,INTER-K2
        //          1,2,BASIC-U1
        //          1,2,SPORT-U1
        //          1,2,INTER-U1
        //          1,1,FREESTYL
        //
        //    As you can see, class names are in the sequence.   THe last part denotes the type of sched.
        //    K1 = Known Sched 1, K2 = Known Sched 2 (e.g. alternate), K3 could be a 3rd known...
        //    Actually only the first 3 characters denote theclass, and then everything after the '-' is 
        //    the Schedule for that class.
        $logger->info("Test mode: check is flight is open.");
        $bl_abort = false;
        if (!is_numeric($nautoflightid)) $bl_abort = true;
        if (!is_numeric($nautocompid))   $bl_abort = true;
        if ($nautoSchedId == "")         $bl_abort = true;

        if ($bl_abort) {
            echo "return:100&H:".date('YmdHis', time());
        } else {
            // Notauscore does not care about the pilot except for updates.   Should ignore it for now (0 = no pilot...).
            $flightStatusReturn = getFlightStatus($nautoflightid, $nautocompid, $nautoSchedId, $nautopilotid);

            // The returned string can be...   OK:STATUS:Message, ERROR:STATUS:Message, ERROR:STATUS:Message
            // Thefirst part is the result of the request.
            // OK - It's OK and this flight can be scored.
            // ERROR - A Critical error occurred i.e. can't read/write to db etc.
            // 
            // The next part is the flight's current state (phase).
            // U = Unflown
            // O = Open
            // P = Paused
            // C = Completed
            // 
            //$status = checkFlightIsOpen($nautoflightid, $nautocompid, $nautoSchedId, $nautopilotid);
            error_log("Got: " . $flightStatusReturn);
            list($flightStatusResult, $flightStatus, $flightStatusMsg) = explode(":", $flightStatusReturn, 3);
            switch ($flightStatusResult) {
                case "OK":
                    switch ($flightStatus) {
                        case "O":
                            // We have a flight...   Lets go!
                            $logger->info("The round is open and ready for scoring.");
                            //updateFlightStatus("O", $nautoflightid, $nautocompid, $nautoSchedId, $nautopilotid);
                            echo "return:0"."&H:".date('YmdHis', time());
                            break;
                        case "U": // Unflown
                            $logger->info("The round is not yet started.");
                            echo "return:100&H:".date('YmdHis', time());
                            break;
                        case "P": // Paused
                            $logger->info("The round is paused.");
                            echo "return:100&H:".date('YmdHis', time());
                            break;
                        case "C": // Completed
                            $logger->info("The round is already flown.");
                            echo "return:100&H:".date('YmdHis', time());
                            break;
                        default:
                            // Normal error (like the flight is not open...
                            $logger->warning("Unknown flight status.");
                            echo "return:100&H:".date('YmdHis', time());
                            break;
                    }
                    break;

                case "ERROR":
                    // Bad error occurred (structural).   Display error bells and whistles.
                    error_log("ERROR: " . $flightStatusMsg);
                    $logger->error("Something went wrong: " . $flightStatusMsg);
                    echo "return:900&H:".date('YmdHis', time());
                    break;


                default:
                    // Unhandled return!
                    error_log("ERROR: " . $flightStatusReturn);
                    $logger->error("Something went wrong: " . $flightStatusReturn . " " . $flightStatusMsg);
                    echo "return:100&H:".date('YmdHis', time());
                    break;
            }
        }
        break;

    case "P":  
        // The Notaumatic is asking for the next pilot to fly	(test)	
        // Is a "next pilot" selected ?
        $logger->info("Request received for the next pilot.");

        $sql = "select nf.*, p.freestyle, p.imacClass "
             . "from nextFlight nf inner join pilot p on nf.nextPilotId = p.pilotId limit 1;";

        if ($statement = $db->prepare($sql)) {
            try {
                $res = $statement->execute();
            } catch (Exception $e) {
                $logger->error("There was an error executing the database query.");

                echo "return:900&H:".date('YmdHis', time());
                break;
            }
        } else {
            $logger->error("There was an error executing the database query.");
            echo "return:900&H:".date('YmdHis', time());
            break;
        }

        $nextFlight = $res->fetchArray();
        if (!$nextFlight || $nextFlight["nextPilotId"] === null || $nextFlight["nextPilotId"] <= 0) {
            $logger->info("No next pilot is set.");
            echo "return:-1";
            break;
        } else {
            $nextNoteFlightId = $nextFlight["nextNoteFlightId"];
            $nextPilotId = $nextFlight["nextPilotId"];
            $nextCompId = $nextFlight["nextCompId"];
            $nextPilotFreestyle = $nextFlight["freestyle"];
            $nextPilotImacClass = $nextFlight["imacClass"];

        }

        $sql = "select f.noteFlightId, f.roundId, f.sequenceNum, r.imacClass, r.schedId "
                . "from flight f inner join round r on f.roundId = r.roundId "
                . "where f.noteFlightId = :nextNoteFlightId "
                . "and r.imacClass = :nextCompImacClass "
                . "and r.phase = 'O'";

        $nextCompImacClass = convertCompIDToClass($nextCompId);
        $logger->info("The next flight details are Pilot:$nextPilotId Class:$nextCompImacClass ");

        if ($statement = $db->prepare($sql)) {
            try {
                $statement->bindValue(':nextNoteFlightId', $nextNoteFlightId);
                $statement->bindValue(':nextCompImacClass', $nextCompImacClass);
                $res = $statement->execute();
            } catch (Exception $e) {
                $logger->error("There was an error executing the database query.");
                echo "return:900&H:".date('YmdHis', time());
                break;
            }
        } else {
            $logger->error("There was an error executing the database query.");
            echo "return:900&H:".date('YmdHis', time());
            break;
        }

        $nextFlightRoundData = $res->fetchArray();
        if (!$nextFlightRoundData) {
            echo "return:-1";
            //print_r($nextFlight);
        } else {
            $roundClassId = convertClassToCompID($nextFlightRoundData["imacClass"]);
            if ( $nextCompImacClass == "Freestyle" ) {
                if ($nextPilotFreestyle != 1) {
                    echo "return:-1";
                    break;
                }
            } else if ($roundClassId != $nextCompId) {
                echo "return:-1";
                print_r($nextFlight);
                print "$nextPilotFreestyle";
                print convertCompIDToClass($nextCompId);
                break;
            }

            $schedId = $nextFlightRoundData["schedId"];
            // Return : pilot #, flight #, comp # and schedule's shortname
            $result = "return:".substr("000".$nextPilotId, -3);
            $result.= substr("00".$nextNoteFlightId, -2);
            $result.= substr("00".$nextCompId, -2);
            $result.= $schedId;
            echo $result;
        }
        break;
        
    default:
        echo "return:104";
        $logger->error("Incorrect operation($nautoption).");
        trigger_error('Wrong OPT Code', E_USER_WARNING);
}
