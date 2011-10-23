<?php
    //note the second argument here is major, minor, patch
    //versions
    rocketpack\Install::package('Fragmentify',array(0,0,0));

    rocketpack\Dependencies::register(function()
    {
        rocketpack\Dependency::forPackage('Fragmentify')
        ->add('Args',array(0,1,0))
        ->verify();
    });
