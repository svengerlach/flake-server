<?php

namespace Svengerlach\FlakeServer\Console;

use Svengerlach\FlakeServer\Console\Command\RunCommand;

class Application extends \Symfony\Component\Console\Application
{
    
    public function __construct($name = 'flakeserver', $version = '1.0')
    {
        parent::__construct($name, $version);
        $this->add(new RunCommand());
    }

}