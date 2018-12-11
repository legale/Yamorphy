<?php

require_once('config.php' );
require_once('Xmlparse.php' );




$x = new Xmlparse();


$name = 'grammeme';
$res = $x->xmlnodes2array($xmlfile, $name);

$name = 'restr';
$res = $x->xmlnodes2array($xmlfile, $name);

$name = 'lemma';
$res = $x->xmlnodes2array($xmlfile, $name);

