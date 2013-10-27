<?php
require_once 'Entry.php';
$id = 0;
$L0_Order_ID_Map = array(); //l0order --> (gID)
$L1_Order_ID_Map = array();
$id_Map = array();
$id_Map['UT'] = array();
$id_Map['DOI'] = array();
$id_Map['REFID'] = array();
$id_Entry_Map = array();
$processedArr = array();
$categoriesArr = array();
$duplicateCnt = 0;
$FLAGARR = array('UT' => 1, 'REFID' => 2, 'DOI' => 3);

function load_journals()
{
    global $categoriesArr;
    echo "loading journals\n";
    $host = '140.112.180.153';
    $con = mysql_connect($host, "ewdchn", "fj1-20") or die("could ont connect: " . mysql_error());
    mysql_select_db("WOK", $con);
    $sql_query = "SELECT * FROM journals";
    $result = mysql_query($sql_query, $con);
    while ($row = mysql_fetch_array($result)) {
        $categoriesArr[strtolower((string)$row['title'])] = $row['categories'];
    }
}
function myserializeAlt($arr){
    $flag = true;
    $output = '';
    foreach ($arr as $value=>$key) {
        $flag ? $output .= $value : $output .= "|" . $value;
        $flag = false;
    }
    return $output;
}

function myserialize($arr)
{
    $flag = true;
    $output = '';
    if(count($arr)==0)return $output;
    foreach ($arr as $value) {
        $flag ? $output .= $value : $output .= "|" . $value;
        $flag = false;
    }
    return $output;
}
//return an global entry ID
function insert_paper($p)
{
    global $duplicateCnt;
    global $id_Entry_Map;
    global $id;
    global $id_Map;
    global $FLAGARR;
    $tmpID = false;
    $flag = false;
    $entry = new Entry($p);
    $tmp = $entry->getAttr();
    //check duplicate
    foreach ($FLAGARR as $flagKey => $flagValue)
        //check for UT REFID DOI
        if (isset($tmp[$flagKey]) && isset($id_Map[$flagKey][$tmp[$flagKey]])) {
            $flag = $flagValue;
            $tmpID = $id_Map[$flagKey][$tmp[$flagKey]]->getID();
            echo substr($flagKey, 0, 1);
            break;
        }
    //no match, new entry
    if (!$flag) {
        $entry->setID($id);
        $id_Entry_Map[$id] = $entry;
        $tmpID = $id;
        $id++;
        foreach ($FLAGARR as $flagKey => $flagValue)
            //add entry to idMaps
            if (isset($tmp[$flagKey]))
                $id_Map[$flagKey][$tmp[$flagKey]] = $entry;
        return $tmpID;
    } //match found
    else {
        $duplicateCnt++;
        unset($entry);
//        echo $tmpID;
//        print_r( $id_Entry_Map[$tmpID]->getAttr());
        return $tmpID;
    }
}


function layer0_process($_l0doc)
{
    global $L0_Order_ID_Map;
    foreach ($_l0doc->paper as $l0paper) {
        $L0_Order_ID_Map[(int)$l0paper->order] = insert_paper($l0paper);
    }
    echo "l0 completete\n";
}
function layer1_process($_l1doc)
{
    echo "process l1...";
    global $id_Entry_Map;
    global $processedArr;
    global $L0_Order_ID_Map;
    global $L1_Order_ID_Map;
    foreach ($_l1doc->paper as $l0paper) { //FOREACH L0 papers
        $l0Paper_gID = $L0_Order_ID_Map[(int)($l0paper->order)];
        $L0Entry = $id_Entry_Map[$l0Paper_gID];

        foreach ($l0paper->citedPaper as $l1Paper) {
            $l1Paper_gID = insert_paper($l1Paper);
            $L0Entry->addCitation($l0Paper_gID);
            $L1_Order_ID_Map[(int)($l1Paper->order)] = $l1Paper_gID;
        }
        $processedArr[$l0Paper_gID] = true;
    }
    echo "complete. \n";
}

function layer2_process($_l2doc)
{
    echo "process l2...";
    global $processedArr;
    global $id_Entry_Map;
    global $L1_Order_ID_Map;
    foreach ($_l2doc->paper as $l1paper) {
        $l1Paper_gID = $L1_Order_ID_Map[(int)($l1paper->order)];
        $L1Entry = $id_Entry_Map[$l1Paper_gID];
        foreach ($l1paper->citedPaper as $l2Paper) {
            $l2Paper_gID = insert_paper($l2Paper);
            $L1Entry->addCitation($l2Paper_gID);
        }
        $processedArr[$l1Paper_gID] = true;
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
if (basename($argv[0]) !== basename(__FILE__))   {return ;}
layer0_process($l0doc);
layer1_process($l1doc);
layer2_process($l2doc);

echo "adding to database";

$host = '140.112.180.153';
$con = mysql_connect($host, "ewdchn", "fj1-20") or die('Could not connect: ' . mysql_error());
mysql_select_db("WOK", $con);
mysql_query("TRUNCATE all_papers", $con);
foreach ($id_Entry_Map as $id=>$entry) {
        echo '.';
        if (!mysql_query($entry->genSQL(), $con))
            die('Error: ' . mysql_error());
}
unset($l0doc);
unset($l1doc);
unset($l2doc);
echo "done";
return 0;
?>