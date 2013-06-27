<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$id = 0;
$sorted_articles=array();
$layer0 = array();
function insert_paper(&$a, $p) {
    global $id;
    global $sorted_articles;
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
        $tmp[$key] = trim($value, ' ');
    }
    /*     * *** check for replication**** */
    if (!isset($a[$tmp['title']])) {
        $tmp['id'] = $id;
        $a[$tmp['title']] = array();
        $a[$tmp['title']][] = $tmp;
        $id++;
        return;
    } else {
        foreach ($a[$tmp['title']] as $oa) {
            if ($oa['DOI'] && $tmp['DOI'] && ($oa['DOI'] == $tmp['DOI'])) {
                return;
            } else if ($oa['UT'] && $tmp['UT'] && ($oa['UT'] == $tmp['UT'])) {
                return;
            } else {
                $ctr = 0;
                foreach ($oa['authors'] as $oat) {
                    foreach ($tmp['authors'] as $at) {
                        if ($oat == $at)
                            $ctr++;
                    }
                }
                if ($ctr >= min(array(count($oa['authors']), count($tmp['authors'])))) {
                    return;
                }
            }

            // $a[$tmp['title']][] = $tmp;
        }
        $tmp['id'] = $id;
        $a[$tmp['title']][] = $tmp;
        $id++;
    }
}

$doc = simplexml_load_file('papers.xml');
$articles = array();
$layer1 = array();
foreach ($doc as $paper) {
    insert_paper($articles, $paper);
}
$layer0 = array();
foreach($articles as $key=>$value){
    foreach($value as $paper){
        $layer0[(int)$paper['order']] = $paper;
    }
}
 print_r($layer0);     
//print_r($articles);
print $id . " Entries\n";
?>
