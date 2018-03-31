<?php
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
      $job == 'get_nextrnds'     ||
      $job == 'get_schedlist'    ||
      $job == 'add_round'        ||
      $job == 'edit_round'       ||
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
    $query = "select s.name, s.id, r.class as roundclass, r.type, r.roundnum, r.sequences, r.phase, r.status from round r left join schedule s on s.id = r.sched order by r.class, r.type, r.roundnum;";
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
                $functions .= '<li class="function_start"><a data-class="'  . $round['roundclass'] . '" data-type="' . $round['type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Start</span></a></li>';
                $functions .= '<li class="function_edit"><a data-class="'   . $round['roundclass'] . '" data-type="' . $round['type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Edit</span></a></li>';
                $functions .= '<li class="function_delete"><a data-class="' . $round['roundclass'] . '" data-type="' . $round['type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Delete</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
            case "O":
                $functions .= '<li class="function_pause"><a data-class="'   . $round['roundclass'] . '" data-type="' . $round['type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Pause</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                break;
            case "P":
                $functions .= '<li class="function_start"><a data-class="'   . $round['roundclass'] . '" data-type="' . $round['type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Start</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_blankspace"><a><span>Spacer</span></a></li>';
                $functions .= '<li class="function_finish"><a data-class="'  . $round['roundclass'] . '" data-type="' . $round['type'] . '" data-roundnum="' . $round['roundnum'] . '"><span>Finalise</span></a></li>';
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
          "class"         => $round['roundclass'],
          "type"          => $round['type'],
          "roundnum"      => $round['roundnum'],
          "name"          => $round['name'],
          "id"            => $round['id'],
          "sequences"     => $round['sequences'],
          "phase"         => $round['phase'],
          "status"        => $round['status'],
          "functions"     => $functions
        );
      }
    }
  } // get_rounds...
  
 elseif ($job == 'get_round') {
    // Get round  (class, type, round number).
    if (isset($_GET['class'])){ $class = $_GET['class'];} else $class = null;
    if (isset($_GET['type'])){ $type = $_GET['type'];} else $type = null;
    if (isset($_GET['roundnum'])){ $roundnum = $_GET['roundnum'];} else $roundnum = null;


    $query =  "select s.name, s.id, r.class as roundclass, r.type, r.roundnum, r.sequences, r.phase, r.status from round r left join schedule s on s.id = r.sched ";
    $query .= "where r.class = :class and r.type = :type and r.roundnum = :roundnum";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':class',    $class);
        $statement->bindValue(':type',     $type);
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
      $result  = 'success';
      $message = 'query success';
      while ($round = $res->fetchArray()){
        $sqlite_data[] = array(
          "class"         => $round['roundclass'],
          "type"          => $round['type'],
          "roundnum"      => $round['roundnum'],
          "name"          => $round['name'],
          "id"            => $round['id'],
          "sequences"     => $round['sequences'],
          "phase"         => $round['phase'],
          "status"        => $round['status']
        );
      }
    }
  } // get_round...

  elseif ($job == 'get_nextrnds') {
    // Get rounds
    $query = "select class as roundclass, type, (max(roundnum) + 1) as nextroundnum from round group by class, type;";
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
          "class"         => $round['roundclass'],
          "type"          => $round['type'],
          "nextroundnum"  => $round['nextroundnum'],
        );
      }
    }
  } // get_roundlist...

  elseif ($job == 'get_schedlist') {
    // Get rounds
    $query = "select * from schedule order by class;";
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
          "id"         => $round['id'],
          "class"      => $round['class'],
          "type"       => $round['type'],
          "name"       => $round['name'],
        );
      }
    }
  } // get_schedlist...
 
  elseif ($job == 'add_round') {
    // Add round
      
    $query =  "INSERT into round (class, type, roundnum, sched, sequences, phase) ";
    $query .= "VALUES (:class, :type, :roundnum, :sched, :sequences, :phase );";

    if ($statement = $db->prepare($query)) {
      try {
        if (isset($_GET['class']))      { $statement->bindValue(':class',        $_GET['class']);        };
        if (isset($_GET['type']))       { $statement->bindValue(':type',         $_GET['type']);         };
        if (isset($_GET['roundnum']))   { $statement->bindValue(':roundnum',     $_GET['roundnum']);     };
        if (isset($_GET['schedule']))   { $statement->bindValue(':sched',        $_GET['schedule']);     };
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
    if (isset($_GET['class'])){ $class = $_GET['class'];} else $class = null;
    if (isset($_GET['type'])){ $type = $_GET['type'];} else $type = null;
    if (isset($_GET['roundnum'])){ $roundnum = $_GET['roundnum'];} else $roundnum = null;
    $query = "delete from round where class = :class and type = :type and roundnum = :roundnum and phase ='U';";
    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':class',    $class);
        $statement->bindValue(':type',     $type);
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
elseif ($job == 'edit_round') {
    // Edit round
    $blOkToGo = true;
    if (isset($_GET['prevclass'])){     $prevclass      = $_GET['prevclass'];   } else $blOkToGo = false;
    if (isset($_GET['prevtype'])){      $prevtype       = $_GET['prevtype'];    } else $blOkToGo = false;
    if (isset($_GET['prevroundnum'])){  $prevroundnum   = $_GET['prevroundnum'];} else $blOkToGo = false;
    if (isset($_GET['class'])){         $class          = $_GET['class'];       } else $blOkToGo = false;
    if (isset($_GET['type'])){          $type           = $_GET['type'];        } else $blOkToGo = false;
    if (isset($_GET['roundnum'])){      $roundnum       = $_GET['roundnum'];    } else $blOkToGo = false;
    if (isset($_GET['schedule'])){      $sched          = $_GET['schedule'];    } else $blOkToGo = false;
    if (isset($_GET['sequences'])){     $sequences      = $_GET['sequences'];   } else $blOkToGo = false;
    
    if (!blOkToGo) {
      $result  = 'error';
      $message = 'Unable to edit this round.  Some form data was missing.';
    } else {
      $query  = "update round set class = :class, type = :type, roundnum = :roundnum, sched = :sched, sequences = :sequences ";
      $query .= "where class = :prevclass and type = :prevtype and roundnum = :prevroundnum and phase ='U';";
      
      if ($statement = $db->prepare($query)) {
        try {
          $statement->bindValue(':prevclass',    $prevclass);
          $statement->bindValue(':prevtype',     $prevtype);
          $statement->bindValue(':prevroundnum', $prevroundnum);
          $statement->bindValue(':class',        $class);
          $statement->bindValue(':type',         $type);
          $statement->bindValue(':roundnum',     $roundnum);
          $statement->bindValue(':sched',        $sched);
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
