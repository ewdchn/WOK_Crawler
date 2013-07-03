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

$categories = array();
$host = '140.112.180.153';
$con = mysql_connect($host, "ewdchn", "fj1-20") or die("could ont connect: " . mysql_error());
mysql_select_db("WOK", $con);
$sql_query = "SELECT * FROM categories";
$result = mysql_query($sql_query, $con);
while ($row = mysql_fetch_array($result)) {
    $categories[$row['category']] = $row['id'];
}
$l0doc = new DOMDocument();
$l0doc->load('categories.xml');

/*      LOGIC
 *      foreach journal
 *          foreach category
 *              insert into databse --> get catID
 *              of (duplicate) --> get catID
 *              journal[cat].= catID
 *          insert journal
 */
foreach ($l0doc->getElementsByTagName('journal') as $j) {
    $title = mb_strtoupper($j->getElementsByTagName('title')->item(0)->nodeValue);
    $abbrv = mb_strtoupper($j->getElementsByTagName('abbrv')->item(0)->nodeValue);
    $journalCats = array();
    foreach ($j->getElementsByTagName('category') as $c) {
        array_push($journalCats, $categories[$c->nodeValue]);
    }
    $catsString = myserialize($journalCats);
    $sql = "INSERT INTO journals (title,abbrv,categories)VALUES
            ('$title','$abbrv','$catsString')";
    if (!mysql_query($sql, $con)) {
        die('Error: ' . mysql_error());
    }
    else
        echo ".";
//echo "$title|$abbrv|$catsString\n";
}
?>