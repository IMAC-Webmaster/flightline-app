<?php
ini_set("display_errors", 0);

// Database details
$dbfile = 'scruddemo.db';
//$dbfile = 'cant.db';

// Get job (and id)
$job = '';
$id  = '';
if (isset($_GET['job'])){
  $job = $_GET['job'];
  if ($job == 'get_companies' ||
      $job == 'get_company'   ||
      $job == 'add_company'   ||
      $job == 'edit_company'  ||
      $job == 'delete_company'){
    if (isset($_GET['id'])){
      $id = $_GET['id'];
      if (!is_numeric($id)){
        $id = '';
      }
    }
  } else {
    $job = '';
  }
}

// Prepare array
$sqlite_data = array();
unset($message);

// Connect to database
try {
  $db = new SQLite3($dbfile);
} catch (Exception $e) {
  $result  = 'error';
  $message = 'Failed to connect to database: ' . $e->getMessage();
  $job     = '';
}    

// Valid job found
if ($job != ''){

  // Execute job
  if ($job == 'get_companies') {
    // Get companies
    $query = "SELECT * FROM it_companies ORDER BY rank";
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
      while ($company = $res->fetchArray()){
        $functions  = '<div class="function_buttons"><ul>';
        $functions .= '<li class="function_edit"><a data-id="'   . $company['company_id'] . '" data-name="' . $company['company_name'] . '"><span>Edit</span></a></li>';
        $functions .= '<li class="function_delete"><a data-id="' . $company['company_id'] . '" data-name="' . $company['company_name'] . '"><span>Delete</span></a></li>';
        $functions .= '</ul></div>';
        $sqlite_data[] = array(
          "rank"          => $company['rank'],
          "company_name"  => $company['company_name'],
          "industries"    => $company['industries'],
          "revenue"       => '$ ' . $company['revenue'],
          "fiscal_year"   => $company['fiscal_year'],
          "employees"     => number_format($company['employees'], 0, '.', ','),
          "market_cap"    => '$ ' . $company['market_cap'],
          "headquarters"  => $company['headquarters'],
          "functions"     => $functions
        );
      }
    }
    
  } elseif ($job == 'get_company') {
    
    // Get company
    if ($id == ''){
      $result  = 'error';
      $message = 'id missing';
    } else {
      $query = "SELECT * FROM it_companies WHERE company_id = :id";

      if ($statement = $db->prepare($query)) {
        try {
          $statement->bindValue(':id', $id);
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
        while ($company = $res->fetchArray()){
          $sqlite_data[] = array(
            "rank"          => $company['rank'],
            "company_name"  => $company['company_name'],
            "industries"    => $company['industries'],
            "revenue"       => $company['revenue'],
            "fiscal_year"   => $company['fiscal_year'],
            "employees"     => $company['employees'],
            "market_cap"    => $company['market_cap'],
            "headquarters"  => $company['headquarters']
          );
        }
      }
    }
  
  } elseif ($job == 'add_company') {
    
    // Add company
    $query = "INSERT INTO it_companies (";
    if (isset($_GET['rank']))         { $query .= "rank, ";            }
    if (isset($_GET['company_name'])) { $query .= "company_name, ";    }
    if (isset($_GET['industries']))   { $query .= "industries, ";      }
    if (isset($_GET['revenue']))      { $query .= "revenue, ";         }
    if (isset($_GET['fiscal_year']))  { $query .= "fiscal_year, ";     }
    if (isset($_GET['employees']))    { $query .= "employees, ";       }
    if (isset($_GET['market_cap']))   { $query .= "market_cap, ";      }
    if (isset($_GET['headquarters'])) { $query .= "headquarters, ";    }
    $query .= "company_id) VALUES (";
    if (isset($_GET['rank']))         { $query .= ":rank, "; }
    if (isset($_GET['company_name'])) { $query .= ":company_name, ";   }
    if (isset($_GET['industries']))   { $query .= ":industries, ";     }
    if (isset($_GET['revenue']))      { $query .= ":revenue, ";        }
    if (isset($_GET['fiscal_year']))  { $query .= ":fiscal_year, ";    }
    if (isset($_GET['employees']))    { $query .= ":employees, ";      }
    if (isset($_GET['market_cap']))   { $query .= ":market_cap, ";     }
    if (isset($_GET['headquarters'])) { $query .= ":headquarters, ";   }
    $query .= ":id )";

    if ($statement = $db->prepare($query)) {
      try {
        $statement->bindValue(':id', null);
        if (isset($_GET['rank']))         { $statement->bindValue(':rank',         $_GET['rank']);         };
        if (isset($_GET['company_name'])) { $statement->bindValue(':company_name', $_GET['company_name']); };
        if (isset($_GET['industries']))   { $statement->bindValue(':industries',   $_GET['industries']);   };
        if (isset($_GET['revenue']))      { $statement->bindValue(':revenue',      $_GET['revenue']);      };
        if (isset($_GET['fiscal_year']))  { $statement->bindValue(':fiscal_year',  $_GET['fiscal_year']);  };
        if (isset($_GET['employees']))    { $statement->bindValue(':employees',    $_GET['employees']);    };
        if (isset($_GET['market_cap']))   { $statement->bindValue(':market_cap',   $_GET['market_cap']);   };
        if (isset($_GET['headquarters'])) { $statement->bindValue(':headquarters', $_GET['headquarters']); };
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

  } elseif ($job == 'edit_company') {
    
    // Edit company
    if ($id == ''){
      $result  = 'error';
      $message = 'id missing';
    } else {
      $query = "UPDATE it_companies SET ";

      if (isset($_GET['rank']))         { $query .= "rank         = :rank, "; }
      if (isset($_GET['company_name'])) { $query .= "company_name = :company_name, "; }
      if (isset($_GET['industries']))   { $query .= "industries   = :industries, "; }
      if (isset($_GET['revenue']))      { $query .= "revenue      = :revenue, "; }
      if (isset($_GET['fiscal_year']))  { $query .= "fiscal_year  = :fiscal_year, "; }
      if (isset($_GET['employees']))    { $query .= "employees    = :employees, "; }
      if (isset($_GET['market_cap']))   { $query .= "market_cap   = :market_cap, "; }
      if (isset($_GET['headquarters'])) { $query .= "headquarters = :headquarters ";   }

      $query .= "WHERE company_id = :id";

      $statement = null;
      if ($statement = $db->prepare($query)) {
        try {
          if (isset($_GET['id']))           { $statement->bindValue(':id',           $_GET['id']);           }
          if (isset($_GET['rank']))         { $statement->bindValue(':rank',         $_GET['rank']);         }
          if (isset($_GET['company_name'])) { $statement->bindValue(':company_name', $_GET['company_name']); }
          if (isset($_GET['industries']))   { $statement->bindValue(':industries',   $_GET['industries']);   }
          if (isset($_GET['revenue']))      { $statement->bindValue(':revenue',      $_GET['revenue']);      }
          if (isset($_GET['fiscal_year']))  { $statement->bindValue(':fiscal_year',  $_GET['fiscal_year']);  }
          if (isset($_GET['employees']))    { $statement->bindValue(':employees',    $_GET['employees']);    }
          if (isset($_GET['market_cap']))   { $statement->bindValue(':market_cap',   $_GET['market_cap']);   }
          if (isset($_GET['headquarters'])) { $statement->bindValue(':headquarters', $_GET['headquarters']); }

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
    }

  } elseif ($job == 'delete_company') {
  
    // Delete company
    if ($id == ''){
      $result  = 'error';
      $message = 'id missing';
    } else {
      $query = "DELETE FROM it_companies WHERE company_id = :id";
      if ($statement = $db->prepare($query)) {
        try {
          $statement->bindValue(':id', $id);
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
    }
  }
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
