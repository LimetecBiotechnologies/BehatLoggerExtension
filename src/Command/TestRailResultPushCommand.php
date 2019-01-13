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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestRailResultPushCommand extends AbstractTestRailCommand
{
    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setTestRailServerOptions();
        $this->setGroupFieldOption();
        $this->setIdentifierOptions();
        $this->setSynchronizationOptions();
        $this->setName('testrail:push:results')
            ->addArgument('suite',
                InputArgument::REQUIRED,
                'the suite name')
            ->addArgument('json-file',
                InputArgument::REQUIRED,
                'the behat json log file')
            ->addArgument('name',InputArgument::REQUIRED,'the plan name')
            ->addOption('milestone',null,InputOption::VALUE_REQUIRED,'the refered milestone',null)
            ->addOption('description',null,InputOption::VALUE_REQUIRED,'the plan description',null)
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
        $server = $this->getTestRailServerOptions($input);
        $groupField = $this->getGroupFieldOption($input);
        $identifier = $this->getTestRailIdentifierOptions($input);
        $synchronization = $this->getTestRailSynchronizationOptions($input);

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

        if($identifier['identifier-field'] === null){
            $output->writeln('<error>the identifier field is required!</error>');
            return -1;
        }

        if($groupField === null){
            $output->writeln('<error>the group-field is required!</error>');
            return -1;
        }

        $importer = new TestRailResultImporter($client,
            $server['project'],
            $input->getArgument('suite'),
            $this->getFieldOptions(),
            $this->getPriorityOptions(),
            $identifier['identifier-regex'],
            $identifier['identifier-field'],
            $groupField);
        $importer->setType($synchronization['type']);

        $plan = $importer->createPlan($input->getArgument('name'),$input->getOption('description'),$input->getOption('milestone'));

        $printer = $this->getContainer()->get('json.printer');
        $jsonSuites = $printer->toObjects($input->getArgument('json-file'));

        $cnt = 0;
        foreach ($jsonSuites as $currentSuite) {
            /* @var $currentSuite BehatSuite */
            $cnt += $currentSuite->getScenarioCount();
        }

        $progressBar = new ProgressBar($output, $cnt);
        $progressBar->setFormat(' %current%/%max% [%bar%] %message%');
        $progressBar->setMessage('start');

        foreach($jsonSuites as $suite){
            /* @var BehatSuite $suite*/
            foreach($suite->getFeatures() as $feature){
                foreach($feature->getScenarios() as $scenario){
                    $progressBar->advance();
                    $progressBar->setMessage($feature->getFilename());
                    $importer->createResult($scenario,$plan);
                }
            }
        }

        $importer->closePlan($plan['id']);
        $progressBar->finish();
        $output->writeln('');

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