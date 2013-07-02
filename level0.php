<?php

require_once 'simple_html_dom.php';
require_once 'post_data.php';
require_once 'articleEntryExtract.php';
require_once 'initialize.php';

set_time_limit(0);
$sid = get_SID();
$post['SID'] = $sid;

$nEntries = 0;
$qid = 0;
list($nEntries, $qid) = get_Entries();
print "number of results:" . $nEntries . "\n";
print "qid : $qid \n";



print '************************performing query****************************' . "\r\n";



$articleURL = array();
$articleEntry = array();


print "***************************************************extracing Each Entry**********************************************************\n";



for ($i = 1; $i <= $nEntries; $i++) {
    echo "\n" . "Processing Entry: " . $i . "\n";
    $articleQuery = curl_init();
    curl_setopt($articleQuery, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($articleQuery, CURLOPT_AUTOREFERER, false);
    curl_setopt($articleQuery, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($articleQuery, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($articleQuery, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
    $articleURL[$i] = "http://apps.webofknowledge.com/full_record.do?product=UA&search_mode=GeneralSearch&qid=" . $qid . "&SID=" . $sid . "&doc=" . ($i);
    curl_setopt($articleQuery, CURLOPT_URL, $articleURL[$i]);
    $content = curl_exec($articleQuery);
    while (!($tmpAtcEntry = articleEntryExtract($content))) {
        echo ".";
        $content = curl_exec($articleQuery);
    }
    $articleEntry[$i] = $tmpAtcEntry;
    $articleEntry[$i]['order'] = $i;
    curl_close($articleQuery);
    print"\n";
    print_r($articleEntry[$i]);
}

print "************************************dumping XML***************************\n";
//dumpXML
$doc = new DOMDocument();
$doc->formatOutput = true;
$r = $doc->createElement("papers");
$doc->appendChild($r);
foreach ($articleEntry as $paper) {
    $p = XML_add($doc, $r, "paper");
    XML_add($doc, $p, "title", $paper['title']);
    if (isset($paper['DOI']))
        XML_add($doc, $p, "DOI", $paper['DOI']);
    if (isset($paper['Source']))
        XML_add($doc, $p, "Source", $paper['Source']);
    if (isset($paper['UT']))
        XML_add($doc, $p, "UT", $paper['UT']);
    XML_add($doc, $p, 'order', $paper['order']);
    foreach ($paper['authors'] as $authorName) {
        XML_add($doc, $p, "author", $authorName);
    }
    
}
$doc->save("level0.xml");
return;
?>