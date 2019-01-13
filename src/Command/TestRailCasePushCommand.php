<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 28.09.18
 * Time: 16:30
 */

namespace seretos\BehatLoggerExtension\Command;

use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Exception\TestRailException;
use seretos\BehatLoggerExtension\IO\JsonIO;
use seretos\BehatLoggerExtension\Service\TestRailSuiteImporter;
use seretos\testrail\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class TestRailCasePushCommand extends ContainerAwareCommand
{
    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('testrail:push:cases')
            ->addArgument('suite',
                InputArgument::REQUIRED,
                'the suite name')
            ->addArgument('json-file',
                InputArgument::REQUIRED,
                'the behat json log file')
            ->setDescription('create an suite, testcases and sections in testrail')
            ->setHelp(<<<EOT
The <info>%command.name%</info> sends the cases to an testrail instance

Example (<comment>1</comment>): <info>send your result to testrail. create or update the suite testsuite. requires a .testrail.yml in the current working dir</info>

    $ %command.full_name% testSuite result.json
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws TestRailException
     */
    public function execute (InputInterface $input, OutputInterface $output) {

        /* @var $fs Filesystem*/
        $fs = $this->getContainer()->get('filesystem');
        if(!$fs->exists('.testrail.yml')){
            $output->writeln('<error>config file .testrail.yml does not exist!</error>');
            return -1;
        }
        $config = Yaml::parseFile('.testrail.yml');

        $client = Client::create($config['api']['server'],$config['api']['user'],$config['api']['password']);

        $tagIdRegex = null;
        $tagIdField = null;
        if(isset($config['api']['identifier_tag_regex']) && isset($config['api']['identifier_tag_field'])){
            $tagIdRegex = $config['api']['identifier_tag_regex'];
            $tagIdField = $config['api']['identifier_tag_field'];
        }

        $importer = new TestRailSuiteImporter($client,
            $config['api']['project'],
            $input->getArgument('suite'),
            $config['fields'],
            $config['priorities'],
            $config['api']['identifier'],$tagIdRegex,$tagIdField);
        $importer->setTemplate($config['api']['template']);
        $importer->setType($config['api']['type']);

        /* @var $printer JsonIO*/
        $printer = $this->getContainer()->get('json.printer');
        $jsonSuites = $printer->toObjects($input->getArgument('json-file'));

        foreach ($jsonSuites as $currentSuite){
            /* @var $currentSuite BehatSuite*/
            foreach ($currentSuite->getFeatures() as $feature){
                $output->writeln('import feature '.$feature->getFilename());
                foreach ($feature->getScenarios() as $scenario) {
                    $importer->pushTest($scenario,$feature);
                }
            }
        }

        return 0;
    }
}