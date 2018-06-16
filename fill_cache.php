<?php
require_once(__DIR__ . '/Dtimer.php');
require_once(__DIR__ . '/bmark.php');
require_once('config.php');
require_once('Db.php');

$db = new \My\Simpledb($db_conf);

dtimer::$enabled = false;

if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        if (!@$redis->connect('127.0.0.1', 6379)) {
            unset($redis);
        }
    } catch (RedisException $e) {
    }
}

if (isset($argv[1]) && $argv[1] === 'reset') {
    $redis->flushAll();
    exit('redis cache cleared!');
} else if (isset($argv[1]) && $argv[1] === 'info') {
    print_r($redis->info());
} else if (isset($argv[1]) && $argv[1] === 'fill') {
    cache_words();
    cache_lemmata();
    cache_post();
    cache_forms();
} else if (isset($argv[1]) && $argv[1] === 'memory_test') {

    $q = $db->query("SELECT * FROM forms_uni WHERE 1 LIMIT 99999999");
    $i = 0;
    while ($res = $db->fetch($q)) {
        ++$i;
    }
}


function cache_words()
{
    global $db_conf;
    $db = new \My\Simpledb($db_conf);
    //$q = $db->query("SELECT * FROM words WHERE id in (SELECT word_id FROM forms_uni WHERE id in (select id FROM forms_uni WHERE word_id = 935256)) LIMIT 99999999");
    $q = $db->query("SELECT * FROM words WHERE 1 LIMIT 99999999");
    $a = array();
    $i = 0;
    while ($res = $db->fetch($q)) {
        dtimer::log(__LINE__ . ' cycle start');
        redis_set($res['name'], $res['id']);
        dtimer::log(__LINE__ . ' cache_set end');
        redis_set('w' . $res['id'], msgpack_pack(array(0 => $res['name'])));
        dtimer::log(__LINE__ . ' cache_set end');
        ++$i;
        if ($i % 1000 === 0) {
            print  $i;
            print ' m: ' . memory_get_usage(true) . "\n";
            gc_collect_cycles();
            gc_mem_caches();
        }
        dtimer::log(__LINE__ . ' cycle end');
    }
    gc_collect_cycles();
    gc_mem_caches();
    return true;
}

function cache_lemmata()
{
    global $db_conf;
    $db = new \My\Simpledb($db_conf);


    //$q = $db->query("SELECT * FROM lemmata WHERE word_id = 935256 LIMIT 99999999999");
    $q = $db->query("SELECT * FROM lemmata WHERE 1 LIMIT 99999999999");
    $a = array();
    $i = 0;
    while ($res = $db->fetch($q)) {
        $key = 'l' . $res['id'];
        $success = null;
        $content = redis_get($key, $success);
        if ($success) {
            $content = msgpack_unpack($content);
            $content['word_id'] = &$res['word_id'];
            $content['props'] = &$res['props'];
        } else {
            $content = array('word_id' => &$res['word_id'], 'props' => &$res['props']);
        }
        redis_set($key, msgpack_pack($content));
        ++$i;
        if ($i % 1000 === 0) {
            print  $i;
            print ' m: ' . memory_get_usage(true) . "\n";
        }
    }
    gc_collect_cycles();
    gc_mem_caches();
    return true;
}

function cache_forms()
{
    global $db_conf;
    $db = new \My\Simpledb($db_conf);

    //$q = $db->query("SELECT * FROM forms_uni WHERE id in (select id FROM forms_uni WHERE word_id = 935256) LIMIT 99999999");
    $q = $db->query("SELECT * FROM forms_uni WHERE 1 LIMIT 99999999");
    $i = 0;
    while ($res = $db->fetch($q)) {
        dtimer::log(__LINE__ . ' cycle start');
        $lid = (int)$res['id'];
        $wid = (int)$res['word_id'];
        $success = false;
        $content = redis_get('w' . $res['word_id'], $success);
        if ($success) {
            $content = msgpack_unpack($content);
            if (array_key_exists(1, $content) && is_array($content[1])) {
                $content[1][$lid] = array();
            } else {
                $content[1] = array($lid => '');
            }
        } else {
            $content = array(1 => array($lid => ''));
        }

        redis_set('w' . $res['word_id'], msgpack_pack($content));
        $props = explode('|', $res['props']);
        foreach ($props as &$prop) {
            $prop = (int)$prop;
        }

        $success = false;
        $content = redis_get('l' . $res['id'], $success);
        if ($success) {
            $content = msgpack_unpack($content);
            if (array_key_exists(2, $content) && is_array($content[2])) {
                $content[2][$wid] = $props;
            } else {
                $content[2] = array($wid => $props);
            }
        } else {
            $content = array(2 => array($wid => $props));
        }
        redis_set('l' . $res['id'], msgpack_pack($content));

        ++$i;
        if ($i % 1000 === 0) {
            print  $i;
            print ' m: ' . memory_get_usage(true) . "\n";
            gc_collect_cycles();
            gc_mem_caches();
        }
        dtimer::log(__LINE__ . ' cycle end');
    }
    gc_collect_cycles();
    gc_mem_caches();
    return true;
}

function cache_post()
{
    global $db_conf;
    $db = new \My\Simpledb($db_conf);

    $q = $db->query("SELECT * FROM `grammemes` WHERE `name` != 'POST'");
    while ($res = $db->fetch($q)) {
        dtimer::log(__LINE__ . ' cycle start');
        $key = array_shift($res);
        redis_set('g' . $key, msgpack_pack($res));
        dtimer::log(__LINE__ . ' cycle end');
    }
    return true;
}

function redis_get($key, &$success = false)
{
    dtimer::log('redis_get start');
    global $redis;
    $res = $redis->get($key);
    dtimer::log('redis_get end');
    if ($res !== false) {
        $success = true;
        return $res;
    } else {
        return false;
    }
}

function redis_set($key, $content, &$success = false)
{
    global $redis;
    $res = $redis->set($key, $content);
    if ($res !== false) {
        $success = true;
        return $res;
    } else {
        return false;
    }
}
