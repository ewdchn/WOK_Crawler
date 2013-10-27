<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'post_data.php';
require_once 'simple_html_dom.php';

function Arr_extractFullRecordPage(&$content)
{
    $html = str_get_html(html_entity_decode($content));
    $tmp = array();

    //get title
    $titleNode = $html->find('td[class=FullRecTitle]', 0);
    if (!$titleNode) {
        $tmp['title'] = 'unavailable';
    } else {
        $titleStr = $html->find('td[class=FullRecTitle]', 0)->find('value', 0)->plaintext;
        $tmp['title'] = str_replace('  ', ' ', trim(html_entity_decode($titleStr), ' '));
    }
    //get authors
    $authorStr = $html->find('a[title^="Find more records by this author"]', 0);
    if ($authorStr) {
        $authorStr = $authorStr->parent()->plaintext;
        list($dump, $authors) = explode(":", $authorStr);
        $tmp['authors'] = array();
        preg_match_all('/\(([^\(\)]+)\)/', $authors, $authorArray);
        $tmp['authors'] = $authorArray[1];
        array_walk($tmp['authors'], create_function('&$val', '$val = trim($val,". ");'));
    }
    //get DOI
    if (($tmpDOI = recordPage_getAttr($html,'DOI')) !== false) {
        $tmp['DOI'] = $tmpDOI;
    }

    //get Source
    if (($tmpSrc = recordPage_getAttr($html, 'Source')) !== false) {
        $tmp['Source'] = html_entity_decode($tmpSrc);
    }
    //get UT
    if ($html->find('a[title^="View this record"]', 0)) {
        $citListRef = $html->find('a[title^="View this record"]', 0)->href;
        parse_str($citListRef, $url_vars);
        $tmp['UT'] = $url_vars['UT'];
    }

    //get REFID
    foreach ($html->find('span.FR_label') as $span) {
        $pos = strpos($span->plaintext, 'Times Cited');
        if ($pos === false) {
        } else {
            $parent = $span->parent();
            if (($a = $parent->find('a', 0)) !== false) {
                parse_str($a, $vars);
                if (isset($vars['REFID']))
                    $tmp['REFID'] = $vars['REFID'];
            }
        }
    }

    //get Source
    $html->clear();
    unset($html);
    return $tmp;
}

?>
