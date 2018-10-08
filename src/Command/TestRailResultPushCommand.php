<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.10.18
 * Time: 10:56
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Service\TestRailResultImporter;
use seretos\testrail\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class TestRailResultPushCommand extends ContainerAwareCommand
{
    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('testrail:push:results')
            ->addArgument('suite',
                InputArgument::REQUIRED,
                'the suite name')
            ->addArgument('json-file',
                InputArgument::REQUIRED,
                'the behat json log file')
            ->addArgument('name',InputArgument::REQUIRED,'the plan name')
            ->addOption('milestone','m',InputOption::VALUE_REQUIRED,'the refered milestone',null)
            ->addOption('description','d',InputOption::VALUE_REQUIRED,'the plan description',null)
            ->setDescription('send a result feature to an testrail instance')
            ->setHelp(<<<EOT
The <info>%command.name%</info> sends the results to an testrail instance
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
        /* @var $fs Filesystem*/
        $fs = $this->getContainer()->get('filesystem');
        if(!$fs->exists('.testrail.yml')){
            $output->writeln('<error>config file .testrail.yml does not exist!</error>');
            return -1;
        }
        $config = Yaml::parseFile('.testrail.yml');

        $client = Client::create($config['api']['server'],$config['api']['user'],$config['api']['password']);

        $importer = new TestRailResultImporter($client,
            $config['api']['project'],
            $input->getArgument('suite'),
            $config['fields'],
            $config['priorities'],
            $config['api']['identifier'],
            $config['api']['run_group_field']);
        $importer->setType($config['api']['type']);

        $plan = $importer->createPlan($input->getArgument('name'),$input->getOption('description'),$input->getOption('milestone'));

        $printer = $this->getContainer()->get('json.printer');
        $jsonSuites = $printer->toObjects($input->getArgument('json-file'));

        foreach($jsonSuites as $suite){
            /* @var BehatSuite $suite*/
            foreach($suite->getFeatures() as $feature){
                $output->writeln('import feature results for '.$feature->getFilename());
                foreach($feature->getScenarios() as $scenario){
                    $importer->createResult($scenario,$feature,$plan);
                }
            }
        }

        return 0;
    }

//    private function importConfigs(Client $client, array $jsonSuites, $projectId, $fieldConfigs, $groupField){
//        $groups = [];
//        $fieldsApi = $client->fields();
//        $configurationApi = $client->configurations();
//        $resultGroups = [];
//        foreach ($jsonSuites as $suite) {
//            /* @var BehatSuite $suite*/
//            foreach ($suite->getFeatures() as $feature){
//                foreach($feature->getScenarios() as $scenario){
//                    $fields = $this->getCustomFieldValues($fieldsApi,$scenario,$projectId, $fieldConfigs);
//                    if(isset($fields[$groupField])){
//                        $group = $fieldsApi->findElementNameById($groupField,$fields[$groupField]);
//                        if(!isset($groups[$group])){
//                            $groups[$group]= [];
//                        }
//                        foreach ($scenario->getResults() as $environment => $result){
//                            if(!in_array($environment,$groups[$group])){
//                                $groups[$group][] = $environment;
//                            }
//                        }
//                    }
//                }
//            }
//        }
//
//        foreach($groups as $group => $values){
//            $testRailGroup = $configurationApi->findByGroupName($projectId,$group);
//            if($testRailGroup === []){
//                $testRailGroup = $configurationApi->createGroup($projectId,$group);
//            }
//            $resultGroups[$group] = [];
//            foreach($values as $value){
//                $element = $configurationApi->findByName($projectId,$group,$value);
//                if($element === []) {
//                    $element = $configurationApi->create($testRailGroup['id'], $value);
//                }
//                $resultGroups[$group][] = $element['id'];
//            }
//        }
//
//        return $resultGroups;
//    }
}