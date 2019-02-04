<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.19
 * Time: 10:59
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\testrail\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestRailCheckSuiteExistsCommand extends AbstractTestRailCommand
{
    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setTestRailServerOptions();
        $this->setName('testrail:check:suite:exists')
            ->addArgument('suite',
                InputArgument::REQUIRED,
                'the suite name')
            ->setDescription('check if a testsuite exists')
            ->setHelp(<<<EOT
The <info>%command.name%</info> checks that an test suite exists

Example (<comment>1</comment>): <info>returns 0 if the suite exists, 1 if not</info>

    $ %command.full_name% testSuite

Example (<comment>2</comment>): <info>returns 0 if the suite exists, 1 if not. without .testrail.yml</info>

    $ %command.full_name% testSuite --server=http://your.testrail.instance/testrail \
                                    --user=yourUser \
                                    --passwod=yourPassword \
                                    --project=yourTestRailProject
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        $server = $this->getTestRailServerOptions($input);

        $client = null;
        try{
            $client = Client::create($server['server'],$server['user'],$server['password']);
        }catch (\Throwable $e){
            $output->writeln('<error>cant connect to server '.$server['server'].'</error>');
            return -1;
        }

        if($server['project'] === null){
            $output->writeln('<error>the project is required!</error>');
            return -1;
        }

        $project = $client->projects()->findByName($server['project']);
        $suite = $client->suites()->findByName($project['id'],$input->getArgument('suite'));
        if(isset($suite['id'])){
            return 0;
        }
        return 1;
    }
}