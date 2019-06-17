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


// DEBUT DECLARATION VARIABLES
$tabnote = array();
$tabjuges = array();
$i = 0;
$j = 0;
// FIN DECLARATION VARIABLES

$cmpid = $_GET['id'];
$pilid = $_GET['pilid'];
$volid = $_GET['volid'];

// recup $rowcmp
include 'data-cmp.php';
//include 'check-defaultlang.php';

$sqljuge = "
SELECT
	DISTINCT (JUG.jugeid),
	JUG.jugenom,
	JUG.jugeprenom,
	JUG.jugepos,
	JUG.jugepays,
  NTE.notebrute,
  NTE.judgeexcluded
FROM
	ffam_classement_note_juges NTE
INNER JOIN ffam_juge JUG ON JUG.jugeid = NTE.jugeid
WHERE
NTE.cmpid = ".$cmpid."
AND NTE.pilid = ".$pilid."
AND NTE.volid = ".$volid."
ORDER BY JUG.jugepos;";


$sqlexejuge = $PROD->Execute($sqljuge);

foreach($sqlexejuge as $rowjuges) {
  $tabjuges[$j][0] = $rowjuges['jugepos'];
  $tabjuges[$j][1] = $rowjuges['jugepays'];
  $tabjuges[$j][2] = $rowjuges['jugeid'];
  $tabjuges[$j][3] = $rowjuges['jugenom'];
  $tabjuges[$j][4] = $rowjuges['jugeprenom'];
  $tabjuges[$j]['scrorebrut'] = $rowjuges['notebrute'];
  $tabjuges[$j]['judgeexcluded'] = $rowjuges['judgeexcluded'];
  $j++;
}

$sqlnote = "
SELECT
	NTE.cmpid,
	NTE.phaseid,
	NTE.volid,
	NTE.figid,
	NTE.jugeid,
	VOL.volpos,
	PIL.pilid,
	PIL.pilnom,
  PIL.pilprenom,
  PIL.pilpays,
  PIL.pilcategory,
  PIL.pilbanniere,
	NTE.complexity,
	NTE.note,
	JUG.jugenom,
	JUG.jugeprenom,
	JUG.jugepos,
  PRG.prglibelle,
  PHA.libelle,
  NTE.notek,
  NTE.noteno,
  FIL.web_path,
  PILVOL.notejudgebrut
FROM
	ffam_classement_note NTE
INNER JOIN ffam_pilote PIL ON PIL.pilid = NTE.pilid
LEFT JOIN ffam_file FIL ON FIL.fileid = PIL.pilphotoid
INNER JOIN ffam_vol VOL ON VOL.volid = NTE.volid
INNER JOIN ffam_juge JUG ON JUG.jugeid = NTE.jugeid
INNER JOIN ffam_programme PRG ON PRG.prgid = VOL.prgid
INNER JOIN core_phase PHA ON PHA.id = VOL.phaseid
INNER JOIN ffam_classement_pilote_vol PILVOL ON PILVOL.cmpid = NTE.cmpid
      AND PILVOL.pilid = PIL.pilid
      AND PILVOL.volid = VOL.volid
WHERE	NTE.cmpid = ".$cmpid."
AND   PIL.pilid = ".$pilid."
AND   VOL.volid = ".$volid;


$sqlexenote = $PROD->Execute($sqlnote);


foreach($sqlexenote as $rownote) {
  $tabnote[$i][0]  = $rownote['cmpid'];
  $tabnote[$i][1]  = $rownote['phaseid'];
  $tabnote[$i][2]  = $rownote['volid'];
  $tabnote[$i][3]  = $rownote['figid'];
  $tabnote[$i][4]  = $rownote['jugeid'];
  $tabnote[$i][5]  = $rownote['volpos'];
  $tabnote[$i][6]  = $rownote['pilid'];
  $tabnote[$i][7]  = $rownote['pilnom'];
  $tabnote[$i][8]  = $rownote['complexity'];
  $tabnote[$i][9]  = $rownote['note'];
  $tabnote[$i][10] = $rownote['jugenom'];
  $tabnote[$i][11] = $rownote['jugeprenom'];
  $tabnote[$i][12] = $rownote['jugepos'];
  $tabnote[$i][13] = $rownote['notek'];
  $tabnote[$i][14] = $rownote['noteno'];
  $i++;
}

$k = 0;
for ($i=0; $i < sizeof ($tabjuges); $i++) {
  $scorejuge[$k][0] = 0;
  $resultjuge = SearchTab::multiSearchArray($tabnote, array(4 => $tabjuges[$i][2]));
    foreach ($resultjuge as $rowresultjuge) {
        $scorejuge[$k][0] = $scorejuge[$k][0]+($rowresultjuge[13]);
    }
    $k++;
}
$nbjuges=$k;
$scoremoyen = 0;
for ($l=0; $l < $nbjuges; $l++) {
  $scoretotal = $scoretotal+$scorejuge[$l][0];
}
$scoremoyen = $scoretotal/$nbjuges;
for ($j=0; $j < $nbjuges; $j++) {
  $scorejuge[$j][1] = (($scorejuge[$j][0]/$scoremoyen)*100)-100;
}

$sqlcountjudgenotexcluded = "
  SELECT sum(notebrute) / count(jugeid) as judgemeanscore
  FROM ffam_classement_note_juges
  WHERE cmpid = ".$cmpid."
  AND volid = ".$volid."
  AND pilid = ".$pilid."
  AND judgeexcluded <> 1;";

$rowmeanscore = $PROD->GetRow($sqlcountjudgenotexcluded);

$pilot_number = $rownote['pilbanniere'];
$flight_number = $rownote['volpos'];
$schedule = stripos($rownote['prglibelle'], 'unknown') === false ? 'KNOWN' : 'UNKNOWN';
$xml = array();
//echo "P:$pilot_number  F:$flight_number S:$schedule</th>"
// Affichage une ligne par figure
$sqlfigure = "
SELECT FIG.figdescription,
        FIG.figcomplexite,
        FIG.figid
FROM    ffam_vol VOL
INNER JOIN ffam_figure FIG ON FIG.prgid = VOL.prgid
WHERE   VOL.cmpid = ".$cmpid."
        AND VOL.volid = ".$volid."
ORDER BY FIG.figposition
";
$sqlexefigure = $PROD->Execute($sqlfigure);

//$numFig = 1;
$xmlfignum = 0;
echo "<flights>\n";

for ($i=0; $i < $nbjuges; $i++) {
    $xml[$i]  = "      <flight index=\"\">\n";
    $xml[$i] .= "         <pilot_primary_id>" . $pilot_number . "</pilot_primary_id>\n";
    $xml[$i] .= "         <type>" . $schedule . "</type>\n";
    $xml[$i] .= "         <round>" . $flight_number . "</round>\n";
    $xml[$i] .= "         <judge>" . ($i + 1) . "</judge>\n";
    $xml[$i] .= "         <sequence>1</sequence>\n";
    $xml[$i] .= "         <figures>\n";
}

foreach($sqlexefigure as $rowfigure) {
    //echo "<td>".substr("0".$numFig++, -2)." - ".$rowfigure['figdescription']."</td>";
    //echo "<td align ='center'>".$rowfigure['figcomplexite']."</td>";
    for ($i=0; $i < sizeof ($tabjuges); $i++) {
        $result = SearchTab::multiSearch($tabnote, array(4 => $tabjuges[$i][2], 3 => $rowfigure['figid']));
        $xml[$i] .= "            <figure index=\"" . $xmlfignum . "\">\n";
        $xml[$i] .= "               <raw_score>" . floatval(number_format($tabnote[$result][9], 1, '.', ' ')) . "</raw_score>\n";
        $xml[$i] .= "               <break_err>false</break_err>\n";
        $xml[$i] .= "            </figure>\n";
    }
    $xmlfignum++;
}
for ($i=0; $i < $nbjuges; $i++) {
    $xml[$i] .= "         </figures>\n";
    $xml[$i] .= "      </flight>\n";
    echo $xml[$i];
}
echo "</flights>";
?>
