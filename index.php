<?php
require_once 'simple_html_dom.php';
$post=array(
"fieldCount"=>"3",
"action"=>"search",
"product"=>"UA",
"search_mode"=>"GeneralSearch",
"max_field_count"=>"25",
"value(input1)"=>"Digital humanities",
"value(select1)"=>"TS",
"value(hidInput1)"=>"initVoid",
"value(hidShowIcon1)"=>"0",
"value(bool_1_2)"=>"OR",
"value(input2)"=>"humanities computing",
"value(select2)"=>"TS",
"value(hidInput2)"=>"",
"value(hidShowIcon2)"=>"0",
"value(bool_2_3)"=>"AND",
"value(input3)"=>"",
"value(select3)"=>"SO",
"value(hidInput3)"=>"SO",
"value(hidShowIcon3)"=>"1",
"x"=>"184",
"y"=>"435",
"limitStatus"=>"collapsed",
"ss_lemmatization"=>"On",
"period"=>"Range Selection",
"range"=>"ALL",
"startYear"=>"1900",
"endYear"=>"2013",
"rs_rec_per_page"=>"10",
"rs_sort_by"=>"PY.D;LD.D;SO.A;VL.D;PG.A;AU.A",
"rs_linksWindows"=>"newWindow",
);
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,"www.webofknowledge.com");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_AUTOREFERER, false);
curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");
$main_page = curl_exec($ch);
print $main_page;
//get SID
preg_match('/^Set-Cookie:\s*([^;]*)/mi', $main_page, $m);
parse_str($m[1], $cookies);
$sid=$cookies['SID'];
$sid = str_replace('"', '', $sid);
$post['SID']=$sid;
print 'SID: '.$sid."\n";
//go to next page
//preg_match('#Location: (.*)#', $main_page, $r);
// $l = trim($r[1]);
//curl_setopt($ch,CURLOPT_URL,$l);
//$main_page=curl_exec($ch);
//preg_match('/^Set-Cookie:\s*([^;]*)/mi', $main_page, $m);
//parse_str($m[1], $cookies);
//$jsid=$cookies['JSESSIONID'];
//print "JSID: ";
//print $jsid;print "\n";
//$init_url= 'http://apps.webofknowledge.com/home.do;jsessionid='.$jsid.'?SID='.$sid.'&SrcApp=CR&Init=Yes';
//print $init_url.'\n';
//curl_setopt($ch,CURLOPT_URL,$init_url);
//$main_page=curl_exec($ch);
//print '***********************initializing********************************'."\r\n";
//print $main_page;


//print $main_page;


print '************************performing query****************************'."\r\n";

//$referer_url='http://apps.webofknowledge.com/UA_GeneralSearch_input.do;jsessionid='.$jsid.'?product=UA&search_mode=GeneralSearch&SID='.$sid.'&preferencesSaved=';
//print $referer_url."\n";
$search = curl_init();
curl_setopt($search, CURLOPT_HEADER, true);
curl_setopt($search,CURLOPT_URL,"apps.webofknowledge.com/UA_GeneralSearch.do");
curl_setopt($search, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($search, CURLOPT_AUTOREFERER, false);
curl_setopt($search, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($search, CURLOPT_RETURNTRANSFER, true);
curl_setopt($search, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
curl_setopt($search, CURLOPT_POST, true);
curl_setopt($search,CURLOPT_POSTFIELDS, http_build_query($post));
//curl_setopt($search,CURLOPT_COOKIEFILE,"cookie.txt");
//curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//curl_setopt( $search, CURLOPT_REFERER , $referer_url );
//curl_setopt($search, CURLOPT_COOKIEJAR , "cookie.txt");
$result = curl_exec($search);
$last_url = curl_getinfo($search, CURLINFO_EFFECTIVE_URL);
$url_comp = parse_url($last_url);
parse_str($url_comp['query'],$output);
$qid = $output['qid'];
print "qid: ".$qid."\n";
print "\n"."*************************results***************************"."\n";
print "URL: ".$last_url."\n";
$html = str_get_html($result);
foreach($html->find('tr[id^=RECORD_]') as $record){
    print $record->find('a[class=smallV110]',0)->plaintext."\n";
    print $record->children(1)->children(1)->plaintext."\n";
}
//print $result;

curl_close($search);
curl_close($ch);
?>
