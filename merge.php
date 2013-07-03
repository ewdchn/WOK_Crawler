<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$id = 0;
$sorted_articles = array();
$layer0 = array();
$layer1 = array();
$processed = array();
$categories = array();
$duplicateCount = 0;

function load_journals() {
    global $categories;
    echo "loading journals";
    $host = '140.112.180.153';
    $con = mysql_connect($host, "ewdchn", "fj1-20") or die("could ont connect: " . mysql_error());
    mysql_select_db("WOK", $con);
    $sql_query = "SELECT * FROM journals";
    $result = mysql_query($sql_query, $con);
    while ($row = mysql_fetch_array($result)) {
        $categories[(string) $row['title']] = $row['categories'];
    }
}

function insert_paper(&$a, $p) {
    global $duplicateCount;
    global $categories;
    global $id;
    global $sorted_articles;
    $order = (int) $p->order;
    $tmp = array();
    $tmp['authors'] = array();


    if (!$p->title) {
        echo "no title, cannot proceed\n";
        return;
    }

    foreach ($p as $key => $value) {
        //author
        if ($key == 'author') {
            $tmp['authors'][] = trim($value, ' ');
            continue;
        } else if ($key == "Source") {
            if (!empty($categories[(string) $value])) {
                $tmp['categories'] = $categories[(string) $value];
            }
            $tmp['source'] = (string) $value;
        } else {
            //other values
            if (($v = trim($value, ' '))) { //not empty
                $tmp[(string) $key] = $v;
            }
        }
    }
    //check duplicate
    if (!isset($a[$tmp['title']])) {
        $tmp['id'] = $id;
        $id++;
        $a[$tmp['title']][$tmp['id']] = $tmp;
        return $tmp['id'];
    } else {
        $flag = false;
        foreach ($a[$tmp['title']] as $oa) { // FOR EACH PAPER WITH SAME TITLE
            if (isset($oa['DOI']) && isset($tmp['DOI']) && ($oa['DOI'] == $tmp['DOI'])) {  //CHECK DOI
                $flag = true;
            } else if (isset($oa['UT']) && isset($tmp['UT']) && ($oa['UT'] == $tmp['UT'])) {//CHECK UT
                $flag = true;
                //CHECK AUTHORS    
            } else {
                $ctr = 0;
                foreach ($oa['authors'] as $oat) {
                    foreach ($tmp['authors'] as $at) {
                        if ($oat == $at)
                            $ctr++;
                    }
                }
                if ($ctr >= min(array(count($oa['authors']), count($tmp['authors'])))) {
                    $flag = true;
                }
            }
            //IF DUPLICATE CONFIRMED RETURN
            if ($flag) {
                $duplicateCount++;
//                echo $oa['title']." ".$tmp['title']."\n";
//                echo myserialize($oa['authors'])." ".myserialize($tmp['authors'])."\n";
                return $oa['id'];
            }
        }
        $tmp['id'] = $id;
        $id++;
        $a[$tmp['title']][$tmp['id']] = $tmp;
        return $tmp['id'];
    }
}

function myserialize($arr) {
    $flag = true;
    $output = '';
    foreach ($arr as $value) {
        $flag ? $output.=$value : $output.="|" . $value;
        $flag = false;
    }
    return $output;
}

//****************************START HERE*****************************//

load_journals();

$l0doc = simplexml_load_file('level0.xml');
$l1doc = simplexml_load_file('level1.xml');
$l2doc = simplexml_load_file('level2.xml');
$articles = array();

/* * *****************
 *          LOGIC:
 *              FOEACH LEVEL0  INSERT -->> (create layer0_order -> globalID)
 *              level1.xml
 *              FOREACh LEVEL0 AS L0
 *                FOREACH L0 [ CITATION ] AS L1
 *                  MERGE L1/GET L1 globalID
 *                  L0[ CITATION_ID_LIST ] .= L1 globalID
 *                  map L1 order -->> globalID
 *                MARK L0 globalID as processed
 *              level2.xml
 *              FOREACH LAYER1 PAPER AS L1
 *                  IF GLOBAL[L1-->>globalID] is NOT processed
 *                       FOREACH LAYER1 [ CITATION ] AS L2
 *                            INSERT L2
 *                  MARK L1 as processed
 */


//*********************LAYER 0 ******************/
foreach ($l0doc->paper as $paper) {
    //order --> globalID and paper title
    $layer0[(int) $paper->order] = array((int) insert_paper($articles, $paper), (string) $paper->title);
}
/* * ******************LAYER 1******************* */
foreach ($l1doc->paper as $l0paper) {  //FOREACH L0 papers
    list($l0Paper_gID, $l0Paper_gTitle) = $layer0[(int) ($paper->order)];
    //extract each citation for this L0 paper
    foreach ($l0paper->citedPaper as $citedPaper) {
        $l1Paper_gID = insert_paper($articles, $citedPaper);
        $articles[$l0Paper_gTitle][$l0Paper_gID]['citations'][] = $l1Paper_gID;
        $layer1[(int) ($citedPaper->order)] = array($l1Paper_gID, (string) ($citedPaper->title));
    }
    $processed[$l0Paper_gID] = true;
}
echo "level1 complete";
echo "duplicate counter: $duplicateCount\n";
//
/* * *******************LAYER 2******************** */
foreach ($l2doc->paper as $l1paper) {
    list($l1Paper_gID, $l1Paper_gTitle) = $layer1[(int) ($l1paper->order)];
    echo (int) ($l1paper->order)."\n";
    if (isset($processed[$l1Paper_gID]))
        continue;
    foreach ($l1paper->citedPaper as $citedPaper) {
        $l2Paper_gID = insert_paper($articles, $citedPaper);
        $articles[$l1Paper_gTitle][$l1Paper_gID]['citations'][] = $l2Paper_gID;
        //$layer2[(int) ($citedPaper->order)] = array($l2PaperID, (string) ($citedPaper->title));
    }
    $processed[$l1Paper_gID] = true;
}
echo "level 2 coplete\n";
//print_r($articles['The Use and Abuse of the Digital Humanities in the History of Ideas: How to Study the Encyclopedie']);
$host = '140.112.180.153';
$con = mysql_connect($host, "ewdchn", "fj1-20");
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db("WOK", $con);
foreach ($articles as $title => $paperArray) {
    foreach ($paperArray as $paper) {
        $tmpttl = addslashes($paper['title']);
        $tmpaut = addslashes(myserialize($paper['authors']));
        $tmpDOI = isset($paper['DOI']) ? addslashes($paper['DOI']) : "";
        $tmpUT = isset($paper['UT']) ? addslashes($paper['UT']) : "";
        $tmpSrc = isset($paper['source']) ? addslashes($paper['source']) : "";
        $tmpCat = isset($paper['categories']) ? addslashes($paper['categories']) : "";
        $tmpcit = isset($paper['citations']) ? addslashes(myserialize($paper['citations'])) : "";
        $sql = "INSERT INTO all_papers (id,title,author,DOI,UT,source,categories,citation)VALUES
            ($paper[id],'$tmpttl','$tmpaut','$tmpDOI','$tmpUT','$tmpSrc','$tmpCat','$tmpcit')";
        //  echo $sql;
        if (!mysql_query($sql, $con)) {
            die('Error: ' . mysql_error());
        }
        else
            echo "adding to database";
    }
}
print $id . " Entries\n";
?>