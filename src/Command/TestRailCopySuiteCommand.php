<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 25.10.18
 * Time: 10:45
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\testrail\api\Cases;
use seretos\testrail\api\Sections;
use seretos\testrail\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestRailCopySuiteCommand extends AbstractTestRailCommand
{
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

        $suitesApi = $client->suites();
        $sectionsApi = $client->sections();
        $casesApi = $client->cases();
        $project = $client->projects()->findByName($server['project']);

        $sourceSuite = $suitesApi->findByName($project['id'],$input->getArgument('source'));
        $targetSuite = $suitesApi->create($project['id'],$input->getArgument('target'),$sourceSuite['description']);

        $this->buildSectionArea($output,$sectionsApi,$casesApi,$project['id'],$sourceSuite['id'],$targetSuite['id']);
        $this->buildSectionCases($output,$casesApi,$project['id'],$sourceSuite['id']);

        return 0;
    }

    private function buildSectionArea(OutputInterface $output,Sections $sectionApi, Cases $caseApi, int $projectId, int $sourceSuiteId, int $targetSuiteId, int $sourceParentId = null, int $targetParentId = null){
        foreach($sectionApi->findByParent($projectId,$sourceSuiteId,$sourceParentId) as $sourceSection){
            $output->writeln('import section '.$sourceSection['name']);
            $targetSection = $sectionApi->create($projectId,$targetSuiteId,$sourceSection['name'],$sourceSection['description'],$targetParentId);
            $this->buildSectionCases($output,$caseApi,$projectId,$sourceSuiteId,$sourceSection['id'],$targetSection['id']);
            $this->buildSectionArea($output,$sectionApi,$caseApi,$projectId,$sourceSuiteId,$targetSuiteId,$sourceSection['id'],$targetSection['id']);
        }
    }

    private function buildSectionCases(OutputInterface $output,Cases $caseApi, int $projectId, int $sourceSuiteId, int $sourceParentId = null, int $targetParentId = null){
        $sourceCases = $caseApi->findBySection($projectId,$sourceSuiteId,$sourceParentId);
        foreach($sourceCases as $sourceCase){
            $title = $sourceCase['title'];
            $template = $sourceCase['template_id'];
            $type = $sourceCase['type_id'];
            unset($sourceCase['id']);
            unset($sourceCase['title']);
            unset($sourceCase['template_id']);
            unset($sourceCase['type_id']);
            unset($sourceCase['created_by']);
            unset($sourceCase['created_on']);
            unset($sourceCase['updated_by']);
            unset($sourceCase['updated_on']);
            unset($sourceCase['suite_id']);
            $output->writeln('import case '.$title);
            $caseApi->create($targetParentId,$title,$template,$type,$sourceCase);
        }
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setTestRailServerOptions();
        $this->setName('testrail:copy:suite')
            ->addArgument('source',InputArgument::REQUIRED,'the source suite name')
            ->addArgument('target',InputArgument::REQUIRED,'the target suite name')
            ->setDescription('send environment configs to an testrail instance')
            ->setHelp(<<<EOT
The <info>%command.name%</info> send environment configs to an testrail instance
EOT
            );
    }
}