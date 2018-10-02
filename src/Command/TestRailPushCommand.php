<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 28.09.18
 * Time: 16:30
 */

namespace seretos\BehatLoggerExtension\Command;

use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\IO\JsonIO;
use seretos\testrail\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class TestRailPushCommand extends ContainerAwareCommand
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

        $project = $this->getProject($client,$config['api']['project']);
        $suite = $this->getSuite($client,$project['id'],$input->getArgument('suite'));
        $template = $client->templates()->findByName($project['id'],$config['api']['template']);
        $type = $client->types()->findByName($config['api']['type']);

        if($template === []){
            $output->writeln('<error>template "'.$config['api']['template'].'" not found!</error>');
            return -1;
        }
        if($type === []){
            $output->writeln('<error>type "'.$config['api']['type'].'" not found</error>');
            return -1;
        }

        $this->pushTests($output, $input->getArgument('json-file'),$client, $project['id'],$suite['id'],$template['id'],$type['id'], $config['api']['identifer'], $config['fields'], $config['priorities']);

        return 0;
    }

    private function pushTests(OutputInterface $output, string $file, Client $client, $projectId, $suiteId, $templateId, $typeId, $identifier, $fieldConfigs, $priorityConfigs){
        /* @var $printer JsonIO*/
        $printer = $this->getContainer()->get('json.printer');
        $jsonSuites = $printer->toObjects($file);
        foreach ($jsonSuites as $currentSuite){
            /* @var $currentSuite BehatSuite*/
            foreach ($currentSuite->getFeatures() as $feature){
                $section = $this->buildSection($feature->getTitle(),$client, $projectId, $suiteId);
                $output->writeln('section: '.$section['name']);
                foreach ($feature->getScenarios() as $scenario) {
                    $output->writeln('case: '.$scenario->getTitle());
                    $case = $this->getTest($client,$scenario,$projectId,$suiteId,$section['id'],$templateId,$typeId, $identifier, $fieldConfigs, $priorityConfigs);
                    $this->pushSteps($client,$scenario,$case);
                }
            }
        }
    }

    private function getTest(Client $client, BehatScenario $scenario, $projectId, $suiteId, $sectionId, $templateId, $typeId, $identifier, $fieldConfigs, $priorityConfigs){
        $case = $client->cases()->findByField($projectId, $suiteId, $sectionId,$identifier,$scenario->getTitle());
        $caseTitle = $scenario->getTitle();
        if(strlen($caseTitle) > 200){
            $caseTitle = substr($caseTitle,0,197).'...';
        }
        $customFields = $this->getCustomFieldValues($client, $scenario, $projectId,$fieldConfigs);
        $customFields[$identifier] = $scenario->getTitle();
        $customFields['priority_id'] = $this->getPriorityValue($client,$scenario,$priorityConfigs);

        if($case === []){
            $case = $client->cases()->create($sectionId,$caseTitle,$templateId,$typeId,$customFields);
        }else{
            $customFields['title'] = $caseTitle;
            $case = $client->cases()->update($case['id'],$customFields);
        }

        return $case;
    }

    private function pushSteps(Client $client, BehatScenario $scenario, $case){
        $steps = [];
        foreach($scenario->getSteps() as $step){
            $text = $step->getKeyword().' '.$step->getText();
            if(is_array($step->getArguments()) && $step->getArguments() !== []){
                $text .= "\n";
                foreach ($step->getArguments() as $row){
                    $text .= '|';
                    foreach ($row as $cell){
                        $text .= '| '.$cell;
                    }
                    $text .= "\n";
                }
            }
            $steps[] = ["content" => $text, "expected" => ""];
        }
        return $client->cases()->update($case['id'],['custom_steps_separated' => $steps]);
    }

    private function getPriorityValue(Client $client, BehatScenario $scenario, $priorityConfigs){
        $priority = $client->priorities()->getDefaultPriority()['id'];
        foreach ($priorityConfigs as $pattern => $priorityString){
            foreach ($scenario->getTags() as $tag){
                if(preg_match($pattern,$tag)){
                    return $client->priorities()->findByName($priorityString)['id'];
                }
            }
        }
        return $priority;
    }

    private function getCustomFieldValues(Client $client, BehatScenario $scenario, $projectId, $fieldConfigs){
        $fields = [];
        foreach ($fieldConfigs as $pattern => $field){
            foreach ($scenario->getTags() as $tag){
                if(preg_match($pattern,$tag)){
                    foreach($field as $item => $value){
                        $fields[$item] = $client->fields()->findElementId($item,$value,$projectId);
                    }
                }
            }
        }
        return $fields;
    }

    private function buildSection(string $sectionString, Client $client, $projectId, $suiteId){
        $sections = explode('=>',$sectionString);
        $parentId = null;
        $sec = [];
        foreach($sections as $section){
            $sec = $this->getSection($client,$projectId,$suiteId,$section,$parentId);
            $parentId = $sec['id'];
        }
        return $sec;
    }

    private function getSection(Client $client, int $projectId, int $suiteId, string $name, int $parent = null){
        $section = $client->sections()->findByNameAndParent($projectId, $suiteId,$name,$parent);
        if($section === []){
            $section = $client->sections()->create($projectId,$suiteId,$name,null,$parent);
        }
        return $section;
    }

    private function getProject(Client $client, string $name){
        $project = $client->projects()->findByName($name);
        if($project === []){
            $project = $client->projects()->create($name);
        }
        return $project;
    }

    private function getSuite(Client $client, int $projectId, string $name){
        $suite = $client->suites()->findByName($projectId,$name);
        if($suite === []){
            $suite = $client->suites()->create($projectId,$name);
        }
        return $suite;
    }
}