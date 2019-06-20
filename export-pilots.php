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
// Création: Laurent Henry lhenry3@gmail.com
// date:    06/2016
// version: 1
//********************************************************************************
// Dernière modification
// version: v4.3
// Date:    07/2017
//********************************************************************************
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-type: application/xml');

$tabActive = "competition";
$toggleNavigation = false;
$GLOBALS['mainTheme'] = "satblue";

include 'session.php';
include 'function.php';
include 'decide_lang.php';
include 'ffam-function.php';

$i = 0;
$xml = array();
$cmpidclassess = explode(',', $_GET['idcls']);

include 'data-cmp.php';

/*   Here is what a record looks like.
      <pilot index="0">
         <primary_id>2</primary_id>
         <secondary_id></secondary_id>
         <name>Angelo Casamento</name>
         <addr1></addr1>
         <addr2></addr2>
         <airplane></airplane>
         <missing_pilot_panel>false</missing_pilot_panel>
         <comments></comments>
         <active>true</active>
         <freestyle>false</freestyle>
         <classes>
            <class>SPORTSMAN</class>
         </classes>
         <spread_spectrum>false</spread_spectrum>
         <frequency>0</frequency>
      </pilot>
*/
$numpilots = 0;
foreach ($cmpidclassess as $cmpidcls) {
    list($cmpid, $cmpclass) = explode('=', $cmpidcls);
    $sqlpilots = "SELECT * FROM `vpilote` WHERE cmpid = " . $cmpid . " ORDER BY pilid";
    $sqlexepilots = $PROD->Execute($sqlpilots);


    foreach($sqlexepilots as $rowpilots) {
      $xml[$i] =  "      <pilot index=\"" . $i . "\">\n";
      $xml[$i] .= "         <primary_id>" . $rowpilots['pilid'] . "</primary_id>\n";
      $xml[$i] .= "         <secondary_id></secondary_id>\n";
      $xml[$i] .= "         <name>" . $rowpilots['pilprenom'] . " " . $rowpilots['pilnom'] . "</name>\n";
      $xml[$i] .= "         <addr1>" . $rowpilots['pilpays'] . "</addr1>\n";
      $xml[$i] .= "         <addr2></addr2>\n";
      $xml[$i] .= "         <airplane></airplane>\n";
      $xml[$i] .= "         <missing_pilot_panel>false</missing_pilot_panel>\n";
      $xml[$i] .= "         <comments></comments>\n";
      $xml[$i] .= "         <active>true</active>\n";
      $xml[$i] .= "         <freestyle>true</freestyle>\n";
      $xml[$i] .= "         <classes>\n";
      $xml[$i] .= "            <class>" . $cmpclass . "</class>\n";
      $xml[$i] .= "         </classes>\n";
      $xml[$i] .= "         <spread_spectrum>false</spread_spectrum>\n";
      $xml[$i] .= "         <frequency>0</frequency>\n";
      $xml[$i] .= "      </pilot>\n";
      $i++;
    }
    $numpilots = $i;    
}

echo "<pilots>\n";

for ($i=0; $i < $numpilots; $i++) {
    echo $xml[$i];
}
echo "</pilots>";

