<?php
    //note the second argument here is major, minor, patch
    //versions
    rocketpack\Install::package('https://github.com/iaindooley/Fragmentify',array(0,2,0));

    rocketpack\Dependencies::register(function()
    {
        rocketpack\Dependency::forPackage('https://github.com/iaindooley/Fragmentify')
        ->add('https://github.com/iaindooley/Args',array(0,2,0))
        ->verify();
    });
