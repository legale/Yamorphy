<?php
/*Yamorphy v0.1.1 */

require_once('config.php');
require_once('Db.php');


function drop_table(My\Simpledb $db, string $name){
    $db->query("DROP TABLE IF EXISTS ?n", $name);
}

function create_simple_table(My\Simpledb $db, string $name, $name_len = 600){
    $db->query("CREATE TABLE ?n (
    `id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`, `name`), 
    `name` VARCHAR($name_len)  NOT NULL DEFAULT '', INDEX (`name`,`id`)
    ) ENGINE = InnoDB COLLATE=utf8_bin", $name);
}

function add_elementary(My\Simpledb $db, array &$cache, string $type, string $name){
    if (isset($cache[$type][$name])) {
        return $cache[$type][$name];
    } else {
        $cache[$type][$name] = $db->insert("INSERT INTO ?n SET `name` = ?s", $type, $name);
        return $cache[$type][$name];
    }
}

