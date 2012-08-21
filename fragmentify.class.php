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

        private function __construct($path) {
            assert(is_string($path));
            $this->path = $path;
            $this->doctype = null;
        }

        public function getPath() {
            return $this->path;
        }

        public function getProcessed() {
            return $this->processed;
        }

        public function process() {
            $x = $this->getXPath();
            $html = $this->getHtml();
            
            if($html) {
                $base = $html->getAttribute('base');
            }
            else {
                $base = '';
            }

            if($base === '') {
                $this->processRequires($this->doc->documentElement,
                    dirname($this->path));
            }
            else {
                $fn = realpath(dirname($this->path).'/'.$base);
                $base = new Fragmentify($fn);
                $this->createBaseDoc($base);
                $this->populateBaseDoc();
                $this->processRequires($this->base_doc->documentElement,
                    dirname($fn));
                $this->doctype = $base->doctype;
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
                        $this->processRequires($i);
                    }
                }
                else if($xp = $node->getAttribute('append')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('append');
                        $t->appendChild($i);
                        $this->processRequires($i);
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
                        $this->processRequires($i);
                    }
                }
                else if($xp = $node->getAttribute('before')) {
                    $ts = $this->getTargets($xp);
                    foreach($ts as $t) {
                        $i = $this->base_doc->importNode($node, true);
                        $i->removeAttribute('before');
                        $t->parentNode->insertBefore($i,$t);
                        $this->processRequires($i);
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
                        $this->processRequires($i);
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
                        $this->processRequires($i);
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
                $this->error('selector "'.$xpath.
                    '" did not match anything in base template '.
                    $this->getHtml()->getAttribute('base'));
                exit(1);
            }
            foreach($t as $node) {
                if(!$node->parentNode) {
                    $this->error('cannot manipulate root node (selector was "'.
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

        private function stripDoctype($m){
            $this->doctype = $m[1];
            return '';
        }

        public function getDoc() {
            if($this->doc == null) {
                $this->doc = new DomDocument();
                $data = file_get_contents($this->path);
                $data = preg_replace_callback('/<!doctype([^>]*)>/',
                    'self::stripDoctype', $data);
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
                $ret = $f->base_doc->saveXML($f->base_doc->documentElement, 
                    LIBXML_NOEMPTYTAG);
            }
            else {
                $ret = $f->getDoc()->saveXML($f->getDoc()->documentElement,
                    LIBXML_NOEMPTYTAG);
            }
            if($f->doctype) {
                $ret = '<!doctype'.$f->doctype.'>'.PHP_EOL.$ret;
            }
            return $ret;
        }

        public function getDocumentElement() {
            //assert($this->processed);
            if(!$this->processed) {
                $this->process();
            }
            if($this->base_doc) {
                return $this->base_doc->documentElement;
            }
            else {
                return $this->getDoc()->documentElement;
            }
        }

        private function processRequires($node, $path=null) {
            if($path == null) {
                $path = dirname($this->path);
            }
            if($node->getAttribute('require')) {
                $this->processRequire($node, $path);
            }
            $x = $this->getXPath();
            foreach($x->query('.//*[@require]', $node) as $sub) {
                $this->processRequire($sub, $path);
            }
        }

        public function processRequire($node, $dir) {
            $fn = $dir.'/'.$node->getAttribute('require');
            $query = $node->getAttribute('xpath');
            
            if($query === '') {
                $query = '//fragment/node()';
            }
           
            $f = new Fragmentify($fn);
            $f->process();
            $x = $f->getXPath();

            $reqs = $x->query('//*[@require]');

            $subdir = dirname($f->path);

            foreach($reqs as $sub) {
                $f->processRequire($sub, $subdir);
            }

            $to_import = $x->query($query);
            
            if(!$to_import->length) {
                $this->error('selector "'.$query.'" did not match any nodes in required file '.$fn);
                exit(1);
            }
            
            foreach($to_import as $import) {
                $import = $node->ownerDocument->importNode($import,true);
                $node->parentNode->insertBefore($import,$node);
            }
            
            $node->parentNode->removeChild($node);
        }

        private function error($msg) {
            error_log('while processing '.$this->path.':'.PHP_EOL.$msg);
        }
    }
