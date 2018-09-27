<?php

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