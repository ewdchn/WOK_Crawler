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
        // print $i . "unavailable" . "\n";
        return null;
    } else {
        $ans['title'] = $titleNode->innertext;
        if (!$titleNode->parent()->parent()->href) {
            //print 'have no link: ' . $title . "\n";
            $authorStr = $titleNode->parent()->next_sibling()->plaintext;
        } else {
            //print "URL: ".$titleNode->parent()->parent()->href;
            parse_str($titleNode->parent()->parent()->href, $url_vars);
            //print_r($url_vars);
            $ans['UT'] = $url_vars['amp;isickref'];
            //print $UT;
            //print 'link: ' . $lnk . "\n";
            $authorStr = $titleNode->parent()->parent()->next_sibling()->plaintext;
            //print $title."  ".$authorStr;
        }
        list($dump, $authors) = explode(':', $authorStr);
        $authors = explode(';', $authors);
        // print " ". $title . "\n  ";
        foreach ($authors as $author) {
            if(!(strpos($author,$moreAuthors)===false)){
                $ans['more_authors']=true;break;
            }
            else{
            $author = trim($author, " .");
            array_push($ans['authors'], $author);
            }
            // print "|" . $author . "|";
        }
        $tmp =  get_DOI2($record);
        $ans['DOI'] = $tmp;
        // print "\n  " . $DOI . "\n";
        //print_r($ans);
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
