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
    while ($i < $cnt) {
        $i++;
        $res = eval($str);
    }
    $end = microtime(true) - $start;
    return $end;
}

function bracket_checker($str){
    $brackets = [array_flip(["(", "[", "{"]), array_flip([")", "]", "}"])];
    $len = strlen($str);
    $opened = "";
    for ($i = 0; $i < $len; ++$i) {
        if (isset($brackets[0][$str[$i]])) {
            $opened .= $brackets[0][$str[$i]];
        } else if (isset($brackets[1][$str[$i]])) {
            if ($opened === "" || (int)substr($opened, -1) !== $brackets[1][$str[$i]]) return false;
            $opened = substr($opened, 0, -1);
        }
    }
    return empty($opened);
}

function bracket_checker2($str)
{
    $brackets = [array_flip(["(", "[", "{"]), array_flip([")", "]", "}"])];
    $opened = [];
    for ($i = 0,$len = strlen($str); $i < $len; ++$i) {
        if (isset($brackets[0][$str[$i]])) {
            array_push($opened,$brackets[0][$str[$i]]);
        } else if (isset($brackets[1][$str[$i]])) {
            if ($opened[sizeof($opened)-1] !== $brackets[1][$str[$i]]){
                return false;
            }
            array_pop($opened);
        }
    }
    return empty($opened);
}



$dataset = [
    "({}{}{}{}{}[]{}[])",
    "{(}{}{}{}{}[]{}[])",
    "({sdfsdgfdbdfb}{dfsdvscv}[dfsdsddsfsvcxvdfgdfg])",
    '{"location":{"href":"http://php.net/manual/ru/function.substr-count.php","ancestorOrigins":{},"origin":"http://php.net","protocol":"http:","host":"php.net","hostname":"php.net","port":"","pathname":"/manual/ru/function.substr-count.php","search":"","hash":""},"changelang":{"0":{},"1":{"0":{},"1":{},"2":{},"3":{},"4":{},"5":{},"6":{},"7":{},"8":{},"9":{},"10":{}}}}',

];
$cycles = 10000;

$f1 = "bracket_checker";
$f2 = "bracket_checker2";

print str_pad($f1, 25) . bmark($f1, $cycles, $dataset[3]) . PHP_EOL;
print str_pad($f2, 25) . bmark($f2, $cycles, $dataset[3]) . PHP_EOL;
print str_pad($f1, 25) . bmark($f1, $cycles, $dataset[3]) . PHP_EOL;
print str_pad($f2, 25) . bmark($f2, $cycles, $dataset[3]) . PHP_EOL;

