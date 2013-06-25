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
    $ans="";
    $html = str_get_html($file);
    foreach ($html->find('span[class=FR_label]') as $ttt) {
        $pos = strpos($ttt->plaintext, 'DOI');
        //print $pos."\n";
        if ($pos === false) {
        } else {
            $ans = $ttt->nextSibling()->plaintext;
            //print $ans;
            if(strpos($ans, '10')===0){return $ans;}
            else return "";
        }
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
