<?php

set_time_limit(0);
require_once 'simple_html_dom.php';
require_once 'post_data.php';
require_once 'initialize.php';

$sid = get_SID();
$post['SID'] = $sid;

$nEntries = 0;
$qid = 0;
list($nEntries, $qid) = get_Entries();
print "number of results:" . $nEntries . "\n";
print "qid : $qid \n";


print '******************************************EXTRACT CITATION FOR EACH PAPER**********************************************************' . "\n";


$doc = new DOMDocument();
$doc->load('papers.xml');
$citQuery = curl_init();
$citLinkHeader = 'http://apps.webofknowledge.com/CitedRefList.do?product=UA&sortBy=PY.D&search_mode=CitedRefList&SID=';
curl_setopt($citQuery, CURLOPT_AUTOREFERER, false);
curl_setopt($citQuery, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($citQuery, CURLOPT_RETURNTRANSFER, true);
curl_setopt($citQuery, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");


$writer = new XMLWriter();
$writer->openUri("citation.xml");
$writer->startDocument('1.0', 'UTF-8');
$writer->setIndent(4);
$writer->startElement('citations');
$papers = $doc->getElementsByTagName("paper");
foreach ($papers as $paper) {
    $order = (int) ($paper->getElementsByTagName("order")->item(0)->nodeValue);
    $paperTitle = ($paper->getElementsByTagName("title")->item(0)->nodeValue);
    print "\n" . $order;
    print "paper title: " . $paperTitle . "\n";
    $UT = $paper->getElementsByTagName("UT")->item(0)->nodeValue;
    $citation = array();
    if ($UT) {
        //go to first page of citation list
        //http://apps.webofknowledge.com/summary.do?product=UA&parentProduct=UA&search_mode=CitedRefList&parentQid=1&qid=2&SID=V1J3pefKLcEm@cdjm62&page=2
        $nextPageURLHeader = 'http://apps.webofknowledge.com/summary.do?product=UA&parentProduct=UA&search_mode=CitedRefList&';
        $queryURL = $citLinkHeader . $sid . '&UT=' . $UT;
        curl_setopt($citQuery, CURLOPT_URL, $queryURL);
        $page = curl_exec($citQuery);
        $html = str_get_html($page);
        $nPages = (int) ($html->find('span[id="pageCount.top"]', 0)->plaintext);
        $lnks = $html->find('table[id=topNavBar] tr td a');
        $button = $lnks[1]->href;
        parse_str($button, $vars);
        if (isset($vars['qid'])) {
            $qid = (int) ($vars['qid']);
        }
        //citation List Pages
        for ($i = 0; $i < $nPages; $i++) {
            print "page " . ($i + 1) . ", ";
            foreach ($html->find('tr[id^=RECORD_]') as $record) {
                $tmp = get_Record($record);
                if (isset($tmp)) {
                    array_push($citation, $tmp);
                }
            }
            if ($i != $nPages - 1) {
                $queryURL = $nextPageURLHeader . '&SID=' . $sid . '&qid=' . $qid . '&page=' . ($i + 2);
                curl_setopt($citQuery, CURLOPT_URL, $queryURL);
                $page = curl_exec($citQuery);
                $html = str_get_html($page);
            }
        }
        //done write data to XML
        write_XML($writer, $citation, $order);
    }
}
$writer->endElement();
return;
?>