<?php
require_once('config.php');
require_once('Db.php');
require_once('lcs.php');
require_once('functions.php');

$db = new \My\Simpledb($db_conf);
if ($db->connected !== true) {
    exit("Unable to connect db. Please check db configuration in config.php\n");
}

$cache = [];
$tables = ["suffix", "root", "prefix", "paradygm", "props"];
foreach ($tables as $table) {
    drop_table($db, $table);
    create_simple_table($db, $table);
    $cache[$table] = [];
}
//$db->query("ALTER TABLE `root` ADD `paradygm_id` VARCHAR(400) NOT NULL DEFAULT '' AFTER `name`, ADD INDEX `paradygm_id` (`paradygm_id`)");


if(empty($db->getAll("SHOW COLUMNS FROM `lemmata` LIKE 'root_id'"))) {
    $db->query("ALTER TABLE `lemmata` ADD `root_id` MEDIUMINT NOT NULL DEFAULT 0, ADD INDEX `root_id` (`root_id`)");
}else {
    $db->query("UPDATE `lemmata` SET root_id = 0 WHERE root_id != 0");
}

if(empty($db->getAll("SHOW COLUMNS FROM `lemmata` LIKE 'paradygm_id'"))) {
    $db->query("ALTER TABLE `lemmata` ADD `paradygm_id` SMALLINT NOT NULL DEFAULT 0,  ADD INDEX `paradygm_id` (`paradygm_id`)");
}else{
    $db->query("UPDATE `lemmata` SET paradygm_id = 0 WHERE paradygm_id != 0");
}




$lems = $db->getInd("lid", "SELECT l.id as lid, w.name as name, l.props 
    FROM lemmata l 
    INNER JOIN words w ON l.word_id = w.id WHERE 1 ##AND l.`id` in (375073)");
$lems = SplFixedArray::fromArray($lems);

foreach ($lems as $lem) {
    if ($lem === null) continue;
    $lem_id = (int)$lem["lid"];
    //& 1023 соответствует делению на 1024
    if (($lem_id & 1023) === 0) echo $lem_id . PHP_EOL;
    $forms = $db->getAll("SELECT f.id as lid, w2.name, f.props
        FROM forms_uni f
        INNER JOIN words w2 on w2.id = f.word_id
        WHERE 1 AND f.id = ?i", $lem_id);

    //теперь добавим саму форму леммы
    $forms[] = $lems[$lem_id];
    //сначала найдем корень
    $root = $lems[$lem_id]["name"];
    foreach ($forms as $form) {
        $root = lcs($root, $form["name"]);
    }
    $root_len = strlen($root);
    //теперь суффиксы и префиксы
    $paradygm = [];
    foreach ($forms as $form) {
        if ($root !== "") {
            $pos = strpos($form["name"], $root);
            $prefix = substr($form["name"], 0, $pos);
            $suffix = substr($form["name"], $pos + $root_len);
        } else {
            $suffix = $form["name"];
            $prefix = "";
        }
        $prefix_id = add_elementary($db, $cache, "prefix", $prefix);
        $suffix_id = add_elementary($db, $cache, "suffix", $suffix);
        $props_id = add_elementary($db, $cache, "props", $form["props"]);
        $paradygm[] = implode("|", [$prefix_id, $suffix_id, $props_id]);
    }
    sort($paradygm);
    $paradygm_id = add_elementary($db, $cache, "paradygm", implode('-', $paradygm));
    $root_id = add_elementary($db, $cache, "root", $root);

    $db->query("UPDATE `lemmata` 
      SET `paradygm_id` = ?i, `root_id` = ?i 
      WHERE `id` = ?i", $paradygm_id, $root_id, $lem_id);


}
