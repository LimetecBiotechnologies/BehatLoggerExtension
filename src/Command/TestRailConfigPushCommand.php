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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class TestRailConfigPushCommand extends ContainerAwareCommand
{
    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('testrail:push:configs')
            ->addArgument('suite',
                InputArgument::REQUIRED,
                'the suite name')
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

        $config = Yaml::parseFile('.testrail.yml');

        $client = Client::create($config['api']['server'],$config['api']['user'],$config['api']['password']);

        $importer = new TestRailConfigImporter($client,
            $config['api']['project'],
            $input->getArgument('suite'),
            $config['fields'],
            $config['priorities'],
            $config['api']['identifier'],
            $config['api']['run_group_field']);

        foreach($jsonSuites as $suite) {
            /* @var BehatSuite $suite */
            foreach ($suite->getFeatures() as $feature) {
                foreach($feature->getScenarios() as $scenario){
                    $importer->createConfigs($scenario);
                }
            }
        }

        return 0;
    }
}