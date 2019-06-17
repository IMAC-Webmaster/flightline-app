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

/**
 * Generate a random string, using a cryptographically secure
 * pseudorandom number generator (random_int)
 *
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 *
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */
function random_str(
    $length,
    $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
) {
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    if ($max < 1) {
        throw new Exception('$keyspace must be at least two characters long');
    }
    for ($i = 0; $i < $length; ++$i) {
        $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
}

function convertClassToCompID($imacClass) {

    switch (strtolower($imacClass)) {
        case "basic":
            return 1;
        case "sportsman":
            return 2;
        case "intermediate":
            return 3;
        case "advanced":
            return 4;
        case "unlimited":
            return 5;
        case "freestyle":
            return 6;
        case "invitational":
            return 7;
        default:
            // No idea...   Give it a unique one.
            return 8;
    }
}

function convertCompIDToClass($compId) {

    switch ($compId) {
        case 1:
        case "1":
            return "Basic";
        case 2:
        case "2":
            return "Sportsman";
        case 3:
        case "3":
            return "Intermediate";
        case 4:
        case "4":
            return "Advanced";
        case 5:
        case "5":
            return "Unlimited";
        case 6:
        case "6":
            return "Freestyle";
        case 7:
        case "7":
            return "Invitational";
        default:
            // No idea...   Give it a unique one.
            return "NoIdea";
    }
}

function getClassFromSchedule($imacSchedule) {

    switch (strtolower($imacClass)) {
        case "bas":
            return 1;
        case "spr":
        case "spo":
            return 2;
        case "int":
            return 3;
        case "adv":
            return 4;
        case "unl":
            return 5;
        case "inv":
            return 7;
        case "fre":
            return 6;
        default:
            if (strtolower($imacType) === "freestyle")
                // Give freestyle it's own class.
                return 6;
            else
                // No idea...   Give it a unique one.
                return 8;
    }
}

function array_strip(&$arr) {
    // Strip the array of all numerical keys.
    for ($i = 0; $i < (sizeof($arr, 0)); $i++) {
        unset ($arr[$i]);
    }
}

function dbConnect($dbfile) {
    global $db;
    try {
        $db = new SQLite3($dbfile);
        $db->busyTimeout(5000);
        // WAL mode has better control over concurrency.
        // Source: https://www.sqlite.org/wal.html
        $db->exec('PRAGMA journal_mode = wal;');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function dbDisconnect() {
    global $db;
    $db->close();
}