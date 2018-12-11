<?php

//this one is faster
function lcs($first, $second)
{
    if($first === "" || $second === "") return "";
    $len1 = strlen($first);
    $len2 = strlen($second);

    if ($len1 < $len2) {
        $shortest = $first;
        $longest = $second;
        $len_shortest = $len1;
    } else {
        $shortest = $second;
        $longest = $first;
        $len_shortest = $len2;
    }

    //check max len
    $pos = strpos($longest, $shortest);
    if($pos !== false) return $shortest;

    for ($i = 1, $j = $len_shortest - 1; $j > 0; --$j, ++$i) {
        for($k = 0; $k <= $i; ++$k){
            $substr = substr($shortest, $k, $j);
            $pos = strpos($longest, $substr);
            //if found then check last char if it is > 207 (0xCF) then trim it
            if($pos !== false) return ord($substr[$j-1]) > 207 ? substr($substr, 0, $j - 1) : $substr;
        }
    }

    return "";
}

