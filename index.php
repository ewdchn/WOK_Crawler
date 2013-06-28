<?php

require_once 'simple_html_dom.php';
require_once 'post_data.php';
require_once 'articleEntryExtract.php';
set_time_limit(0);

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, "www.webofknowledge.com");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_AUTOREFERER, false);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
$main_page = curl_exec($ch);
//print "main" + "\n";
//print $main_page;
//get SID
preg_match('/^Set-Cookie:\s*([^;]*)/mi', $main_page, $m);
parse_str($m[1], $cookies);
$sid = $cookies['SID'];
$sid = str_replace('"', '', $sid);
$post['SID'] = $sid;
print 'SID: ' . $sid . "\n";


curl_close($ch);
$nEntries = 0;




print '************************performing query****************************' . "\r\n";




//$referer_url='http://apps.webofknowledge.com/UA_GeneralSearch_input.do;jsessionid='.$jsid.'?product=UA&search_mode=GeneralSearch&SID='.$sid.'&preferencesSaved=';
//print $referer_url."\n";
$result = "";
while (!$nEntries) {
    print $result;
    $search = curl_init();
    curl_setopt($search, CURLOPT_HEADER, true);
    curl_setopt($search, CURLOPT_URL, "apps.webofknowledge.com/UA_GeneralSearch.do");
    curl_setopt($search, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($search, CURLOPT_AUTOREFERER, false);
    curl_setopt($search, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($search, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($search, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
    curl_setopt($search, CURLOPT_POST, true);
    curl_setopt($search, CURLOPT_POSTFIELDS, http_build_query($post));
    $result = curl_exec($search);
    preg_match('/handle_nav_final_counts\([^)]+\)/', $result, $m);
    preg_match('/\'[^,]+\'/', $m[0], $n);
    $nEntries = intval(str_replace('\'', '', $n[0]));
    $last_url = curl_getinfo($search, CURLINFO_EFFECTIVE_URL);
    $url_comp = parse_url($last_url);
    parse_str($url_comp['query'], $output);
    $qid = $output['qid'];
    print "qid: " . $qid . "\n";
    print "\n" . "*************************results***************************" . "\n";
    $html = str_get_html($result);
    $entries_str = $html->find('span[id=hitCount.bottom]', 0)->innertext;
    str_replace(" ", "", $entries_str);
//print "\n".$entries_str."\n";
    print "entries string" . "\n" . $entries_str . "\n";
    $nEntries = (int) ($entries_str);
    print "number of results:" . $nEntries . "\n";

    curl_close($search);
}
//sleep(2);
//print $result;
//*******************************Article URLs**********************************//
//curl_setopt($articalQuery, CURLOPT_HEADER, true);
$articleURL = array();
$articleEntry = array();
//Request URL:http://apps.webofknowledge.com/full_record.do?product=UA&search_mode=GeneralSearch&qid=2&SID=P2b38BICoOj4c16aaNC&page=1&doc=1









print "***************************************************extracing Each Entry**********************************************************\n";









for ($i = 0; $i < $nEntries; $i++) {
    $html = null;
    print "\n" . "Processing Entry: " . $i + 1 . "\n";
    $articleQuery = curl_init();
    curl_setopt($articleQuery, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($articleQuery, CURLOPT_AUTOREFERER, false);
    curl_setopt($articleQuery, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($articleQuery, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($articleQuery, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
    $articleURL[$i] = "http://apps.webofknowledge.com/full_record.do?product=UA&search_mode=GeneralSearch&qid=" . $qid . "&SID=" . $sid . "&doc=" . ($i + 1);
    curl_setopt($articleQuery, CURLOPT_URL, $articleURL[$i]);
    $content = curl_exec($articleQuery);
    while (!($tmpAtcEntry = articleEntryExtract($content))) {
        echo ".";
        $content = curl_exec($articleQuery);
    }
    $articleEntry[$i] = $tmpAtcEntry;
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
    XML_add($doc, $p, "DOI", $paper['DOI']);
    XML_add($doc, $p, "UT", $paper['UT']);
    XML_add($doc, $p, 'order', $paper['content']);
    foreach ($paper['authors'] as $authorName) {
        XML_add($doc, $p, "author", $authorName);
    }
}
$doc->save("papers.xml");
return;
print '********************************************extraction Lvl 1 Citation********************************************' . "\n";
for ($i = 0; $i < $nEntries; $i++) {
    $citQuery = curl_init();
    $citLinkHeader = 'http://apps.webofknowledge.com/CitedRefList.do?product=UA&sortBy=PY.D&search_mode=CitedRefList&SID=';
    curl_setopt($citQuery, CURLOPT_AUTOREFERER, false);
    curl_setopt($citQuery, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($citQuery, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($citQuery, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
    $queryURL = $citLinkHeader . $SID . '&UT=' . $articleEntry[$i]['UT'];
    //curl_setopt($articleQuery, CURLOPT_URL, $articleEntry[$i]['citLink']);
    $result = curl_exec($citQuery);

    $html = str_get_html($result);
    $nextPageLnk = $html->find();
    $nPages = (int) ($html->find('span[id="pageCount.top"]', 0)->plaintext);
    for ($j = 1; $j <= $nPages; $j++) {
        curl_setopt($citQuery, CURLOPT_URL, $citLinkHeader . '&page=' . $j);
    }
}
?>