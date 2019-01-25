<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 05.10.18
 * Time: 14:40
 */

namespace seretos\BehatLoggerExtension\Service;

use seretos\BehatLoggerExtension\Entity\BehatFeature;
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

    public function __construct(Client $client, string $projectName, string $suiteName, array $customFieldConfig, array $priorityConfig, string $identifierField, string $identifierTagRegex = null, string $identifierTagField = null)
    {
        parent::__construct($client, $projectName, $suiteName, $customFieldConfig, $priorityConfig, $identifierField, $identifierTagRegex, $identifierTagField);
        $this->templateApi = $client->templates();

        $this->templateId = null;
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
     * @param BehatFeature $feature
     * @throws TestRailException
     */
    public function pushTest(BehatScenario $scenario, BehatFeature $feature)
    {
        $section = $this->getCaseSection($feature->getTitle());
        $case = $this->caseApi->findByField($this->projectId, $this->suiteId, $section, $this->identifierField, $scenario->getTitle());

        $caseTitle = $scenario->getTitle();
        if (strlen($caseTitle) > 200) {
            $caseTitle = substr($caseTitle, 0, 197) . '...';
        }

        $customFields = $this->getCustomFieldValues($scenario->getTags());
        $customFields[$this->identifierField] = $scenario->getTitle();
        $customFields['priority_id'] = $this->getPriorityValue($scenario->getTags());
        if ($this->identifierTagField !== null
            && $this->fieldApi->findByName($this->identifierTagField, $this->projectId)['id'] !== null
            && $this->identifierTagRegex !== null) {
            $customFields[$this->identifierTagField] = $scenario->getTestRailId($this->identifierTagRegex, null);
        }

        $steps = [];
        foreach ($scenario->getSteps() as $step) {
            $steps[] = ["content" => $this->getStepText($step), "expected" => ""];
        }
        $customFields['custom_steps_separated'] = $steps;

        if (!isset($case['id'])) {
            $this->caseApi->create($section, $caseTitle, $this->templateId, $this->typeId, $customFields);
        } else {
            $customFields['title'] = $caseTitle;
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
}