<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once('db_config.php');

function db_connect() {
    global $conf_db_type;
    switch($conf_db_type) {
        case "sqlite":
            return db_connect_sqlite();
        case "mysql":
            return db_connect_mysql();
        default:
            return db_connect_sqlite();
    }
}

function db_connect_sqlite() {
    global $conf_db_connect;
    $db = new SQLite3($conf_db_connect);
    $db->busyTimeout(5000);
    // WAL mode has better control over concurrency.
    // Source: https://www.sqlite.org/wal.html
    $db->exec('PRAGMA journal_mode = wal;');
    return ($db);
}

function db_prepare ($db, $sql) {
    return $db->prepare($sql);
}

function db_exec ($statement) {
    return $statement->execute();
    
}

function db_close($db) {
    $db->close();
}
