<?php


function get_categories($html) {
    foreach ($html->find('tr b')as $b) {
        if (strpos($b->innertext, "Subject Categories") !== false) {
            $j = $b->parent()->nextSibling()->find('p', 0)->plaintext;
            $categories = explode("\n", trim(html_entity_decode($j), "\n "));
            foreach ($categories as $key => $c) {
                if (str_word_count($c) === 0) {
                    unset($categories[$key]);
                }
            }
        }
    }
    return $categories;
}

function get_full_journal_title($html) {
    foreach ($html->find('tr b')as $b) {
        if (strpos($b->innertext, "Full Journal Title") !== false) {
            $j = html_entity_decode($b->parent()->nextSibling()->find('text', 0)->plaintext);
            // return trim(preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $j));
            return trim(str_replace("\xC2\xA0", "", $j));
            
        }
    }
}

set_time_limit(0);

$flag = false;



$cookie_file = 'cookie.txt';
$journals = array();
require_once 'simple_html_dom.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, "http://admin-router.webofknowledge.com/?DestApp=JCR");
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_AUTOREFERER, false);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36");
$main_page = curl_exec($ch);
preg_match('/^Set-Cookie:\s*([^;]*)/mi', $main_page, $m);
parse_str($m[1], $cookies);
$sid = $cookies['SID'];
$sid = str_replace('"', '', $sid);
print 'SID: ' . $sid . "\n";
$form_data = array(
    'edition' => 'science',
    'science_year' => '2012',
    'social_year' => '2012',
    'view' => 'category',
    'RQ' => 'SELECT_ALL',
    'Submit.x' => '1',
    'SID' => $sid,
    'query_new' => 'true'
);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_URL, 'http://admin-apps.webofknowledge.com/JCR/JCR');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($form_data));
$cursor = 1;

if ($flag) {
    do {
        echo "$cursor \n";
        $page = curl_exec($ch);
        $html = str_get_html($page);
        foreach ($html->find('td[class=sorted] a') as $j) {
            $journals[$j->innertext] = $j->href;
        }
        curl_setopt($ch, CURLOPT_POST, false);
        $cursor+=20;
        curl_setopt($ch, CURLOPT_URL, 'admin-apps.webofknowledge.com/JCR/JCR?RQ=SELECT_ALL&cursor=' . $cursor);
    } while ($cursor < 8411);
//print_r($journals);
    $file = fopen('journal_list', 'w');
    fwrite($file, serialize($journals));
    fclose($file);
} else {
    $page = curl_exec($ch);
    curl_setopt($ch, CURLOPT_POST, false);
    $file = fopen('journal_list', 'r');
    $journals = unserialize(fgets($file));
}

$writer = new XMLWriter();
$writer->openUri("categories.xml");
$writer->startDocument('1.0', 'UTF-8');
$writer->setIndent(4);
$writer->startElement('journals');
foreach ($journals as $journal_name => $journal_url) {
    $writer->startElement('journal');
    $journal_categories = array();
    curl_setopt($ch, CURLOPT_URL, $journal_url);
    $html = str_get_html(curl_exec($ch));
    $journal_categories['categories'] = get_categories($html);
    $journal_categories['full_title'] = get_full_journal_title($html);
    echo $journal_name . " ";
    echo $journal_categories['full_title'] . "\n";
    $writer->writeElement('title', $journal_categories['full_title']);
    foreach ($journal_categories['categories'] as $cat) {
        $writer->writeElement('category', $cat);
    }
    $writer->writeElement('abbrv', $journal_name);
    $writer->endElement();
}
$writer->endElement();

return;
?>
