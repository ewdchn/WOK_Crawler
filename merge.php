<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$id = 0;
$sorted_articles = array();
$layer0 = array();
$layer1 = array();

function insert_paper(&$a, $p) {
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
        if ($key == 'author') {
            $tmp['authors'][] = trim($value, ' ');
            continue;
        }
        if (($v = trim($value, ' '))) {
            $tmp[(string) $key] = $v;
        }
    }
    if (!isset($a[$tmp['title']])) {
        $tmp['id'] = $id;
        $id++;
        $a[$tmp['title']][$order] = $tmp;
        return $tmp['id'];
    } else {
        foreach ($a[$tmp['title']] as $oa) {
            if (isset($oa['DOI']) && isset($tmp['DOI']) && ($oa['DOI'] == $tmp['DOI'])) {
                return $oa['id'];
            } else if (isset($oa['UT']) && isset($tmp['UT']) && ($oa['UT'] == $tmp['UT'])) {
                return $oa['id'];
            } else {
                $ctr = 0;
                foreach ($oa['authors'] as $oat) {
                    foreach ($tmp['authors'] as $at) {
                        if ($oat == $at)
                            $ctr++;
                    }
                }
                if ($ctr >= min(array(count($oa['authors']), count($tmp['authors'])))) {
                    echo "replicate";
                    return $oa['id'];
                }
            }
            // $a[$tmp['title']][] = $tmp;
        }
        $tmp['id'] = $id;
        $id++;
        $a[$tmp['title']][$order] = $tmp;
        return $tmp['id'];
    }
}

function copy_ref(&$layer0, &$paper) {
    $layer0[(int) $paper['order']] = $paper;
}
function myserialize($arr){
    $flag = true;
    $output = '';
    foreach($arr as $value){
        
        $flag?$output.=$value:$output.="|".$value;
        $flag=false;
    }
    return $output;
}
$doc = simplexml_load_file('papers.xml');
$citDoc = simplexml_load_file('citation.xml');
$articles = array();
$layer1 = array();
foreach ($doc as $paper) {
    echo (int) insert_paper($articles, $paper);
    echo "\n";
}
//print_r($articles);

foreach ($articles as $title => $paperArray) {
    foreach ($paperArray as $paper) {
        copy_ref($layer0, $paper);
    }
}
//print_r($layer0);
foreach ($layer0 as $l0paper) {
    print $l0paper['id'] . ": " . $l0paper['title'] . "\n";
}

/* * **********citation ********* */
foreach ($citDoc as $paper) {
    $order = (int) ($paper->order);
    //print "\norder: " . $order . "\n";
    foreach ($paper->citedPaper as $citedPaper) {
        $citedPaperID = insert_paper($articles, $citedPaper);
        //print " " . $citedPaperID . " ";
        $articles[$layer0[$order]['title']][$order]['citations'][] = $citedPaperID;
        //$layer0[$order]['citation'][] = $citedPaperID;
    }
}
//print_r($articles['The Use and Abuse of the Digital Humanities in the History of Ideas: How to Study the Encyclopedie']);

$con = mysql_connect("localhost", "ewdchn", "fj1-20");
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db("WOK", $con);
foreach ($articles as $title => $paperArray) {
    foreach ($paperArray as $paper) {
        $tmpttl= addslashes($paper['title']);
        $tmpaut = addslashes(myserialize($paper['authors']));
        $tmpDOI = isset($paper['DOI']) ? addslashes($paper['DOI']) : "";
        $tmpUT = isset($paper['UT']) ? addslashes($paper['UT']) : "";
        $tmpcit = isset($paper['citations']) ? addslashes(myserialize($paper['citations'])) : "";
        $sql = "INSERT INTO level1 (id,title,author,DOI,UT,citation)VALUES
            ($paper[id],'$tmpttl','$tmpaut','$tmpDOI','$tmpUT','$tmpcit')";
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
