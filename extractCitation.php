<?php

set_time_limit(0);
require_once 'simple_html_dom.php';
require_once 'post_data.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, "www.webofknowledge.com");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_AUTOREFERER, false);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");
$main_page = curl_exec($ch);
print "main" + "\n";
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
    //curl_setopt($search,CURLOPT_COOKIEFILE,"cookie.txt");
    //curl_setopt( $search, CURLOPT_REFERER , $referer_url );
    //curl_setopt($search, CURLOPT_COOKIEJAR , "cookie.txt");
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
    //foreach($html->find('tr[id^=RECORD_]') as $record){
    //    print $record->find('a[class=smallV110]',0)->plaintext."\n";
    //    print $record->children(1)->children(1)->plaintext."\n";
    //}
    //print "URL: ".$last_url."\n";
    curl_close($search);
}
print '******************************************extrace citation for each paper**********************************************************' . "\n";
$doc = new DOMDocument();
$doc->load('papers.xml');
$citQuery = curl_init();
$citLinkHeader = 'http://apps.webofknowledge.com/CitedRefList.do?product=UA&sortBy=PY.D&search_mode=CitedRefList&SID=';
curl_setopt($citQuery, CURLOPT_AUTOREFERER, false);
curl_setopt($citQuery, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($citQuery, CURLOPT_RETURNTRANSFER, true);
curl_setopt($citQuery, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");


$citDoc = new DOMDocument();
$citDoc->formatOutput = true;
$r = $citDoc->createElement("citations");
$citDoc->appendChild($r);

$papers = $doc->getElementsByTagName("paper");
foreach ($papers as $paper) {
    $order = (int) ($paper->getElementsByTagName("order")->item(0)->nodeValue);
    print $order."\n";
    $UT = $paper->getElementsByTagName("UT")->item(0)->nodeValue;
    $citation = array();
    if ($UT) {
        //http://apps.webofknowledge.com/summary.do?product=UA&parentProduct=UA&search_mode=CitedRefList&parentQid=1&qid=2&SID=V1J3pefKLcEm@cdjm62&page=2
        $nextPageURLHeader = 'http://apps.webofknowledge.com/summary.do?product=UA&parentProduct=UA&search_mode=CitedRefList&';
        $queryURL = $citLinkHeader . $sid . '&UT=' . $UT;
        curl_setopt($citQuery, CURLOPT_URL, $queryURL);
        $page = curl_exec($citQuery);
        //print $page;
        $html = str_get_html($page);
        $nPages = (int) ($html->find('span[id="pageCount.top"]', 0)->plaintext);
        $lnks = $html->find('table[id=topNavBar] tr td a');
        $button = $lnks[1]->href;
        parse_str($button, $vars);
        if (isset($vars['qid'])) {
            $qid = (int) ($vars['qid']);
        }
        //print "\n" . $button . "\n";

        for ($i = 0; $i < $nPages; $i++) {
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
        //print_r($citation);
        //XML_add($doc, $parent, $child, $text = "");
        $p = XML_add($citDoc, $r, 'paper');
        XML_add($citDoc, $p, "order", $order);
        foreach ($citation as $citedPaper) {
            $cp = XML_add($citDoc, $p, 'citedPaper');
            foreach ($citedPaper as $key => $value) {
                if ($key == 'authors') {
                    foreach ($value as $authorName) {
                        XML_add($citDoc, $cp, "author", $authorName);
                    }
                } else {
                    XML_add($citDoc, $cp, $key, $value);
                }
            }
        }
    }
    $citDoc->save("citedPaper.xml");
}
?>