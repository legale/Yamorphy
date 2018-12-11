<?php

/**
 * Class Xmlparse
 */
class Xmlparse
{

    public $handle;
    public $dom;

    public function __construct($filepath = null)
    {
        if (isset($filepath)) {
            $this->handle = $this->xml_open($filepath);
        }
    }

    public function dom_load($xmlfile)
    {
        $this->dom = $dom = new DOMDocument();
        $dom->load($xmlfile);
        return $dom;
    }

    private function str_pos ($str, $needle, $offset = 0){
        $len = strlen($str);
        while($offset < $len){
            if($str[$offset] === $needle){
                return $str;
            }
            ++$offset;
        }
        return false;
    }

    public function get_tags(&$str, $offset = 0, &$recurse = null)
    {

        $start = strpos($str, '<', $offset); //first find '<' pos
        if ($start !== false) { //if found
            $end = strpos($str, '>', $start + 1); // try to find '>' from second char (start offset + 1 sym)
            if ($end !== false) { //if found
                $range = substr($str, $start + 1, $end - $start - 1); //get substring from second char (start offset + 1) sym to $end
                $space = strpos($range, ' '); //find first space in $range
                $tag_name = $space !== false
                    ? substr($range, 0, $space) : //if space found get substring from first sym to the last ($space)
                    $tag_name = $range; //else get all range
            }
            if ($recurse === null || !key_exists($tag_name, $recurse)) {
                $recurse[$tag_name] = 1;
            } else {
                $recurse[$tag_name] += 1;
            }

            return $this->get_tags($str, $end + 1, $recurse);
        } else {
            return $recurse ? $recurse : array();
        }
    }

    public function write_tags($tags, &$res)
    {
        foreach ($tags as $tag => $count) {
            if (key_exists($tag, $res)) {
                $res[$tag] += $count;
            } else {
                $res[$tag] = $count;
            }
        }
        return true;
    }

    private function read_n_parse(&$h, $len, &$array)
    {
        $time_init = $time_start = microtime(true);
        $filesize = fstat($h)['size'];
        do {
            $str = fread($h, $len) . fgets($h); //read first range + 1 string
            $offset = ftell($h); //save absolute cursor position
            $time_end = microtime(true);
            $time = $time_end - $time_start;
            $time_start = $time_end;
            print "\n" . round($len / $time) . "bytes/sec " . $offset / $filesize * 100;
            $this->write_tags($this->get_tags($str), $array);
        } while (!feof($h));
    }


    public function count_nodes($xmlfile)
    {
        $res = array();
        $h = fopen($xmlfile, 'r');
        $len = 256;
        $this->read_n_parse($h, $len, $res);


        return $res;
    }


    public function dom_load_string($xmlstring)
    {
        $this->dom = $dom = new DOMDocument();
        $dom->loadXML($xmlstring);
        return $dom;
    }

    public function getxsdstruct($xmlfile = null)
    {
        if ($xmlfile && gettype($xmlfile) === 'string') {
            $dom = $this->dom_load($xmlfile);
        } elseif ($this->dom) {
            $dom = $this->dom;
        }


        //получаем узлы element
        $elem_list = $dom->getElementsByTagName('element');

        //перебираем их, создавая дерево элементов
        foreach ($elem_list as $el) {
            $el_name = $el->getAttribute('name');
            $el_type = $el->getAttribute('type');
            if ($el->parentNode->tagName === 'xs:sequence') {
                $parent = $el->parentNode->parentNode->getAttribute('name');
            } else {
                $root = $el_type;
                $parent = '';
            }
            $level = isset($names[$parent]['level']) ? $names[$parent]['level'] + 1 : 1;
            $names[$el_type] = array('name' => $el_name, 'level' => $level);
            $names[$parent]['children'][$el_name] = &$names[$el_type];
        }

        //возвращаем дерево
        return $names[$root];
    }


    public function xpath($xmlfile = null)
    {
        if ($xmlfile && gettype($xmlfile) === 'string') {
            $dom = $this->dom_load($xmlfile);
        } elseif ($this->dom) {
            $dom = $this->dom;
        }

        $xpath = new DOMXPath($dom);

        return $xpath;
    }

    public function xml2array($xmlfile)
    {
        $res = array();
        $p = xml_parser_create();
        xml_parse_into_struct($p, file_get_contents($xmlfile), $res['vals'], $res['index']);
        xml_parser_free($p);
        return $res;
    }

    /**
     * @param $xmlfile
     * @return XMLReader
     */
    public function xml_open($xmlfile)
    {
        $this->handle = new XMLReader;
        return $this->handle->open($this->uncompress($xmlfile)) ? $this->handle : false;
    }

    public function uncompress($src){
        if(!is_readable($src)){
            return false;
        }

        $pinfo =  pathinfo($src);
        $ext = strtolower($pinfo['extension']);
        $file = $pinfo['filename'];
        $dir = $pinfo['dirname'];

        switch($ext) {
            case 'xml':
                return $src;
                break;

            case 'bz2':
                $open = 'bzopen';
                $read = 'bzread';
                $close = 'bzclose';
                if(!function_exists($open)){
                    exit("you need to install php $ext extension first\n");
                }
                break;

            case 'gz':
                $open = 'gzopen';
                $read = 'gzread';
                $close = 'gzclose';
                if(function_exists($open)){
                    exit("you need to 'sudo apt install php.$ext' extension first\n");
                }
                break;
        }
        $dst = $dir.'/'.$file;
        $fopen = fopen($dst, "w");
        $handle = $open($src, 'r');
        if(!is_resource($handle) || !is_resource($fopen)){
            @unlink(dst);
            return false;
        }

        while (!feof($handle)) {
            $data = $read($handle, 4097152);
            fwrite($fopen, $data);
        }
        $close($handle);
        fclose($fopen);


        return $dst;
    }

    public function node_goto($name)
    {
        do {
            if ($this->handle->read() === false) {
                return false;
            }
            if ($this->handle->name === $name) {
                return true;
            }
        } while (1);
    }

    public function node_next($name)
    {
        return $this->handle->next($name);
    }

    public function read_raw()
    {
        return $this->handle->readOuterXML();
    }

    public function xmlnodes2array($xmlfile, $name)
    {
        $dirname = dirname(__FILE__).'/serialized/';
        $this->xml_open($xmlfile);
        $this->node_goto($name);
        $res = array();
        $i = 0;
        $n = 0;
        do {
            //print $i . ' ' . memory_get_usage(true) . PHP_EOL;
            $element = new SimpleXMLElement($this->read_raw());
            $res[$i] = $this->loop_xml_element($element);
            $i++;
            if ($i % 5000 === 0) {
                file_put_contents($dirname . $name . '_' . $n, serialize($res));
                $n++;
                //unset($res);
                print $i . ' before gc: ' . memory_get_usage(true) . PHP_EOL;
                $res = array();
                gc_mem_caches();
                print "cycle references: " . gc_collect_cycles() . PHP_EOL;
                print $i . ' after gc: ' . memory_get_usage(true) . PHP_EOL;
            }
        } while ($this->node_next($name));
        file_put_contents($dirname . $name . '_' . $n, serialize($res));
        return true;
    }


    function loop_xml_element($el, $array = null)
    {
        if ($array === null) {
            $array = array();
        }
        $array[0] = $el->getName();
        $attribs = (array)$el->attributes();
        $attribs = isset($attribs['@attributes']) ? $attribs['@attributes'] : array();
        $array[1] = &$attribs;
        if ($el->count() === 0) {
            $array[2] = (string)$el;
        } else {
            $i = 0;
            foreach ($el as $name => $val) {
                $array[2][$name . "_" . $i] = $this->loop_xml_element($val);
                $i++;
            }
        }
        return $array;
    }
}

