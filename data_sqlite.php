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
  if ($job == 'get_rounds' ||
      $job == 'get_round'   ||
      $job == 'add_round'   ||
      $job == 'edit_round'  ||
      $job == 'delete_round') {
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
  
 if ($job == 'get_round') {
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
  } // get_round...
  
  else if ($job == 'delete_round') {
    // Delete round
    if (isset($_GET['class'])){ $class = $_GET['class'];} else $class = null;
    if (isset($_GET['type'])){ $type = $_GET['type'];} else $type = null;
    if (isset($_GET['roundnum'])){ $roundnum = $_GET['roundnum'];} else $roundnum = null;
    $query = "delete from round where class = :class and type = :type and roundnum = :roundnum;";
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
    }
  } // delete_round...

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
