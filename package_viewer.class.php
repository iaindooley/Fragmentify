<?php
    namespace Fragmentify;
    use Fragmentify,Args,DOMDocument,DOMXPath;
    
    class PackageViewer implements \RocketSled\Runnable
    {
        public function run()
        {
            if(!$package = Args::get('package',$_GET,Args::argv))
                die('You need to include the name of a package in the query string like package=my_package');
            else if(!is_dir(PACKAGES_DIR.'/'.$package))
                die(PACKAGES_DIR.'/'.$package.' does not exist');
            
            if($file = Args::get('file',$_GET,Args::argv))
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

                foreach(self::directoryList(PACKAGES_DIR.'/'.$package) as $fn)
                {
                    if(self::endsWith($fn,'.html'))
                        $ul->item(0)->appendChild($this->createLi($package,$fn,$li));
                }
                
                echo $doc->saveXML();
            }
        }
        
        
        public static function endsWith($str,$test)
        {
            return (substr($str, -strlen($test)) == $test);
        }

        /**
        * Courtesy of donovan dot pp at gmail dot com on http://au2.php.net/scandir
        */
        public static function directoryList($dir)
        {
           $path = '';
           $stack[] = $dir;
    
           while ($stack)
           {
               $thisdir = array_pop($stack);
    
               if($dircont = scandir($thisdir))
               {
                   $i=0;
    
                   while(isset($dircont[$i]))
                   {
                       if($dircont[$i] !== '.' && $dircont[$i] !== '..')
                       {
                           $current_file = "{$thisdir}/{$dircont[$i]}";
    
                           if (is_file($current_file))
                               $path[] = "{$thisdir}/{$dircont[$i]}";
                           else if(is_dir($current_file))
                           {
                               $path[] = "{$thisdir}/{$dircont[$i]}";
                               $stack[] = $current_file;
                           }
                       }
    
                       $i++;
                   }
               }
           }
    
           return $path;
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
