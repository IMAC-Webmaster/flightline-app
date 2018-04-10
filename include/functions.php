<?php

function convertClassToCompID($imac_class, $imac_type) {

    switch (strtolower($imac_class)) {
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
        case "invitational":
            return 7;
        default:
            if (strtolower($imac_type) === "freestyle")
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