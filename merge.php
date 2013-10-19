<?php

$id = 0;
$layer0 = array(); //l0order --> (gID, title)
$layer1 = array();
$processed = array();
$categories = array();
$duplicateCount = 0;
$categorized = 0;
$articles = array();

function processAuthor(&$_author,$key) {
    if (count(preg_split("/[,\s]+/", $_author, 2)) < 2){
        $_author = strtoupper($_author);
        return strtoupper($_author);
    }
    list($firstName, $lastName) = preg_split("/[,\s]+/", $_author, 2);
    $lastName = trim($lastName);
    $firstName = trim($firstName) . ",";
    $lastNameToken = preg_split('/[\s.]+/', $lastName);
    if (count($lastNameToken) == 1) {
        if (preg_match("/[A-Z]+/", $lastNameToken[0], $tok))
            $firstName.=$tok[0];
    } else {
        foreach ($lastNameToken as $tok){
            empty($tok)?:$firstName.=$tok[0];
//            $firstName.=substr($tok, 0, 1);
        }
    }
    $_author=strtoupper($firstName);
    return strtoupper($firstName);
}

function authorMatch($_autArr1, $_autArr2) {
    $ctr = 0;
    array_walk($_autArr1, "processAuthor");
    array_walk($_autArr2, "processAuthor");
    foreach ($_autArr1 as $auth1) {
        foreach ($_autArr2 as $auth2) {
            if ($auth1 == $auth2) {
                $ctr++;
                break;
            }
        }
    }
    if ($ctr >= min(array(count($_autArr1), count($_autArr2))))
        return true;
    else
        return false;
}

function load_journals() {
    global $categories;
    echo "loading journals\n";
    $host = '140.112.180.153';
    $con = mysql_connect($host, "ewdchn", "fj1-20") or die("could ont connect: " . mysql_error());
    mysql_select_db("WOK", $con);
    $sql_query = "SELECT * FROM journals";
    $result = mysql_query($sql_query, $con);
    while ($row = mysql_fetch_array($result)) {
        $categories[(string) $row['title']] = $row['categories'];
    }
}

function paperMerge(&$entryA, &$entryB) {
    foreach (array('DOI', 'UT', 'citation', 'source', 'categories') as $key) {
        if (!isset($entryA[$key]) && isset($entryB[$key])) {
            $entryA[$key] = $entryB[$key];
        }
    }
}

function insert_paper(&$a, $p) {
    global $duplicateCount;
    global $categories;
    global $id;
    global $categorized;
    $tmp = array();
    $tmp['authors'] = array();

    if (!$p->title) {
        echo "no title, cannot proceed\n";
        return;
    }
    foreach ($p as $key => $value) {
        if ($key == 'author') {                      //author
            $tmp['authors'][] = trim($value, ' ');
        } else if ($key == "Source") {              //Source
            $srcStr = trim(strtoupper((string) $value));
            $tmp['source'] = $srcStr;
            if (isset($categories[$srcStr]) && $tmp['categories'] = $categories[$srcStr])
                $categorized++;
        } else {                                    //other values
            if (($v = (trim($value))))
                $tmp[(string) $key] = strtolower($v);
        }
    }
    //check duplicate
    if (isset($a[$tmp['title']])) {
        $title_Entry = &$a[$tmp['title']];
        $flag = false;
        foreach ($title_Entry as &$oa) { // FOR EACH PAPER WITH SAME TITLE
            if (isset($oa['DOI']) && isset($tmp['DOI']) && ($oa['DOI'] == $tmp['DOI'])) {
                $flag = true;
            } else if (isset($oa['UT']) && isset($tmp['UT']) && ($oa['UT'] == $tmp['UT'])) {
                $flag = true;
            } else {
                $flag = authorMatch($oa['authors'], $tmp['authors']);
            }
            //IF DUPLICATE CONFIRMED RETURN
            if ($flag) {
                paperMerge($oa, $tmp);
                $duplicateCount++;
                return $oa['id'];
            }
        }
    }
    $tmp['id'] = $id;
    $id++;
    $a[$tmp['title']][$tmp['id']] = $tmp;
    return $tmp['id'];
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

function layer0_process($l0doc) {
    global $articles;
    global $layer0;
    foreach ($l0doc->paper as $l0paper) {
        $layer0[(int) $l0paper->order] = array((int) insert_paper($articles, $l0paper), (string) strtolower($l0paper->title));
    }
    echo "l0 completete\n";
}

function layer1_process($l1doc) {
    echo "process l1...";
    global $articles;
    global $processed;
    global $layer0;
    global $layer1;
    foreach ($l1doc->paper as $l0paper) {  //FOREACH L0 papers
        list($l0Paper_gID, $l0Paper_gTitle) = $layer0[(int) ($l0paper->order)];

        $articles[$l0Paper_gTitle][$l0Paper_gID]['citations'] = array();
        $citationArray = &$articles[$l0Paper_gTitle][$l0Paper_gID]['citations'];

        foreach ($l0paper->citedPaper as $l1Paper) {
            $l1Paper_gID = insert_paper($articles, $l1Paper);
            $citationArray[] = $l1Paper_gID;
            $layer1[(int) ($l1Paper->order)] = array($l1Paper_gID, (string) strtolower(($l1Paper->title)));
        }

        $processed[$l0Paper_gID] = true;
    }

    echo "complete. \n";
}

function layer2_process($l2doc) {
    echo "process l2...";
    global $articles;
    global $processed;
    global $layer1;
    foreach ($l2doc->paper as $l1paper) {
        list($l1Paper_gID, $l1Paper_gTitle) = $layer1[(int) ($l1paper->order)];
        echo (int) ($l1paper->order) . "\n";
        if (isset($processed[$l1Paper_gID])) {
            echo"processed,passing\n";
            continue;
        }
        $articles[$l1Paper_gTitle][$l1Paper_gID]['citations'] = array();
        $citationArray = &$articles[$l1Paper_gTitle][$l1Paper_gID]['citations'];
        foreach ($l1paper->citedPaper as $l2Paper) {
            $l2Paper_gID = insert_paper($articles, $l2Paper);
            $citationArray[] = $l2Paper_gID;
        }
        $processed[$l1Paper_gID] = true;
    }
    echo "complete\n";
}

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
load_journals();

$l0doc = simplexml_load_file('level0.xml');
$l1doc = simplexml_load_file('level1.xml');
$l2doc = simplexml_load_file('level2.xml');
layer0_process($l0doc);
layer1_process($l1doc);
$t = microtime(true);
layer2_process($l2doc);
$t = microtime(true) - $t;


echo "adding to database";


$host = '140.112.180.153';
$con = mysql_connect($host, "ewdchn", "fj1-20") or die('Could not connect: ' . mysql_error());
mysql_select_db("WOK", $con);
$sql = "TRUNCATE all_papers";
mysql_query($sql, $con);

foreach ($articles as $title => $paperArray) {
    foreach ($paperArray as $paper) {
        echo $paper['title'] . "\n";
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
            echo ".";
    }
}
print $id . " Entries\n";
print "duplicates: $duplicateCount\n";
print "categorized: $categorized\n";
print "Time in L2: $t\n";
return;
?>