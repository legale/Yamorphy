<?php
require_once('config.php');
require_once('Db.php');
$db = new \My\Simpledb($db_conf);

if($db->connected !== true){
    exit("Unable to connect db. Please check db configuration in config.php\n");
}

if ($db_create) {
    $db->query("DROP DATABASE IF EXISTS ?n", $db_conf['db']);
    $db->query("CREATE DATABASE ?n", $db_conf['db']);
}

$db->query("use ?n", $db_conf['db']);

$table = 'words';
$check = $db->query_silent("SELECT 1 FROM `$table` LIMIT 1");
if ($check === false) {
    $db->query("CREATE TABLE `$table` (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`, `name`), 
    `name` VARCHAR(75)  NOT NULL DEFAULT '' 
    ) ENGINE = InnoDB");
    $db->query("ALTER TABLE `$table` ADD INDEX (`name`, `id`)");
}
$db->query("truncate table `$table`");


$table = 'grammemes';
$check = $db->query_silent("SELECT 1 FROM `$table` LIMIT 1");
if ($check === false) {
    $db->query("CREATE TABLE `$table` (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`), 
    `name` VARCHAR(50)  NOT NULL DEFAULT '' ,
    `parent` VARCHAR(50)  NOT NULL DEFAULT '' ,
    `alias` VARCHAR(50)  NOT NULL DEFAULT '' ,
    `desc` VARCHAR(512)  NOT NULL DEFAULT '' 
    ) ENGINE = InnoDB");
}
$db->query("truncate table `$table`");


$table = 'restrictions';
$check = $db->query_silent("SELECT 1 FROM `$table` LIMIT 1");
if ($check === false) {
    $db->query("CREATE TABLE `$table` (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`), 
    `type` VARCHAR(256)  NOT NULL DEFAULT '' ,
    `auto` TINYINT(1)  UNSIGNED NOT NULL DEFAULT 0 ,
    `left_type` VARCHAR(50)  NOT NULL DEFAULT '' ,
    `left` VARCHAR(50)  NOT NULL DEFAULT '' ,
    `right_type` VARCHAR(50)  NOT NULL DEFAULT '' ,
    `right` VARCHAR(50)  NOT NULL DEFAULT '' 
    ) ENGINE = InnoDB");
}
$db->query("truncate table `$table`");

$table = 'lemmata';
$check = $db->query_silent("SELECT 1 FROM `$table` LIMIT 1");
if ($check === false) {
    $db->query("CREATE TABLE `$table` (
    `id` MEDIUMINT UNSIGNED NOT NULL, PRIMARY KEY (`id`), 
    `rev` MEDIUMINT UNSIGNED NOT NULL, 
    `word_id` MEDIUMINT UNSIGNED NOT NULL, 
    `props` VARCHAR(100)  NOT NULL DEFAULT '' 
    ) ENGINE = InnoDB");
}
$db->query("truncate table `$table`");


$table = 'forms_uni';
$check = $db->query_silent("SELECT 1 FROM `$table` LIMIT 1");
if ($check === false) {
    $db->query("CREATE TABLE `$table` (
    `id` MEDIUMINT UNSIGNED NOT NULL,
    `word_id` MEDIUMINT UNSIGNED NOT NULL, 
    `props` VARCHAR(100)  NOT NULL DEFAULT '', PRIMARY KEY (`id`, `props`, `word_id`)
    ) ENGINE = InnoDB");
    $db->query("ALTER TABLE $table ADD INDEX (`word_id`, `id`)");
}
$db->query("truncate table `$table`");


$table = 'grammemes';
foreach (glob($serialized . 'grammeme_*') as $filename) {
    $data = unserialize(file_get_contents($filename));
    foreach ($data as $i => &$elem) {
        $set = array(
            'id' => null,
            'name' => $elem[2]['name_0'][2],
            'parent' => $elem[1]['parent'],
            'alias' => $elem[2]['alias_1'][2],
            'desc' => $elem[2]['description_2'][2]
        );
        $q = "INSERT `$table` SET ?u";
        if ($db->query($q, $set) === false) {
            print "query error: $db->prepared_query \n";
        }

    }
}


$table = 'restrictions';
foreach (glob($serialized . 'restr_*') as $filename) {
    $data = unserialize(file_get_contents($filename));
    foreach ($data as $i => &$elem) {
        $set = array(
            'id' => null,
            'type' => $elem[1]['type'],
            'auto' => $elem[1]['auto'],
            'left_type' => $elem[2]['left_0'][1]['type'],
            'left_type' => $elem[2]['right_1'][1]['type'],
            'left' => $elem[2]['left_0'][2],
            'right' => $elem[2]['right_1'][2]
        );
        $q = "INSERT `$table` SET ?u";
        if ($db->query($q, $set) === false) {
            print "query error: $db->prepared_query \n";
        }

    }
}

print "m before:" . memory_get_usage(true);
print ' c:' . gc_collect_cycles();
gc_mem_caches();
print " m after:" . memory_get_usage(true) . "\n";

$words = array();
$post = $db->getInd('name', "SELECT name, id FROM `grammemes` WHERE name != ?s", 'POST');
$post = array_combine(array_column($post, 'name'), array_column($post, 'id'));
//print_r($post);
//die;


foreach (glob($serialized . 'lemma_*') as $filename) {
    print "m before:" . memory_get_usage(true);
    print ' c:' . gc_collect_cycles();
    gc_mem_caches();
    print " m after:" . memory_get_usage(true) . "\n";
    unset($data);
    $data = unserialize(file_get_contents($filename));
    array_walk($data, 'parse_lemma');
}


function parse_lemma($elem)
{
    global $db, $post;
    $part = null;
    $table = 'lemmata';

    foreach ($elem[2]['l_0'][2] as &$e) {
        if (isset($post[$e[1]['v']])) {
            $part = $post[$e[1]['v']];
        }
        $props[] = $post[$e[1]['v']];
    }

    $set = array(
        'id' => $elem[1]['id'],
        'rev' => $elem[1]['rev'],
        'word_id' => get_word_id($elem[2]['l_0'][1]['t']),
        'props' => implode('|', $props)
    );

    if ($db->query("INSERT `$table` SET ?u", $set) === false) {
        print "query error: $db->prepared_query \n";
    }
    unset($elem[2]['l_0']);
    return parse_uni($set['id'], $elem[2]);

}


function parse_uni($id, $array)
{
    global $db, $post;
    $table = 'forms_uni';
    $set['id'] = $id;

    foreach ($array as $f) {
        unset($props, $set['name'], $set['props']);
        switch ($f[0]) {
            case 'f':
                $set['word_id'] = get_word_id($f[1]['t']);
                if (gettype($f[2]) === 'string') {
                    continue(2);
                }
                foreach ($f[2] as $g) {
                    $props[] = $post[$g[1]['v']];
                }
                break;
            default:
                print "id: $id unknown tag: $f[0]";
                return false;
        }

        $set['props'] = implode('|', $props);
        if ($db->query("INSERT `$table` SET ?u", $set) === false) {
            print "query error: $db->prepared_query \n";
            return false;
        }

    }

    return true;
}

function get_word_id($word)
{
    global $db, $words;
    $table = 'words';
    if (isset($words[$word])) {
        return $words[$word];
    }

    $id = $db->getOne("SELECT id FROM $table WHERE name=?s", $word);
    if ($id === false) {
        if ($db->query("INSERT $table SET ?u", array('name' => $word)) !== false) {
            $id = $db->insertId();
        }
    }
    if ($id !== false) {
        $words[$word] = $id;
        return $id;
    } else {
        return false;
    }
}