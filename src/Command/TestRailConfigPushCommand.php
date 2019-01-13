<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 12.10.18
 * Time: 16:27
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Service\TestRailConfigImporter;
use seretos\testrail\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestRailConfigPushCommand extends AbstractTestRailCommand
{
    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setTestRailServerOptions();
        $this->setGroupFieldOption();
        $this->setName('testrail:push:configs')
            ->addArgument('json-file',
                InputArgument::REQUIRED,
                'the behat json log file')
            ->setDescription('send environment configs to an testrail instance')
            ->setHelp(<<<EOT
The <info>%command.name%</info> send environment configs to an testrail instance
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws \seretos\BehatLoggerExtension\Exception\TestRailException
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        $printer = $this->getContainer()->get('json.printer');
        $jsonSuites = $printer->toObjects($input->getArgument('json-file'));

        $server = $this->getTestRailServerOptions($input);
        $groupField = $this->getGroupFieldOption($input);

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
        if($groupField === null){
            $output->writeln('<error>the group-field is required!</error>');
            return -1;
        }

        $importer = new TestRailConfigImporter($client,
            $server['project'],
            $this->getFieldOptions(),
            $this->getPriorityOptions(),
            $groupField);

        foreach($jsonSuites as $suite) {
            /* @var BehatSuite $suite */
            foreach ($suite->getFeatures() as $feature) {
                foreach($feature->getScenarios() as $scenario){
                    $importer->createConfigs($scenario);
                }
            }
        }
        $output->writeln('done');

        return 0;
    }
}