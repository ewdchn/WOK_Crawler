<?php

function myserialize($arr) {
    $flag = true;
    $output = '';
    foreach ($arr as $value) {

        $flag ? $output.=$value : $output.="|" . $value;
        $flag = false;
    }
    return $output;
}

$con = mysql_connect("localhost", "ewdchn", "fj1-20");
if (!$con) {
    die('Could not connect: ' . mysql_error());
}
mysql_select_db("WOK", $con);
$doc = new DOMDocument();
$doc->load('categories.xml');
foreach ($doc->getElementsByTagName('journal') as $j) {
    $title = mb_strtoupper($j->getElementsByTagName('title')->item(0)->nodeValue);
    $abbrv = mb_strtoupper($j->getElementsByTagName('abbrv')->item(0)->nodeValue);
    $cats = array();
    foreach ($j->getElementsByTagName('category') as $c) {
        array_push($cats, $c->nodeValue);
    }
    $catsString = myserialize($cats);
    $sql = "INSERT INTO categories (title,abbrv,category)VALUES
            ('$title','$abbrv','$catsString')";
    if (!mysql_query($sql, $con)) {
        die('Error: ' . mysql_error());
    }
    else
        echo ".";
    //echo "$title|$abbrv|$catsString\n";
}
?>