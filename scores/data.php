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

global $json;

$data = array (
    (object) [ "Number" => "01", "Figure" => "Roll",                 "Judge1" => (object) [ "value" => "7",   "features" => array() ], "Judge2" => (object) [ "value" => "7",   "features" => array() ], "Judge3" => (object) [ "value" => "8",   "features" => array() ] ],
    (object) [ "Number" => "02", "Figure" => "Loop",                 "Judge1" => (object) [ "value" => "4",   "features" => array() ], "Judge2" => (object) [ "value" => "3",   "features" => array() ], "Judge3" => (object) [ "value" => "5",   "features" => array() ] ],
    (object) [ "Number" => "03", "Figure" => "Humpty Bump",          "Judge1" => (object) [ "value" => "6",   "features" => array() ], "Judge2" => (object) [ "value" => "5",   "features" => array() ], "Judge3" => (object) [ "value" => "5.5", "features" => array() ] ],
    (object) [ "Number" => "04", "Figure" => "Hammerhead",           "Judge1" => (object) [ "value" => "7",   "features" => array() ], "Judge2" => (object) [ "value" => "6.5", "features" => array() ], "Judge3" => (object) [ "value" => "6",   "features" => array() ] ],
    (object) [ "Number" => "05", "Figure" => "Reverse Half Cuban",   "Judge1" => (object) [ "value" => "7",   "features" => array() ], "Judge2" => (object) [ "value" => "5",   "features" => array() ], "Judge3" => (object) [ "value" => "8",   "features" => array() ] ],
    (object) [ "Number" => "06", "Figure" => "Two turn spin",        "Judge1" => (object) [ "value" => "5.5", "features" => array() ], "Judge2" => (object) [ "value" => "6.5", "features" => array() ], "Judge3" => (object) [ "value" => "4.5", "features" => array() ] ],
    (object) [ "Number" => "07", "Figure" => "Sharks Tooth",         "Judge1" => (object) [ "value" => "6",   "features" => array() ], "Judge2" => (object) [ "value" => "7",   "features" => array() ], "Judge3" => (object) [ "value" => "8.5", "features" => array() ] ],
    (object) [ "Number" => "08", "Figure" => "Immelmann",            "Judge1" => (object) [ "value" => "6",   "features" => array() ], "Judge2" => (object) [ "value" => "6",   "features" => array() ], "Judge3" => (object) [ "value" => "7",   "features" => array() ] ],
    (object) [ "Number" => "09", "Figure" => "Positive Snap",        "Judge1" => (object) [ "value" => "6",   "features" => array() ], "Judge2" => (object) [ "value" => "6",   "features" => array() ], "Judge3" => (object) [ "value" => "6",   "features" => array() ] ],
    (object) [ "Number" => "10", "Figure" => "Figure P",             "Judge1" => (object) [ "value" => "2.5", "features" => array() ], "Judge2" => (object) [ "value" => "2.5", "features" => array() ], "Judge3" => (object) [ "value" => "3",   "features" => array() ] ],
    (object) [ "Number" => "11", "Figure" => "Sound",                "Judge1" => (object) [ "value" => "8",   "features" => array() ], "Judge2" => (object) [ "value" => "8",   "features" => array() ], "Judge3" => (object) [ "value" => "8",   "features" => array() ] ],
    (object) [ "Number" => "12", "Figure" => "Airspace",             "Judge1" => (object) [ "value" => "8",   "features" => array() ], "Judge2" => (object) [ "value" => "6",   "features" => array("new") ], "Judge3" => (object) [ "value" => "7",   "features" => array("new") ] ]
);


    for ($i = 0; $i < 36; $i++) {
        $col = ($i % 3);
        $row = (int)($i / 3);
        if ($i > $_SESSION['views']) {
            switch($col) {
                case 0:
                    unset($data[$row]->Judge1);
                    break;
                case 1:
                    unset($data[$row]->Judge2);
                    break;
                case 2:
                    unset($data[$row]->Judge3);
                    break;
            }

            // Now set the previous 2 as new...
            $j = $i - 2;
            if ($j > 0 && ($j > $_SESSION['views'] - 2)) {
                $col = ($j % 3);
                $row = (int)($j / 3);
                switch($col) {
                    case 0:
                        if (isset($data[$row]->Judge1)) {
                            $data[$row]->Judge1->features = array("new");
                        }
                        break;
                    case 1:
                        if (isset($data[$row]->Judge2)) {
                            $data[$row]->Judge2->features = array("new");
                        }
                        break;
                    case 2:
                        if (isset($data[$row]->Judge3)) {
                            $data[$row]->Judge3->features = array("new");
                        }
                        break;
                }
            }
        }
    }


$response = (object) [
    "data" => $data
];

$json = json_encode($response, JSON_PRETTY_PRINT);
