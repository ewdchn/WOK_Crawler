<?php

require_once 'simple_html_dom.php';
require_once 'post_data.php';
require_once 'articleEntryExtract.php';
require_once 'initialize.php';

set_time_limit(0);
$sid = get_session_SID();
$post['SID'] = $sid;

$nEntries = 0;
$qid = 0;
list($nEntries, $qid) = get_Entries();
print "number of results:" . $nEntries . "\n";
print "qid : $qid \n";

/*
 *
 * Initiate Search, grab the full_record Page of level 0 entries and dump the info(without cited entry details)
 *
 *
 */

print '************************performing query****************************' . "\r\n";


$articleURL = array();
$articleEntry = array();


print "***************************************************extracing Each Entry Page**********************************************************\n";


for ($i = 1; $i <= $nEntries; $i++) {
    echo "\n" . "Processing Entry: " . $i . "\n";
    $articleQuery = curl_init();
    curl_setopt_array($articleQuery, $curl_options);
    curl_setopt($articleQuery, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    $articleURL[$i] = "http://apps.webofknowledge.com/full_record.do?product=UA&search_mode=GeneralSearch&qid=" . $qid . "&SID=" . $sid . "&doc=" . ($i);
    curl_setopt($articleQuery, CURLOPT_URL, $articleURL[$i]);
    $content = curl_exec($articleQuery);
    while (!($tmpAtcEntry = Arr_extractFullRecordPage($content))) {
        echo ".";
        $content = curl_exec($articleQuery);
    }
    $articleEntry[$i] = $tmpAtcEntry;
    $articleEntry[$i]['order'] = $i;
    curl_close($articleQuery);
    print"\n";
    if (isset($articleEntry[$i]['REFID']))
        print_r($articleEntry[$i]);
}

print "************************************dumping XML***************************\n";
//dumpXML
$l0doc = new DOMDocument();
$l0doc->formatOutput = true;
$r = $l0doc->createElement("papers");
$l0doc->appendChild($r);


foreach ($articleEntry as $paper) {
    $p = XML_add($l0doc, $r, "paper");
    XML_add($l0doc, $p, "title", $paper['title']);
    foreach (array('order', 'DOI', 'Source', 'UT', 'REFID') as $key)
        if (isset($paper[$key])) XML_add($l0doc, $p, $key, $paper[$key]);
        else if ($key == 'order') echo "ERROR: no order";
    foreach ($paper['authors'] as $authorName)
        XML_add($l0doc, $p, "author", $authorName);
}
$l0doc->save("level0.xml");
return;
?>