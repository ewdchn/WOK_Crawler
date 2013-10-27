<?php

$post = array(
    "fieldCount" => "3",
    "action" => "search",
    "product" => "UA",
    "search_mode" => "GeneralSearch",
    "max_field_count" => "25",
    "value(input1)" => "Digital humanities",
    "value(select1)" => "TS",
    "value(hidInput1)" => "initVoid",
    "value(hidShowIcon1)" => "0",
    "value(bool_1_2)" => "OR",
    "value(input2)" => "humanities computing",
    "value(select2)" => "TS",
    "value(hidInput2)" => "",
    "value(hidShowIcon2)" => "0",
    "value(bool_2_3)" => "AND",
    "value(input3)" => "",
    "value(select3)" => "SO",
    "value(hidInput3)" => "SO",
    "value(hidShowIcon3)" => "1",
    "x" => "184",
    "y" => "435",
    "limitStatus" => "collapsed",
    "ss_lemmatization" => "On",
    "period" => "Range Selection",
    "range" => "ALL",
    "startYear" => "1900",
    "endYear" => "2013",
    "rs_rec_per_page" => "10",
    "rs_sort_by" => "PY.D;LD.D;SO.A;VL.D;PG.A;AU.A",
    "rs_linksWindows" => "newWindow",
);

function get_session_SID() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, "www.webofknowledge.com");
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
    $main_page = curl_exec($ch);
    preg_match('/^Set-Cookie:\s*([^;]*)/mi', $main_page, $m);
    parse_str($m[1], $cookies);
    $sid = $cookies['SID'];
    $sid = str_replace('"', '', $sid);
    print 'SID: ' . $sid . "\n";

    curl_close($ch);
    return $sid;
}

function get_Entries() {
    global $post;
    $result = "";
    $nEntries=0;$qid=0;
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
        print "entries string" . "\n" . $entries_str . "\n";
        $nEntries = (int) ($entries_str);

        curl_close($search);
    }
    return array($nEntries,$qid);
}

?>
