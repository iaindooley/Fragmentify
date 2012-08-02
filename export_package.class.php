<?php
    class ExportPackage implements RocketSled\Runnable
    {
        public function run()
        {
            if(!$src = Args::get('src',Args::argv))
                exit(1);
            if(!$dst = Args::get('dst',Args::argv))
                exit(1);
            
            if(!is_dir($dst))
                mkdir($dst);

            foreach(glob($src.'/*.html') as $fname)
                file_put_contents($dst.'/'.basename($fname),Fragmentify::render($fname));
            
            echo 'done! Now copy across any assets (images, css, javascript etc.) manually'.PHP_EOL;
        }
    }
