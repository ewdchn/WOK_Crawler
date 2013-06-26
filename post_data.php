<?php

function XML_add($doc, $parent, $child, $text = "") {
    // $parent --> DOM node
    // $child --> string
    $newnode = $doc->createElement($child);
    if ($text) {
        $newnode->appendChild($doc->createTextNode($text));
    }
    $parent->appendChild($newnode);
    return $newnode;
}

function write_XML($writer, $data, $order) {
    if (!$data) {
        print "Error: data is empty\n";
        return;
    } else {
        $writer->startElement('paper');
        $writer->writeElement('order', $order);
        foreach ($data as $ct) {
            $writer->startElement('citedPaper');
            foreach ($ct as $key => $value) {
                if ($key == "authors") {
                    foreach ($value as $author) {
                        $writer->writeElement('author', $author);
                    }
                } else {
                    $writer->writeElement($key, $value);
                }
            }
            $writer->endElement();
        }
        $writer->endElement();
    }
}

function get_DOI($file) {
    //print $file;
    $ans = "";
    $html = str_get_html($file);
    foreach ($html->find('span[class=FR_label]') as $ttt) {
        $pos = strpos($ttt->plaintext, 'DOI');
        //print $pos."\n";
        if ($pos === false) {
            
        } else {
            $ans = $ttt->nextSibling()->plaintext;
            //print $ans;
            if (strpos($ans, '10') === 0) {
                return $ans;
            }
            else
                return "";
        }
    }
}

function get_DOI2($html) {
    foreach ($html->find('span[class=label]') as $span) {
        $pos = strpos($span->plaintext, 'DOI');
        if ($pos === false) {
            
        } else {
            $ans = $span->nextSibling()->plaintext;
            //print $ans;
            if (strpos($ans, '10') === 0) {
                return $ans;
            }
            else
                return "";
        }
    }
}

function get_Record($record) {
    $moreAuthors = 'et al';
    $ans = array();
    $ans['authors'] = array();
    $titleNode = $record->find('span[class=reference-title] value', 0);
    if (!$titleNode) {
        return null;        // print $i . "No title skipping" . "\n";
    } else {
        $ans['title'] = $titleNode->innertext;
        if (!$titleNode->parent()->parent()->href) {
            
        } else {
            parse_str($titleNode->parent()->parent()->href, $url_vars);
            $ans['UT'] = $url_vars['amp;isickref'];
        }
        foreach ($record->find('td[class=summary_data] div span[class=label]') as $div) {
            if (!(strpos($div->plaintext, "Author") === false)) {
                $authorStr = $div->parent()->plaintext;
                break;
            }
        }
        if (!isset($authorStr)) {
            print "\n authorStr Not Found: Result: " . $ans['title'] . "\n";
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


        $tmp = get_DOI2($record);
        if ($tmp)
            $ans['DOI'] = $tmp;
        return $ans;
    }
}

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
?>
