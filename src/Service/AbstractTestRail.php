<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.10.18
 * Time: 14:40
 */

namespace seretos\BehatLoggerExtension\Service;


use seretos\BehatLoggerExtension\Entity\BehatStep;
use seretos\BehatLoggerExtension\Exception\TestRailException;
use seretos\testrail\api\Cases;
use seretos\testrail\api\Fields;
use seretos\testrail\api\Priorities;
use seretos\testrail\api\Projects;
use seretos\testrail\api\Sections;
use seretos\testrail\api\Suites;
use seretos\testrail\api\Types;
use seretos\testrail\Client;

abstract class AbstractTestRail
{
    /**
     * @var int
     */
    protected $projectId;
    /**
     * @var int
     */
    protected $suiteId;
    /**
     * @var int
     */
    protected $typeId;
    /**
     * @var Projects
     */
    protected $projectApi;
    /**
     * @var  Suites
     */
    protected $suiteApi;
    /**
     * @var Sections
     */
    protected $sectionApi;
    /**
     * @var Fields
     */
    protected $fieldApi;
    /**
     * @var Priorities
     */
    protected $priorityApi;
    /**
     * @var Cases
     */
    protected $caseApi;
    /**
     * @var Types
     */
    protected $typeApi;
    /**
     * @var array
     */
    private $customFieldConfig;
    /**
     * @var array
     */
    private $priorityConfig;
    /**
     * @var string
     */
    protected $identifierField;

    public function __construct(Client $client, string $projectName, string $suiteName, array $customFieldConfig, array $priorityConfig, string $identifierField)
    {
        $this->projectApi = $client->projects();
        $this->suiteApi = $client->suites();
        $this->sectionApi = $client->sections();
        $this->fieldApi = $client->fields();
        $this->priorityApi = $client->priorities();
        $this->caseApi = $client->cases();
        $this->typeApi = $client->types();

        $this->setProject($projectName);
        $this->setSuite($suiteName);
        $this->customFieldConfig = $customFieldConfig;
        $this->priorityConfig = $priorityConfig;
        $this->identifierField = $identifierField;

        $this->typeId = null;
    }

    /**
     * @param string $projectName
     */
    protected function setProject(string $projectName){
        $project = $this->projectApi->findByName($projectName);
        if(!isset($project['id'])){
            $project = $this->projectApi->create($projectName);
        }
        $this->projectId = $project['id'];
    }

    /**
     * @param string $suiteName
     */
    protected function setSuite(string $suiteName){
        $suite = $this->suiteApi->findByName($this->projectId,$suiteName);
        if($suite === []){
            $suite = $this->suiteApi->create($this->projectId,$suiteName);
        }
        $this->suiteId = $suite['id'];
    }

    /**
     * @param string $sectionString
     * @return int
     * @throws TestRailException
     */
    protected function getCaseSection(string $sectionString){
        $sections = explode('=>',$sectionString);
        $parentId = null;
        $sec = [];
        foreach($sections as $section){
            $sec = $this->sectionApi->findByNameAndParent($this->projectId, $this->suiteId,$section,$parentId);
            if(!isset($sec['id'])){
                $sec = $this->sectionApi->create($this->projectId,$this->suiteId,$section,null,$parentId);
            }
            $parentId = $sec['id'];
        }
        if(!isset($sec['id'])){
            throw new TestRailException('section not found!');
        }
        return $sec['id'];
    }

    /**
     * @param array $tags
     * @return array
     * @throws TestRailException
     */
    protected function getCustomFieldValues(array $tags){
        $fields = [];
        foreach ($this->customFieldConfig as $pattern => $field){
            foreach ($tags as $tag){
                if(preg_match($pattern,$tag)){
                    foreach($field as $item => $value){
                        $fields[$item] = $this->fieldApi->findElementId($item,$value,$this->projectId);
                        if($fields[$item] === 0){
                            throw new TestRailException('the custom field or field element does not exist! '.$item.'=>'.$value);
                        }
                    }
                }
            }
        }
        return $fields;
    }

    protected function getPriorityValue(array $tags){
        $priority = $this->priorityApi->getDefaultPriority()['id'];
        foreach ($this->priorityConfig as $pattern => $priorityString){
            foreach ($tags as $tag){
                if(preg_match($pattern,$tag)){
                    return $this->priorityApi->findByName($priorityString)['id'];
                }
            }
        }
        return $priority;
    }

    protected function getStepText(BehatStep $step){
        $text = '**'.$step->getKeyword().'** ';
        $text .= preg_replace('/"(.[^"]*)"/','**"${1}"**',$step->getText());
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
        return $text;
    }

    /**
     * @param string $typeName
     * @throws TestRailException
     */
    public function setType(string $typeName){
        $type = $this->typeApi->findByName($typeName);
        if(!isset($type['id'])){
            throw new TestRailException('type not found!');
        }
        $this->typeId = $type['id'];
    }
}