<?php
require_once('Dtimer.php');
require_once('bmark.php');

require_once('config.php');
require_once('Db.php');


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


//run
cli_read($argv);


//$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
//$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
//var_dump($redis->getOption(Redis::OPT_SERIALIZER));


dtimer::show_console();

function cli_read($argv)
{
    array_shift($argv); //element 0 is a script filename
    $cnt = count($argv);
    if ($cnt < 2) {
        exit("commands: find, solo \nsyntax: [command] [argument] \nex: find мусорок\n");
    }

    $func = array_shift($argv);
    $forms = call_user_func_array($func, $argv);
    print_r($forms);

}

function find($word, $props = null)
{
    global $db_conf, $redis;
    $db = new \My\Simpledb($db_conf);
    if ($redis) {
        $wid = redis_get($word);
    }

    if (!$redis || $wid === false) {
        $wid = $db->getOne("SELECT id FROM words WHERE name = ?s", $word);
    }
    return findlemma($wid, $props);
}


function findlemma($word_id, $props = null)
{
    //print $word_id."\n";
    global $db_conf, $redis;
    $db = new \My\Simpledb($db_conf);
    if (!$redis) {
        $post = $db->getInd('name', "SELECT name, id FROM `grammemes` WHERE name != ?s", 'POST');

        $post = array_combine(array_column($post, 'id'), array_column($post, 'name'));
        if ($props !== null) {
            $q = "SELECT f.id, w.name, f.props FROM `forms_uni` f 
        INNER JOIN `words` w ON f.word_id = w.id
        WHERE 1 AND f.id in (SELECT id FROM forms_uni WHERE word_id = ?i) AND f.props = '$props'";
        } else {
            $q = "SELECT f.id, w.name, f.props FROM `forms_uni` f 
        INNER JOIN `words` w ON f.word_id = w.id
        WHERE 1 AND f.id in (SELECT id FROM forms_uni WHERE word_id = ?i)";
        }
        $res = $db->getAll($q, $word_id);
        foreach ($res as &$r) {
            $r['props'] = array_intersect_key($post, array_flip(explode('|', $r['props'])));
        }
        return $res;
    }

    $success = false;
    $content = redis_get('w' . $word_id, $success);
    if ($success) {
        $content = msgpack_unpack($content)[1];
    }

    $res = array();
    if (!$success) {
        exit("not found!\n");
    }

    foreach ($content as $lid => $val) {
        $success = false;
        $lemma = redis_get('l' . $lid, $success);
        if ($success) {
            $lemma = msgpack_unpack($lemma);
            foreach ($lemma[2] as $wid => $props) {
                array_walk($props, function (&$gid) {
                    $gid = msgpack_unpack(redis_get('g' . $gid))['alias'];
                });
                $word = msgpack_unpack(redis_get('w' . $wid));
                $res[$lid][$word[0]] = $props;
            }
        }
    }

    return $res;
}


function cache_get($key, &$success = false)
{
    if (@include cache_key2path($key)) {
        $success = true;
        return $val;
    } else {
        return false;
    }
}

function cache_set($key, $val)
{
    dtimer::log(__LINE__ . ' cache_key2path');
    $path = cache_key2path($key);
    dtimer::log(__LINE__ . ' cache_key2path end');
    $val = var_export($val, true);
    dtimer::log(__LINE__ . ' var_export end');
    // HHVM fails at __set_state, so just use object cast for now
    dtimer::log(__LINE__ . ' str_replace');
    $val = str_replace('stdClass::__set_state', '(object)', $val);
    dtimer::log(__LINE__ . ' str_replace end');
    // Write to temp file first to ensure atomicity
    //$tmp = $path . uniqid('', true) . '.tmp';
    $tmp = $path . '.tmp';
    $dirname = dirname($path);

    dtimer::log(__LINE__ . ' if file_exists');
    if (!file_exists($dirname)) {
        dtimer::log(__LINE__ . ' if file_exists end');
        mkdir($dirname, 0777, true);
        dtimer::log(__LINE__ . ' mkdir end');
    }
    dtimer::log(__LINE__ . ' file_put_contents');
    file_put_contents($tmp, '<?php $val = ' . $val . ';', LOCK_EX);
    dtimer::log(__LINE__ . ' file_put_contents end');
    dtimer::log(__LINE__ . ' cache_set function end');
    return rename($tmp, $path);
}


function cache_key2path($key)
{
    $key = rawurlencode($key);
    $len = strlen($key);
    switch ($len) {
        case 0:
            return false;

        case 1:
            return CACHEDIR . $key . '/' . $key . '.txt';

        default:
            return CACHEDIR . implode('/', str_split($key, 4)) . '.txt';
    }
}

function cache_path2key($path)
{
    if (strpos($path, CACHEDIR) !== false) {
        $path = substr($path, 0, strlen($path) - 4);
        $filename = substr($path, strlen(CACHEDIR), 9999);
        return rawurldecode(str_replace('/', '', $filename));
    } else {
        return false;
    }
}

function redis_get($key, &$success = false)
{
    global $redis;
    $res = $redis->get($key);
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
