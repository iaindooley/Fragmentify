<?php
    //note the second argument here is major, minor, patch
    //versions
    RocketPack\Install::package('https://github.com/iaindooley/Fragmentify',array(0,2,1));

    RocketPack\Dependencies::register(function()
    {
        RocketPack\Dependency::forPackage('https://github.com/iaindooley/Fragmentify')
        ->add('https://github.com/iaindooley/Args',array(0,2,1))
        ->verify();
    });
