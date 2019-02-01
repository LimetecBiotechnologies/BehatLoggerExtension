<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.10.18
 * Time: 14:40
 */

namespace seretos\BehatLoggerExtension\Service;

use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Exception\TestRailException;
use seretos\testrail\api\Templates;
use seretos\testrail\Client;

class TestRailSuiteImporter extends AbstractTestRail
{
    /**
     * @var int
     */
    private $templateId;
    /**
     * @var Templates
     */
    private $templateApi;
    /**
     * @var String
     */
    private $defaultSection;

    /**
     * TestRailSuiteImporter constructor.
     * @param Client $client
     * @param string $projectName
     * @param string $suiteName
     * @param array $customFieldConfig
     * @param array $priorityConfig
     * @param string|null $titleField
     * @param string|null $identifierRegex
     * @param string|null $identifierField
     * @param string|null $defaultSection
     * @throws TestRailException
     */
    public function __construct(Client $client, string $projectName, string $suiteName, array $customFieldConfig, array $priorityConfig, string $titleField = null, string $identifierRegex = null, string $identifierField = null, string $defaultSection = null)
    {
        parent::__construct($client, $projectName, $suiteName, $customFieldConfig, $priorityConfig, $titleField, $identifierRegex, $identifierField);
        $this->templateApi = $client->templates();

        $this->templateId = null;
        $this->defaultSection = $this->getCaseSection($defaultSection);
    }

    /**
     * @param string $templateName
     * @throws TestRailException
     */
    public function setTemplate(string $templateName)
    {
        if ($this->projectId === null) {
            throw new TestRailException('projectId not setted!');
        }
        $template = $this->templateApi->findByName($this->projectId, $templateName);
        if (!isset($template['id'])) {
            throw new TestRailException('template not found!');
        }
        $this->templateId = $template['id'];
    }

    /**
     * @param BehatScenario $scenario
     * @throws TestRailException
     */
    public function pushTest(BehatScenario $scenario)
    {
        $importId = $scenario->getTestRailId($this->identifierRegex);
        $case = $this->caseApi->findByField($this->projectId, $this->suiteId, $this->identifierField, $importId,null);

        $caseTitle = $scenario->getTitle();
        if (strlen($caseTitle) > 200) {
            $caseTitle = substr($caseTitle, 0, 197) . '...';
        }

        $customFields = $this->getCustomFieldValues($scenario->getTags());
        if($this->titleField !== null) {
            $customFields[$this->titleField] = $scenario->getTitle();
        }
        $customFields['priority_id'] = $this->getPriorityValue($scenario->getTags());
        $customFields[$this->identifierField] = $importId;

        $steps = [];
        foreach ($scenario->getSteps() as $step) {
            $steps[] = ["content" => $this->getStepText($step), "expected" => ""];
        }
        $customFields['custom_steps_separated'] = $steps;

        if (!isset($case['id'])) {
            $this->caseApi->create($this->defaultSection, $caseTitle, $this->templateId, $this->typeId, $customFields);
        } else {
            if (!$this->compareWithOrigin($customFields, $case)) {
                $this->caseApi->update($case['id'], $customFields);
            }
        }
    }

    private function compareWithOrigin(array $currentFields, array $originFields)
    {
        foreach ($currentFields as $key => $value) {
            if ($value !== $originFields[$key]) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $sectionString
     * @return mixed
     * @throws TestRailException
     */
    protected function getCaseSection(?string $sectionString){
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
}