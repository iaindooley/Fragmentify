<?php
    namespace fragmentify;
    use Fragmentify;
    
    class PackageViewer implements \rocketsled\Runnable
    {
        public function run()
        {
            if(!$package = Args::get('package',$_GET))
                die('You need to include the name of a package in the query string like package=my_package');
            else if(!is_dir(PACKAGES_DIR.'/'.$package))
                die($package.' does not exist');
            
            if($file = Args::get('file',$_GET))
            {
                echo Fragmentify::render(PACKAGES_DIR.'/'.$package.'/'.$file);
                exit(1);
            }
            
            else
            {
                $doc = DOMDocument::loadHTML(dirname(__FILE__).'/packages_list.html');
                die($doc->saveXML());
            }
        }
    }
