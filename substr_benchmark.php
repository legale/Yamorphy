<?php

$cnt = 100;
$str = str_repeat("приветдорогойдруг123",100000);
$time = microtime(true);

$i = 0;
while($i < $cnt) {
    iconv_substr($str, 1000, 90000, 'UTF-8');
    $i++;
}
echo "iconv_substr: " . (microtime(true)-$time) . "\n";

$time = microtime(true);
$i = 0;
while($i < $cnt) {
    mb_substr($str, 1000, 90000, 'UTF-8');
    $i++;
}
echo "mb_substr: " . (microtime(true)-$time) . "\n";

$time = microtime(true);
$i = 0;
while($i < $cnt) {
    substr($str, 1000, 90000);
    $i++;
}
echo "substr: " . (microtime(true)-$time) . "\n";
?>