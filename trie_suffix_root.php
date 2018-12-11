<?php
require_once('config.php');
require_once('Db.php');
require_once('lcs.php');
require_once('functions.php');

$db = new \My\Simpledb($db_conf);
if ($db->connected !== true) {
    exit("Unable to connect db. Please check db configuration in config.php\n");
}

$sufs = $db->getIndCol("id", "SELECT id, name FROM suffix");
//$sufs = SplFixedArray::fromArray($sufs);
$paradygms = $db->getIndCol("id", "SELECT id, name FROM paradygm");
//$paradygms = SplFixedArray::fromArray($paradygms);


//разбираем парадигмы на составляющие
$callback2 = function($e){
    return explode('|', $e);
};

//тут мы хитро сложим отдельно префиксы, корни, суффиксы
$callback1 = function($e) use ($callback2){
    $exploded = array_map($callback2, explode('-', $e));
    return SplFixedArray::fromArray(
        [
            SplFixedArray::fromArray(array_values(array_unique(array_column($exploded, 0)))), //префиксы
            SplFixedArray::fromArray(array_values(array_unique(array_column($exploded, 1)))), //корни
            SplFixedArray::fromArray(array_values(array_unique(array_column($exploded, 2)))), //суффиксы
        ]
    );
};

$paradygms_parsed = array_map($callback1, $paradygms);
foreach($paradygms_parsed as $pid=>$p){
    $pref2 = array_values((array)$p[0]);
    $suf2 = array_values((array)$p[1]);
    $prop2 = array_values((array)$p[2]);
    foreach($suf2 as $s){
        if(is_string($sufs[$s])){
            $sufs[$s] = ["paradygm_id" => [] , "name" => $sufs[$s]];
        }
        $sufs[$s]["paradygm_id"][] = $pid;
    }
}



$trie = yatrie_new(980000,980000, 100);

foreach($sufs as $k=>$suffix){
    if(!isset($suffix["paradygm_id"])){
        echo $k.PHP_EOL;
        continue;
    }
    $rev = yatrie_strrev($suffix["name"]);
    foreach($suffix["paradygm_id"] as $pid){
        yatrie_add($trie, $rev);
        yatrie_add($trie, $rev.'$'.$pid);
    }
}

yatrie_save($trie, "suffix.trie");


$roots = $db->getAll("SELECT r.name, l.paradygm_id FROM lemmata l INNER JOIN root r ON r.id = l.root_id");
$roots = SplFixedArray::fromArray($roots);
$trie = yatrie_new(5000000,5000000, 100);

foreach($roots as $root){
    $rev = yatrie_strrev($root["name"]);
    yatrie_add($trie, $rev);
    yatrie_add($trie, $rev."$".$root["paradygm_id"]);
}

yatrie_save($trie, "root.trie");

