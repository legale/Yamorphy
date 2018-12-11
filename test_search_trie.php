<?php
if (!isset($argv[1])) {
    exit("enter a word to search\n");
}

require_once("bmark.php");


function traverse_head($trie_suf, $trie_root, $suf_node_id, $head){
    $head_nodes = yatrie_get_word_nodes($trie_root, implode("",$head));
    $head_letters = count($head_nodes);
    if ($head_letters) {
        $suffix_paradygms = yatrie_node_traverse($trie_suf, yatrie_get_id($trie_suf, "$", $suf_node_id));
        for ($j = $head_letters; $j > 0; --$j) {
            $head_node_id = $head_nodes[$j - 1];
            if ($head_node_id <= 0) continue; //если листа на этой букве нет, пропускаем итерацию
            $root = array_slice($head, 0, $j);
//            $root_str = implode("", $root);
//            $prefix = array_slice($head, $j);
//            $prefix_str = implode("", $prefix);
//            printf("\t\troot:'%s' prefix:%s node_id:%d \n", $root_str, $prefix_str, $head_node_id);
            $found_paradygms = yatrie_node_traverse($trie_root, yatrie_get_id($trie_root, "$", $head_node_id));
            $res = array_intersect($found_paradygms, $suffix_paradygms);
            if($res) break;
        }
    } else { //если ни одной буквы не найдено, проверяем пустоту ""
        $found_paradygms = yatrie_node_traverse($trie_root, yatrie_get_id($trie_root, "$"));
        $suffix_paradygms = yatrie_node_traverse($trie_suf, yatrie_get_id($trie_suf, "$", $suf_node_id));
        $res = array_intersect($found_paradygms, $suffix_paradygms);
    }
    return $res ? $res : null;
}


function get_paradygm ($trie_suf, $trie_root, $keyword){
    $kw_rev_str = yatrie_strrev($keyword);
    $kw_rev = yatrie_str_split($kw_rev_str);


    $suf_nodes = yatrie_get_word_nodes($trie_suf, $kw_rev_str);
    $suffix_letters = count($suf_nodes);
    if ($suffix_letters) {
        for ($i = $suffix_letters; $i > 0; --$i) {
            $suf_node_id = $suf_nodes[$i - 1];
            //если узел без листа - пропустим цикл
            if ($suf_node_id <= 0) continue;

//            $suffix = array_slice($kw_rev, 0, $i);
//            $suffix_str = implode("", $suffix);
            $head = array_slice($kw_rev, $i); //это оставшаяся часть слова префикс + корень
//            $head_str = implode("", $head);
//            printf("suffix:'%s' head:'%s' node_id:%d \n", $suffix_str, $head_str, $suf_node_id);
            $res = traverse_head($trie_suf, $trie_root, $suf_node_id, $head);
            if($res) break;
        }
    }
    return $res ?? null;
}



$trie_suf = yatrie_load("suffix.trie"); //дерево суффиксов
$trie_root = yatrie_load("root.trie"); //дерево корней

$kw_str = $argv[1];

$res = get_paradygm($trie_suf, $trie_root, $kw_str);
if(!$res) {
    //проверяем пустой суффикс, если ничего не нашлось, в качестве head будет слово целиком
    $res = traverse_head($trie_suf, $trie_root, 0, $kw_rev);
}

if(!$res) exit("word not found: " . $kw_str . PHP_EOL);
print_r($res);
echo PHP_EOL;
