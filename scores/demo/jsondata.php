<?php
if ( session_status() !== PHP_SESSION_ACTIVE ) session_start();
if (isset($_SESSION['time']) && isset($_SESSION['views'])) {
    if (time() > ($_SESSION['time'] + 3)) {
        $_SESSION['views'] = $_SESSION['views'] + 2;
        $_SESSION['time'] = time();
    }
} else {
    $_SESSION['time'] = time();
    $_SESSION['views'] = 0;
}

if($_SESSION['views'] >= 36){
    $_SESSION['views'] = 0;
}

header('Content-Type: application/json');
global $json;

include_once ("data.php");

echo $json;