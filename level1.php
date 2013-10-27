<?php
set_time_limit(0);
require_once 'simple_html_dom.php';
require_once 'post_data.php';
require_once 'initialize.php';

$sid = get_session_SID();
$post['SID'] = $sid;

$nEntries = 0;
$qid = 0;
list($nEntries, $qid) = get_Entries();
print "number of results:" . $nEntries . "\n";
print "qid : $qid \n";


print '******************************************EXTRACT CITATION FOR EACH PAPER**********************************************************' . "\n";


$l0doc = new DOMDocument();
$l0doc->load('level0.xml');
$citLinkHeader = 'http://apps.webofknowledge.com/CitedRefList.do?product=UA&sortBy=PY.D&search_mode=CitedRefList&SID=';

$citQuery = curl_init();
curl_setopt_array($citQuery, $curl_options);

$writer = new XMLWriter();
$writer->openUri("level1.xml");
$writer->startDocument('1.0', 'UTF-8');
$writer->setIndent(4);
$writer->startElement('citations');
$papers = $l0doc->getElementsByTagName("paper");
$l1order = 1;

/*
 * Associate L0 Entries with L1
 *
 *
 *
 *
 */
foreach ($papers as $paper) {
    $upperOrder = (int)($paper->getElementsByTagName("order")->item(0)->nodeValue);
    $upperTitle = ($paper->getElementsByTagName("title")->item(0)->nodeValue);
//    print "\n" . $upperOrder;
//    print "paper title: " . $upperTitle . "\n";
    $upperUT = $paper->getElementsByTagName("UT")->item(0)->nodeValue;
    $citation = array();
    if ($upperUT) {
        //go to first page of citation list
        //http://apps.webofknowledge.com/summary.do?product=UA&parentProduct=UA&search_mode=CitedRefList&parentQid=1&qid=2&SID=V1J3pefKLcEm@cdjm62&page=2
        $nextPageURLHeader = 'http://apps.webofknowledge.com/summary.do?product=UA&parentProduct=UA&search_mode=CitedRefList&';
        curl_setopt($citQuery, CURLOPT_URL, $citLinkHeader . $sid . '&UT=' . $upperUT);
        $page = curl_exec($citQuery);
        $page = html_entity_decode($page);
        if (isset($html)) {
            $html->clear();
            unset($html);
        }
        $html = str_get_html($page);
        $nPages = (int)($html->find('span[id="pageCount.top"]', 0)->plaintext);
        $lnks = $html->find('table[id=topNavBar] tr td a');
        $button = $lnks[1]->href;
        parse_str($button, $vars);
        if (isset($vars['qid'])) {
            $qid = (int)($vars['qid']);
        }
        //citation List Pages
        for ($i = 0; $i < $nPages; $i++) {
//            echo "page " . ($i + 1) . ", ";
            foreach ($html->find('tr[id^=RECORD_]') as $record) {
                $tmp = Arr_ParseCitRecordNode($record);
                if (isset($tmp)) {
                    $tmp['order'] = $l1order;
                    $l1order++;
                    array_push($citation, $tmp);
                }
            }
            if ($i != $nPages - 1) {
                $queryURL = $nextPageURLHeader . '&SID=' . $sid . '&qid=' . $qid . '&page=' . ($i + 2);
                curl_setopt($citQuery, CURLOPT_URL, $queryURL);
                $page = curl_exec($citQuery);
                $page = html_entity_decode($page);
                $html->clear();
                unset($html);
                $html = str_get_html($page);
            }
        }
        //done write data to XML
        write_XML($writer, $citation, $upperOrder);
    }
}
$writer->endElement();
echo "done";
return;
?>
