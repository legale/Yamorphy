<?php
function bmark() {
    $args = func_get_args();
    $len = count($args);

    if ($len < 3) {
        trigger_error("At least 3 args expected. Only $len given.", 256);
        return false;
    }

    $fun = array_shift($args);
    $cnt = array_shift($args);

    $start = microtime(true);
    $i = 0;
    $args = array_map(function ($e) {
        return var_export($e, true);
    }, $args);
    $str = "$fun(" . implode(', ', $args) . ");";
    print $str;
    while ($i < $cnt) {
        $i++;
        $res = eval($str);
    }
    $end = microtime(true) - $start;
    return $end;
}

