<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$file = fopen('journal_lnks','r');
$j = unserialize(fgets($file));
echo count($j);
?>
