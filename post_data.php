<?php

$curl_options = array(
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_AUTOREFERER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.110 Safari/537.36',
);

function XML_add($doc, $parent, $child, $text = "")
{
    // $parent --> DOM node
    // $child --> string
    $newnode = $doc->createElement($child);

    if ($text) {
        $newnode->appendChild($doc->createTextNode($text));
    }
    $parent->appendChild($newnode);
    return $newnode;
}
function getRidOfNbsp($input){
    return utf8_encode(trim(html_entity_decode(str_replace(html_entity_decode('&nbsp;'),'',$input))));
}
function write_XML($writer, $data, $order)
{
    if (!$data) {
        print "Error: data is empty\n";
        return;
    } else {
        $writer->startElement('paper');
        $writer->writeElement('order', utf8_encode($order));
        foreach ($data as $ct) {
            $writer->startElement('citedPaper');
            foreach ($ct as $key => $value) {
                if ($key == "authors") {
                    foreach ($value as $author) {
                        $writer->writeElement('author', utf8_encode($author));
                    }
                } else {
                    $writer->writeElement($key, utf8_encode($value));
                }
            }
            $writer->endElement();
        }
        $writer->endElement();
    }
}

function recordPage_getAttr(&$html, $attr = 'DOI')
{
    foreach ($html->find('td[class=fr_data_row] span[class=FR_label]') as $ttt) {
        $pos = strpos($ttt->plaintext, $attr);
        if ($pos !== false)
            return getRidOfNbsp($ttt->next_sibling()->innertext);
    }
    return false;
}

function recordNode_getDOI($html)
{
    foreach ($html->find('span.label') as $span) {
        $pos = strpos($span->plaintext, 'DOI');
        if ($pos === false) {
        } else {
            $ans = $span->nextSibling()->innertext;
            //print $ans;
            if (strpos($ans, '10') === 0)
                return getRidOfNbsp($ans);
            else
                return false;
        }
    }
    return false;
}

function recordNode_getSrc(&$html)
{
    foreach ($html->find('span.label') as $span) {
        $pos = strpos($span->plaintext, 'Source');
        if ($pos === false) ;
        else return getRidOfNbsp($span->parent()->find("text", 2)->innertext);
    }
    return false;
}

function recordNode_getREFID(&$html)
{
    foreach ($html->find('span.label') as $span) {
        $pos = strpos($span->innertext, 'Times Cited');
        if ($pos === false) continue;
        else {
            $a = $span->next_sibling();
            if (isset($a->href)) {
                parse_str(html_entity_decode($a->href), $vars);
                if (isset($vars['REFID']))
                { return $vars['REFID'];}
            }
        }
    }
    return false;
}

function Arr_ParseCitRecordNode(&$_html)
{
    $moreAuthors = 'et al';
    $ans = array();
    $ans['authors'] = array();

    //title
    $titleNode = $_html->find('span[class=reference-title] value', 0);
    if (!$titleNode) {
        $ans['title'] = 'unavailable'; // print $i . "No title skipping" . "\n";
    } else {
        $ans['title'] = $titleNode->innertext;
        //UT
        if (!$titleNode->parent()->parent()->href) {
        } else {
            parse_str(getRidOfNbsp($titleNode->parent()->parent()->href), $url_vars);
            $ans['UT'] = $url_vars['isickref'];
        }
    }
    //author
    foreach ($_html->find('td[class=summary_data] div span[class=label]') as $div) {
        if (!(strpos($div->plaintext, "Author") === false)) {
            $authorStr = $div->parent()->plaintext;
            break;
        }
    }
    if (!isset($authorStr)) {
//        print "\n authorStr Not Found: Result: " . $ans['title'] . "\n";
    } else {
        list($dump, $authors) = explode(':', $authorStr);
        $authors = explode(';', $authors);
        foreach ($authors as $author) {
            if (!(strpos($author, $moreAuthors) === false)) {
                $ans['more_authors'] = true;
                break;
            } else {
                $author = trim($author, " .");
                array_push($ans['authors'], $author);
            }
        }
    }

    //DOI & source
    $DOI = recordNode_getDOI($_html);
    $src = recordNode_getSrc($_html);
    $REFID = recordNode_getREFID($_html);
    if ($src)
        $ans['Source'] = $src;
    if ($DOI)
        $ans['DOI'] = $DOI;
    if ($REFID)
        $ans['REFID'] = $REFID;

    return $ans;
}

?>
