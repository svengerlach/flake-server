<?php

namespace Svengerlach\FlakeServer\Console\Command;

use Symfony\Component\Console\Command\Command, 
    Symfony\Component\Console\Input\InputOption, 
    Symfony\Component\Console\Input\InputInterface, 
    Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Run the flake-server')
            ->addOption(
                'ip', 
                null, 
                InputOption::VALUE_OPTIONAL, 
                'IP the server listens on', 
                getenv('FLAKESERVER_IP') ?: '0.0.0.0'
            )
            ->addOption(
                'port', 
                null, 
                InputOption::VALUE_OPTIONAL, 
                'Port the server listens on', 
                getenv('FLAKESERVER_PORT') ?: 9944
            )
            ->addOption(
                'node-identifier', 
                null, 
                InputOption::VALUE_OPTIONAL, 
                'Node identifier (integer between 0 and 1023)', 
                getenv('FLAKESERVER_NODE_IDENTIFIER') ?: 0
            )
            ->addOption(
                'epoch-start', 
                null, 
                InputOption::VALUE_OPTIONAL, 
                'Epoch start (in microseconds)', 
                getenv('FLAKESERVER_EPOCH_START') ?: 1470088800000
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timer = new \Svengerlach\Flake\Timer();
        $sequencer = new \Svengerlach\Flake\Sequencer();
        
        $generator = new \Svengerlach\Flake\Generator(
            $timer, 
            $sequencer, 
            $input->getOption('epoch-start'), 
            $input->getOption('node-identifier')
        );
        
        $loop = \React\EventLoop\Factory::create();

        $socket = new \React\Socket\Server($loop);
        $socket->on('connection', function (\React\Socket\Connection $conn) use ($generator) {
            $conn->on('data', function ($data) use ($conn, $generator) {
                $data = trim($data);
                
                if ( 0 !== strpos($data, 'GET ') ) {
                    return $conn->close();
                }
                
                $parameter = substr($data, 4);
                 
                for ( $i = 0; $i < (int) $parameter; $i++ ) {
                    $flake = $generator->generate();
                    $conn->write($flake . "\n");
                }
                
                $conn->end();
            });
        });

        $socket->listen($input->getOption('port'), $input->getOption('ip'));

        $loop->run();
    }

}