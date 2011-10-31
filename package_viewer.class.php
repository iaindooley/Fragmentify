<?php
    namespace fragmentify;
    use Fragmentify,Args,DOMDocument,DOMXPath;
    
    class PackageViewer implements \rocketsled\Runnable
    {
        public function run()
        {
            if(!$package = Args::get('package',$_GET))
                die('You need to include the name of a package in the query string like package=my_package');
            else if(!is_dir(PACKAGES_DIR.'/'.$package))
                die(PACKAGES_DIR.'/'.$package.' does not exist');
            
            if($file = Args::get('file',$_GET))
            {
                $realpackages = realpath(PACKAGES_DIR);
                $realfile     = realpath($file);
                
                if(strpos($realfile,$realpackages) === 0)
                    echo Fragmentify::render($file);

                exit(1);
            }
            
            else
            {
                $doc = DOMDocument::loadHTMLFile(dirname(__FILE__).'/packages_list.html');
                $xpath = new DOMXPath($doc);
                $ul = $xpath->query('.//ul[@id="packagesList"]');
                $li = $ul->item(0)->childNodes->item(0);//->childNodes[0];
                
                while ($ul->item(0)->hasChildNodes())
                    $ul->item(0)->removeChild($ul->item(0)->childNodes->item(0));

                foreach(explode(PHP_EOL,trim(shell_exec('find '.PACKAGES_DIR.'/'.$package.' -name "*.html"'))) as $fn)
                    $ul->item(0)->appendChild($this->createLi($package,$fn,$li));
                
                echo $doc->saveXML();
            }
        }
        
        public function createLi($package,$fn,$tpl)
        {
            $new = $tpl->cloneNode(TRUE);
            
            foreach($new->childNodes as $cn)
            {
                if($cn->nodeType == 1)
                {
                    $cn->setAttribute('href',$_SERVER['SCRIPT_NAME'].'?r=fragmentify\PackageViewer&package='.$package.'&file='.$fn);
                    $cn->nodeValue = $fn;
                    $ret = $cn;
                }
            }
            
            return $new;
        }
    }
