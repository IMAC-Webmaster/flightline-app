<?php
//********************************************************************************
// Création: Laurent Henry lhenry3@gmail.com
// date:    06/2016
//
//********************************************************************************
// Dernière modification
// version: v3.2.2
// Date:    31/01/2017
//********************************************************************************
// LHE Modif 20160430 - V1.1
// 		=> prise en compte uniquement des statuts des vols pour les notaumatic
//		=> modification pilinflight
//*************************************************
// LHE Modif V2.7
// prise en compte option OPT=H pour renvoi de l'heure format HEURE=AAAAMMJJHH24MMSS
//*************************************************
// LHE Modif V3.0.2
// Ajout option N pour prise en compte immédiate note avec uniquement une note
//*************************************************
// RPO Modif V3.2.3
// Revue de code, renommage en import-notaumatic.php
// Suppression du DELETE SQL qui ne fonctionnait pas
//*************************************************
// LHE Modif V4.3
// Si OPT=U => delete puis insertion
// Si OPT=N => replace
//*************************************************
// Multicomp modification
// Artur Uzieblo
// Date:    13/12/2017
// the code scans through all open flights across all competitions and attempts to find one
// unique competition matching the pilot and judge numbers. It does rely on a competitions 
// definition where the pilot and judge numbers are unique across all comps that can be opened at a time
// Also added C option support
//*************************************************
// Next Pilot modification
// Roland Poidevin
// Date:    21/12/2017
// Option "P"
// The code search if the "next pilot to fly" is set in ffam_pilot_next and return pilot pilbanniere

// URL
//server/import-notaumatic.php?OPT=U&F=1&J=8&D=58&N1=0&N2=0&N3=0&N4=0&N5=0&N6=0&N7=0&N8=0&N9=0&N10=0&N11=0

//http://192.168.100.200/import-notaumatic.php/?OPT=U&F=1&J=8&D=58&N1=1&N2=1&N3=1&N4=1&N5=0&N6=0&N7=0&N8=0&N9=0&N10=0&N11=1&N12=1&N13=1&N14=1&N15=1&N16=1&N17=1&N18=1

//http://192.168.1.220/import-notaumatic.php?OPT=U&F=1&J=2&D=1&N1=1&N2=2&N3=3&N4=4&N5=5&N6=6&N7=7&N8=8&N9=9&N10=10&N11=11
//http://localhost/html/import-notaumatic.php?OPT=P&C=1&F=1


// OPT = Option (U pour update, T pour Test, H pour Heure, N pour note unique, P pour next Pilot)
// C        = numéro de compétition
// F		= numéro de vol
// J		= numéro de juge
// D		= numéro de dossard
// Nx		= Notes
/*
Error code
900 --> Erreur SQL recup info vol
901 --> Erreur SQL recup pilid
902 --> Erreur SQL recup figid
903 --> Erreur SQL update table note
904 --> Erreur SQL insert table logs

100 --> Erreur No flight or notaumatic flag defined
101 --> Erreur Judge not allowed for this flight or do not exist
102 --> Erreur No pilot found with this id
103 --> Erreur Nombre de note envoyé non cohérent
104 --> Erreur Mauvais code option
105 --> Could not identified a unique competition match
-1  --> Could not find the next pilot
0   --> Fin Ok
*/

require_once('lib/adodb/adodb.inc.php');
require_once('conf/conf.ffam.php');

/*
	if ($_GET['debug'] == "on") {
	  //$PROD->debug = true;
	  $starttimetotal = microtime(true);
	};
*/
$nautoption = $_GET['OPT'];
if ($nautoption == "H") {
    
    date_default_timezone_set($timezone);    
    echo "return:0&H:".date('YmdHis', time());
    exit;
}

/*
if ($nautoption == "T")	//test pour IMAC
{
	echo "return:0&seq=1";
	exit;
};
*/

if ($nautoption != "T" && $nautoption != "U" && $nautoption != "N" && $nautoption != "P" )  {
    echo "return:104";
    trigger_error('Wrong OPT Code', E_USER_WARNING);
    exit;
};

if ($nautoption == "P")	{	// The Notaumatic is asking for the next pilot to fly	(test)	
	// Is a "next pilot" checked ?
    $sql = "SELECT a.cmpid, b.pilbanniere, d.prgshortname, c.volpos
	    FROM       ffam_pilot_next a 
	    INNER JOIN ffam_pilote     b ON a.pilid = b.pilid 
	    INNER JOIN ffam_vol        c ON a.volid = c.volid 
	    INNER JOIN ffam_programme  d ON d.prgid = c.prgid
		WHERE LENGTH(d.prgshortname)>0;";

    $sqlexe = $PROD->Execute($sql);
    if($sqlexe === false)   {
	    echo "return:900&H:".date('YmdHis', time());
		//trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg(), E_USER_WARNING);
		exit;
    }

    if ($sqlexe->RecordCount() === 0)	{
		echo "return:-1";
		exit;
    }

    $rowpil = $sqlexe->FetchRow();
	// Return : pilot #, flight #, comp # and schedule's shortname
	$monRet = "return:".substr("00".$rowpil['pilbanniere'], -2);
	$monRet.= substr("00".$rowpil['volpos'], -2);
	$monRet.= substr("00".$rowpil['cmpid'], -2);
	$monRet.= $rowpil['prgshortname'];
    echo $monRet;
    exit;
}

$nautovolid      = $_GET['F'];
$nautocmpid      = $_GET['C'];
$nautojugepos    = $_GET['J'];
$nautopildossard = $_GET['D'];

$i=0;
// copy all scores to the $nautnote array
foreach($_GET as $key => $val)	{
    if ($key[0] === "N")    {
	if ($val === "-1")  {
	    $val ="NO";
	}
	$fignbvar = explode("N", $key);
	$nautnote[$i]['figpos'] = $fignbvar[1];
	$nautnote[$i]['note'] = $val;
	$i++;
    }
}
// number of scores
$nbnote = count($nautnote);

// this section forwards the url to the redundant IP address, if configured
if ($redundancyOption & ($nautoption === 'N' || $nautoption === 'U')) {
    // forward the result to redundancy IP address
    // we are checking REDUNDANCY parameter in conf/conf.ffam.php to save sql queries here
    // 
    // create a new cURL resource
    $ch = curl_init();
    // set URL and other appropriate options
    $url = "http://".$redundancyIP."/import-notaumatic.php"
	    . "?OPT=".$nautoption
	    . "&F=".$nautovolid
	    . "&D=".$nautopildossard
	    . "&J=".$nautojugepos
	    . "&C=".$nautocmpid;
    foreach($nautnote as $note)	{
	$url .= "&N".$note['figpos']."=".$note['note'];
    }    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1); // disable signals (curl bug workaround)
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100); // set timeout to 100ms
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // makes sure that no answer is sent as output
    curl_exec($ch);
    curl_close($ch);
}


// recuperation volid, phaseid pour le vol
// select flights that are open for scoring and match the provided flight number
// in multiflightline option there may be several returned with same or different program id (when several comps opened)
if ($nautocmpid !== null)   {
    $sql = "SELECT
		VOL.volpos volpos,
		VOL.volid volid,
		VOL.prgid prgid,
		VOL.phaseid phaseid,
		VOL.clgid clgid,
		CMP.cmpid cmpid
	    FROM
		ffam_competition CMP,
		ffam_vol VOL
	    WHERE VOL.cmpid = CMP.cmpid
	    AND	VOL.cmpid = ".$nautocmpid."
	    AND	VOL.volpos = ".$nautovolid."
	    AND VOL.volstatus = 0";
}   else    {
    $sql = "SELECT
		VOL.volpos volpos,
		VOL.volid volid,
		VOL.prgid prgid,
		VOL.phaseid phaseid,
		VOL.clgid clgid,
		CMP.cmpid cmpid
	    FROM
		ffam_competition CMP,
		ffam_vol VOL
	    WHERE 	VOL.cmpid = CMP.cmpid
	    AND	VOL.volpos = ".$nautovolid."
	    AND 	VOL.volstatus = 0";            
}

$sqlexe = $PROD->Execute($sql);
if($sqlexe === false)   {
    $return = 900;
    echo "return:".$return;
    //trigger_error('Wrong SQL: ' . $sql . ' Error: ' . $conn->ErrorMsg(), E_USER_WARNING);
    exit;
}

$rows_returned = $sqlexe->RecordCount();

if ($rows_returned === 0)    {
    echo "return:100&H:".date('YmdHis', time());
    exit;
}
// match results arrays stores the outcome from each while loop below
// the loop has been modified so it searches for a match across multiple comps
$mr = array();
$i = 0; 
$mr['match'] = FALSE; 
$mr['multimatch'] = FALSE;
    
while($rowvol = $sqlexe->FetchRow())    {
    // see if a match can be found. With multiline/comp option turned on we need to test it for all comp and flight combination.
    // we do rely on the unique judge and pilot numbering across the opened comps
    // if the first run fails, do not exit, store the results and continue searching for other comps

    // used locally in the loop
    $cmpid = $rowvol['cmpid'];
    $phaseid = $rowvol['phaseid'];
    $volid = $rowvol['volid'];
    $prgid = $rowvol['prgid'];

    // used later in the scores updates
    $mr[$i]['cmpid'] = $cmpid;
    $mr[$i]['phaseid'] = $phaseid;
    $mr[$i]['volid'] = $volid;
    $mr[$i]['prgid'] = $prgid;

    $sql = "SELECT
		VOL.volpos  volpos,
		VOL.volid   volid,
		VOL.prgid   prgid,
		VOL.phaseid phaseid,
		VOL.clgid   clgid,
		JUG.jugeid  jugeid
	    FROM
		ffam_college_juge COLJUG,
		ffam_vol  VOL,
		ffam_juge JUG
	    WHERE 	
		VOL.cmpid = ".$cmpid."
	    AND VOL.volid = ".$volid."
	    AND COLJUG.clgid = VOL.clgid
	    AND JUG.cmpid = VOL.cmpid
	    AND JUG.jugeid = COLJUG.jugeid
	    AND JUG.jugepos = ".$nautojugepos."";

    $sqlexejug = $PROD->Execute($sql);

    // test if this row matches the scores provided by NA
    $mr[$i]['error'] = FALSE; $mr[$i]['return'] = 0;

    if($sqlexejug === false)    {
	$mr[$i]['error'] = TRUE;
	$mr[$i]['return'] = 900;
	$mr[$i]['returntext'] = 'Wrong SQL: '.$sql.' Error: '.$conn->ErrorMsg();
    }   else    {
	$rows_returned = $sqlexejug->RecordCount();
	if ($rows_returned === 0)    {
	    $mr[$i]['error'] = TRUE;
	    $mr[$i]['return'] = 101;
	    $mr[$i]['returntext'] = 'Judge not allowed for this flight or do not exist';
	}   else {
	    while($rowjug = $sqlexejug->FetchRow()) {
		//$jugeid = $rowjug['jugeid'];
		$mr[$i]['judgeid'] = $rowjug['jugeid'];
	    }
	    // recuperation pilid pour le pilote
	    $sqlpil = "SELECT
			pilid
		    FROM
			ffam_pilote
		    WHERE
			cmpid = ".$cmpid."
		    AND	pilbanniere = ".$nautopildossard."
		    AND pillocked = 0
		    AND pilflyphase".$phaseid."=1";

	    $sqlexepil = $PROD->Execute($sqlpil);

	    if($sqlexepil === false)    {
			$mr[$i]['error'] = TRUE;
			$mr[$i]['return'] = 901;
			$mr[$i]['returntext'] = 'Wrong SQL: ' . $sqlpil . ' Error: ' . $PROD->ErrorMsg();

	    }   else    {
		$rows_returned = $sqlexepil->RecordCount();
		if ($rows_returned == 0)    {
		    $mr[$i]['error'] = TRUE;
		    $mr[$i]['return'] = 102;
		    $mr[$i]['returntext'] = 'No pilot found with this id';
		} else {
		    while($rowpil = $sqlexepil->FetchRow())
		    {
			$mr[$i]['pilotid'] = $rowpil['pilid'];
		    }
		    if ($mr['match'])   {
			// we just found another match
			$mr['return'] = 105;
			$mr['returntext'] = 'Could not identify a unique matching competition';
			$mr['multimatch'] = TRUE;
		    }
		    $mr['match'] = TRUE;
		}
	    }
	}
    }
    $i++;
}        // end of while for all open flights matching a number
        
// make sure that one match has been found
// if no match - exit with the captured error
// more than 1 match - exit with multiple comp match
// 1 match - continue
if(!$mr['match']) {            
    // not found matching comp at all
//    echo 'return : '.$mr[0]['return'].' '.$mr[0]['returntext']; // this error could be expanded to be more informative, not jyst the first one
    echo 'return:'.$mr[0]['return']."&H:".date('YmdHis', time());	//RP 8/3/2018
	    //echo "return:0&H:".date('YmdHis', time());

    exit();
}   else if($mr['multimatch']) {
    // found multiple comp matches
    //echo 'return:'.$mr['return'].' '.$mr['returntext'];
    echo 'return:'.$mr['return']."&H:".date('YmdHis', time());
    exit();                
}   else    {
    // continue with a unique comp match
    // select the right $i index
    foreach ($mr as $mri)   {
	if (!$mri['error'])     {
	    $cmpid = $mri['cmpid'];
	    $phaseid = $mri['phaseid'];
	    $volid = $mri['volid'];
	    $prgid = $mri['prgid'];
	    $jugeid = $mri['judgeid'];
	    $pilid = $mri['pilotid'];
	}
    }
    $sqlcountfig = "SELECT
			figid
		    FROM
			ffam_figure FIG
		    WHERE 	FIG.prgid = ".$prgid."";

    $sqlcountfigexe = $PROD->Execute($sqlcountfig);
    if ($sqlcountfigexe === false)  {
	$mr[$i]['error'] = TRUE;
	$mr[$i]['return'] = 902;
	$mr[$i]['returntext'] = 'Wrong SQL: ' . $sqlcountfig . ' Error: ' . $PROD->ErrorMsg();
	exit;
    }

    $rows_returned = $sqlcountfigexe->RecordCount();
    if ($rows_returned != $nbnote && $nautoption != "N")    {
	$return = 103;
	echo "return:".$return;
	trigger_error('Number of note is not equal to flight defn', E_USER_WARNING);
	exit;
    }

	//echo 'cmpid : '.$cmpid.'<br>';
	//echo 'jugeid : '.$jugeid.'<br>';
	//echo 'pilid : '.$pilid.'<br>';
	//echo 'phaseid : '.$phaseid.'<br>';
	//echo 'volid : '.$volid.'<br>';

    // loop for each Note sent
    for ($i=0; $i<sizeof($nautnote); $i++)  {
	    // recuperation figid
	    $sqlfig = "SELECT
			figid
		    FROM
			ffam_figure FIG
		    WHERE 	FIG.prgid = ".$prgid."
		    AND	FIG.figposition = ".$nautnote[$i]['figpos']."";

	    $sqlfigexe = $PROD->Execute($sqlfig);
	    if($sqlfigexe === false)    {
    	    $return = 902;
	        echo "return:".$return;
	        trigger_error('Wrong SQL: ' . $sqlfig . ' Error: ' . $PROD->ErrorMsg(), E_USER_WARNING);
	        exit;
	    }

	    while($rowfig = $sqlfigexe->FetchRow())	{
		if ($nautoption == "U") {
		    /*
			    $sqlnotedel = "DELETE FROM
				ffam_note
			    WHERE (
				cmpid = ".$cmpid."
				AND jugeid = ".$jugeid."
				AND pilid = ".$pilid."
				AND phaseid = ".$phaseid."
				AND volid = ".$volid.")";
		    $sqlnotedelete = $PROD->Execute($sqlnotedel);
		    */ 
			$sqlnotemaj = "REPLACE INTO ffam_note (cmpid, jugeid, pilid, phaseid, volid, figid, note)
			    VALUES (
				".$cmpid.",
				".$jugeid.",
				".$pilid.",
				".$phaseid.",
				".$volid.",
				".$rowfig['figid'].",
				\"".$nautnote[$i]['note']."\")";

		        $sqlnoteupdate = $PROD->Execute($sqlnotemaj);
		        if($sqlnoteupdate === false)	{
		            $return = 903;
		            echo "return:".$return;
		            trigger_error('Wrong SQL: ' . $sqlnotemaj . ' Error: ' . $PROD->ErrorMsg(), E_USER_WARNING);
		            exit;
		        };
	        }   elseif ($nautoption == "N") {
        		$sqlnotemaj = "REPLACE INTO ffam_note (cmpid, jugeid, pilid, phaseid, volid, figid, note)
			    VALUES (
			    ".$cmpid.",
			    ".$jugeid.",
			    ".$pilid.",
			    ".$phaseid.",
			    ".$volid.",
			    ".$rowfig['figid'].",
			    \"".$nautnote[$i]['note']."\")";

		        $sqlnoteupdate = $PROD->Execute($sqlnotemaj);
		        if($sqlnoteupdate === false)	{
		            $return = 903;
		            echo "return:".$return;
		            trigger_error('Wrong SQL: ' . $sqlnotemaj . ' Error: ' . $PROD->ErrorMsg(), E_USER_WARNING);
		            exit;
		        };
	        }   elseif ($nautoption == "T")	{
		        echo "return:0"."&H:".date('YmdHis', time());
		        //Tout est OK, on va créer un enregistrement dans ffam_ctrl_juge
		        $sqlnotemaj = "REPLACE INTO ffam_ctrl_juge (judge, pilot)
				VALUES (
				".$nautojugepos.",
				".$nautopildossard.")";

		        $sqlnoteupdate = $PROD->Execute($sqlnotemaj);
		        if($sqlnoteupdate === false)	{
		            trigger_error('Wrong SQL : ' . $sqlnotemaj . ' Error : ' . $PROD->ErrorMsg(), E_USER_WARNING);
		            exit;
		        }
		        exit;
	        }
	    }
	    /*End while */
    }	// end for each Note loop
    
    date_default_timezone_set($timezone);    
    echo "return:0&H:".date('YmdHis', time()); // option 2
    exit;
}
/*
    if ($_GET['debug'] == "on") { echo "<br>elapsed time total ==> ".(microtime(true)-$starttimetotal)."s<br>";};
*/

?>
