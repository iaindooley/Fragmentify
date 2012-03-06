<?php
    class Fragmentify {
        private $processed = false;
        private $path = null;

        private $html = null;
        private $base_html = null;
        private $doc = null;
        private $base_doc = null;
        private $xpath = null;
        private $base_xpath = null;

        private function __construct($path)
        {
            assert(is_string($path));
            $this->path = $path;
        }

        public function getPath() {
            return $this->path;
        }

        public function getProcessed() {
            return $this->processed;
        }

        public function process()
        {
            $x = $this->getXPath();
            $html = $this->getHtml();
            
            if($html)
                $base = $html->getAttribute('base');
            else
                $base = '';

            $reqs = $x->query('//*[@require]');
            
            if($base !== '')
            {
                $fn = realpath(dirname($this->path).'/'.$base);
                $this->createBaseDoc(new Fragmentify($fn));
                $this->populateBaseDoc();
                $x = $this->getXPath();
                $reqs = $x->query('//*[@require]');
                
                //hacking this in here because its not getting passed in as an
                //arg any more
                //$rootdir = realpath('./src');
                $rootdir = dirname($this->path);
                
                foreach($reqs as $req)
                    //$this->processRequire($rootdir,$req,$processed);
                    $this->processRequire($rootdir,$req);
            }
        }

        private function populateBaseDoc() {
            assert(is_object($this->base_doc));
            assert(is_object($this->getHtml()));
            foreach($this->getHtml()->childNodes as $node) {
                if($node->nodeType == 3 || $node->nodeType == 8) {
                    continue;
                }
                if($xp = $node->getAttribute('replace')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('replace');
                        $t->parentNode->insertBefore($i, $t);
                        if($node->getAttribute('keep-contents') == 'true') {
                            $i->removeAttribute('keep-contents');
                            while($t->firstChild) {
                                $i->appendChild($t->firstChild);
                            }
                        }
                        $t->parentNode->removeChild($t);
                    }
                }
                else if($xp = $node->getAttribute('append')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('append');
                        $t->appendChild($i);
                    }
                }
                else if($xp = $node->getAttribute('prepend')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('prepend');
                        if($t->firstChild) {
                            $t->insertBefore($i,$t->firstChild);
                        }
                        else {
                            $t->appendChild($i);
                        }
                    }
                }
                else if($xp = $node->getAttribute('before')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('before');
                        $t->parentNode->insertBefore($i,$t);
                    }
                }
                else if($xp = $node->getAttribute('after')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('after');
                        if($t->nextSibling) {
                            $t->parentNode->insertBefore($i,$t->nextSibling);
                        }
                        else {
                            $t->parentNode->append($i);
                        }
                    }
                }
                else if($xp = $node->getAttribute('surround')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('surround');
                        $i->removeAttribute('where');
                        $t->parentNode->insertBefore($i,$t);
                        $where = $node->getAttribute('where');
                        switch($where) {
                            case 'top':
                                if($i->firstChild) {
                                    $i->insertBefore($t,$i->firstChild);
                                }
                                else {
                                    $i->appendChild($t);
                                }
                                break;
                            default:
                                $i->appendChild($t);
                                break;
                        }
                    }
                }
                else if($xp = $node->getAttribute('merge')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('merge');
                        foreach($i->attributes as $att) {
                            $t->setAttribute($att->name,$att->value);
                        }
                    }
                }
                else if($xp = $node->getAttribute('remove')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $t->parentNode->removeChild($t);
                    }
                }
            }
        }

        private function getTargets($xpath) {
            $t = $this->base_xpath->query($xpath);
            if(!$t->length) {
                error_log('selector "'.$xpath.'" did not match anything in '.
                    $this->getHtml()->getAttribute('base'));
                exit(1);
            }
            foreach($t as $node) {
                if(!$node->parentNode) {
                    error_log('cannot manipulate root node (selector was "'.
                        $xpath.'")');
                    exit(1);
                }
            }
            return $t;
        }

        private function createBaseDoc($src) {
            //assert($src->getProcessed());
            $this->base_doc = new DomDocument();
            $this->base_doc->appendChild($this->base_doc->importNode(   
                $src->getDocumentElement(), true));
            $this->base_xpath = new DomXPath($this->base_doc);
        }

        public function getDoc() {
            if($this->doc == null) {
                $this->doc = new DomDocument();
                $data = file_get_contents($this->path);
                $data = str_replace('&','&amp;',$data);
                $this->doc->loadXML($data);
            }
            return $this->doc;
        }

        private function getHtml() {
            if($this->html == null) {
                $this->html = $this->getXPath()->query('//html')->item(0);
                if(!$this->html) {
                    $this->html = false;
                }
            }
            return $this->html;
        }

        private function getXPath() {
            if($this->base_doc) {
                $d = $this->base_doc;
            }
            else {
                $d = $this->getDoc();
            }
            if($this->xpath == null || $this->xpath->document !== $d) {
                $this->xpath = new DomXPath($d);
            }
            return $this->xpath;
        }

        public static function render($path) {
            $f = new Fragmentify($path);
            $f->process();
            
            if($f->base_doc) {
                $ret = $f->base_doc->saveXML($f->base_doc,LIBXML_NOEMPTYTAG);
            }
            else {
                $ret = $f->getDoc()->saveXML($f->getDoc(),LIBXML_NOEMPTYTAG);
            }
            /*
            <?xml version="1.0" encoding="UTF-8"?>
            */
            $ret = preg_replace('/<\?xml[^?]*\?>/','',$ret);
            return $ret;
        }

        public function getDocumentElement() {
            //assert($this->processed);
            $this->process();
            if($this->base_doc) {
                return $this->base_doc->documentElement;
            }
            else {
                return $this->getDoc()->documentElement;
            }
        }

        //public function processRequire($rootdir, $req, $processed) {
        public function processRequire($rootdir, $req) {
            $fn = $rootdir.'/'.$req->getAttribute('require');
            $query = $req->getAttribute('xpath');
            if($query === '') {
                $query = '//fragment/node()';
            }
            //$x = $processed[$fn]->getXPath();
            $f = new Fragmentify($fn);
            $x = $f->getXPath();
            $to_import = $x->query($query);
            if(!$to_import->length) {
                error_log('selector "'.$query.'" in '.$this->path.' for '.
                    $fn.' did not match any nodes');
                exit(1);
            }
            foreach($to_import as $node) {
                $node = $req->ownerDocument->importNode($node,true);
                $req->parentNode->insertBefore($node,$req);
            }
            $req->parentNode->removeChild($req);
        }
    }
