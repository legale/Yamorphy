<?php

//dictionary src http://opencorpora.org/dict.php
$rootdir = dirname(__FILE__);
$xmldir = $rootdir . '/xml/';
$xmlfile = $xmldir . 'dict.opcorpora.xml.bz2';

//serilized files dirname
$serialized = $rootdir . '/serialized/';


//db connection params
$db_conf = array(
    'db' => 'corpora',
    'host' => '127.0.0.1',
    'user' => 'root',
    'pass' => ''
);

//if true existing db will be DESTROYED and created again
$db_create = true;
