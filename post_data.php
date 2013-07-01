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

function get_attribute(&$html, $attr = 'DOI') {
    foreach ($html->find('td[class=fr_data_row] span[class=FR_label]') as $ttt) {
        $pos = strpos($ttt->plaintext, $attr);
        if ($pos !== false) {
            foreach ($ttt->parent()->children as $ss) {
                $ans[] = $ss->innertext;
            }
            //print_r($ans);
            foreach ($ans as $key => $token) {
                if (strpos($token, $attr) !== false) {
                    return trim($ans[$key + 1], ' ');
                }
            }
        }
    }
    return false;
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
        $ans['title'] = 'unavailable';        // print $i . "No title skipping" . "\n";
    } else {
        $ans['title'] = $titleNode->innertext;
    }
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
?>
