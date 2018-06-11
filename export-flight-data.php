<?php
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

include 'session.php';
include 'function.php';
include 'decide_lang.php';
include 'ffam-function.php';

$debug = false;
$i = 0;
$numflights = 0;
$xml = array();
$cmpids = explode(',', $_GET['id']);

include 'data-cmp.php';

/*   Here is what a record looks like.
<flight index="77">
         <pilot_primary_id>2239</pilot_primary_id>
         <type>UNKNOWN</type>
         <round>3</round>
         <sequence>1</sequence>
         <judge>1</judge>
         <missing_pilot_panel>false</missing_pilot_panel>
         <figures>
            <figure index="0">
               <raw_score>8.0</raw_score>
               <break_err>false</break_err>
            </figure>
            <figure index="1">
               <raw_score>8.0</raw_score>
               <break_err>false</break_err>
            </figure>
            ...
            <figure index="11">
               <raw_score>7.0</raw_score>
               <break_err>false</break_err>
            </figure>
         </figures>
      </flight>

*/
        $lastjudge = -1;
        $lastpilot = -1;

foreach ($cmpids as $cmpid) {
    $sqlcompflights = "SELECT volid FROM ffam_vol WHERE cmpid = " . $cmpid . " ORDER BY volid";
    $sqlexecompflights = $PROD->Execute($sqlcompflights);
    if ($debug) echo "Checking comp: " . $cmpid . "\n";

    foreach($sqlexecompflights as $rowcompoflights) {
        // Now for each pilot, start saving the judges scores.
        // Also have to get the figure data...
        if ($debug) echo "Checking flight: " . $rowcompoflights['volid'] . "\n";

        $sqlscores = "
            SELECT  NTE.cmpid, NTE.phaseid, NTE.volid, NTE.figid, NTE.jugeid,
                    FIG.figposition,
                    VOL.volpos, PIL.pilid, PIL.pilnom, PIL.pilprenom, 
                    PIL.pilpays, PIL.pilcategory, PIL.pilbanniere, NTE.complexity, 
                    NTE.note, JUG.jugenom, JUG.jugeprenom, JUG.jugepos, PRG.prglibelle, 
                    PHA.libelle, NTE.notek, NTE.noteno, FIL.web_path, PILVOL.notejudgebrut 

            FROM ffam_classement_note NTE INNER JOIN ffam_pilote PIL ON PIL.pilid = NTE.pilid 
                LEFT JOIN ffam_file FIL ON FIL.fileid = PIL.pilphotoid 
                INNER JOIN ffam_vol VOL ON VOL.volid = NTE.volid 
                INNER JOIN ffam_figure FIG ON FIG.figid = NTE.figid 
                INNER JOIN ffam_juge JUG ON JUG.jugeid = NTE.jugeid 
                INNER JOIN ffam_programme PRG ON PRG.prgid = VOL.prgid 
                INNER JOIN core_phase PHA ON PHA.id = VOL.phaseid 
                INNER JOIN ffam_classement_pilote_vol PILVOL ON PILVOL.cmpid = NTE.cmpid AND PILVOL.pilid = PIL.pilid AND PILVOL.volid = VOL.volid 
            WHERE NTE.cmpid = " . $cmpid . "
			AND VOL.volid = " . $rowcompoflights['volid'] . "
            ORDER BY PIL.pilid, VOL.volpos, JUG.jugepos, FIG.figposition
        ";
        
        $sqlexescores = $PROD->Execute($sqlscores);
        foreach($sqlexescores as $rowscores) {
            if ($rowscores['pilid'] != $lastpilot || $rowscores['jugepos'] != $lastjudge) {
                // New judge and/or pilot.    That means new flight record.
                //if ($debug) echo "Checking flight for pilot: " . $rowscores['pilid'] . " flight: " . $rowcompoflights['volid'] . " Judge: " . $rowscores['jugepos'] . "\n";

                if ($lastjudge != -1) {
                    // Finish it off!
                    $xml[$i] .= "         </figures>\n";
                    $xml[$i] .= "      </flight>\n";
                    $i++;
                }
                $lastpilot = $rowscores['pilid'];
                $lastjudge = $rowscores['jugepos'];
                $schedule = stripos($rowscores['prglibelle'], 'unknown') === false ? 'KNOWN' : 'UNKNOWN';
                if ($debug) echo "Storing flight $i for pilot: " . $rowscores['pilid'] . " flight: " . $rowcompoflights['volid'] . " Judge: " . $rowscores['jugepos'] . "\n";

                $xml[$i] =  "      <flight index=\"" . $i . "\">\n";
                $xml[$i] .= "         <pilot_primary_id>" . $rowscores['pilid'] . "</pilot_primary_id>\n";
                $xml[$i] .= "         <type>" . $schedule . "</type>\n";
                $xml[$i] .= "         <round>" . $rowscores['volpos'] . "</round>\n";
                $xml[$i] .= "         <sequence>1</sequence>\n";
                $xml[$i] .= "         <judge>" . $rowscores['jugepos']  . "</judge>\n";
                $xml[$i] .= "         <missing_pilot_panel>false</missing_pilot_panel>\n";
                $xml[$i] .= "         <figures>\n";
            }
            $xml[$i] .= "            <figure index=\"" . ( $rowscores['figposition'] - 1 ) . "\">\n";
            $xml[$i] .= "               <raw_score>" . $rowscores['note'] . "</raw_score>\n";
            $xml[$i] .= "               <break_err>false</break_err>\n";
            $xml[$i] .= "            </figure>\n";
        }
        //$xml[$i] .= "         </figures>\n";
        //$xml[$i] .= "      </flight>\n";
        $i++;// Catch the last one.
    }
}
$numflights = $i;
echo "<flights>\n";
for ($i=0; $i <= $numflights; $i++) {
    echo $xml[$i];
}
echo "         </figures>\n";
echo "      </flight>\n";
echo "</flights>";