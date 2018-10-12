<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 12.10.18
 * Time: 16:29
 */

namespace seretos\BehatLoggerExtension\Service;


use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\testrail\api\Configurations;
use seretos\testrail\Client;

class TestRailConfigImporter extends AbstractTestRail
{
    /**
     * @var string
     */
    private $groupField;
    /**
     * @var Configurations
     */
    private $configurationApi;

    public function __construct(Client $client, string $projectName, string $suiteName, array $customFieldConfig, array $priorityConfig, string $identifierField, string $groupField)
    {
        parent::__construct($client, $projectName, $suiteName, $customFieldConfig, $priorityConfig, $identifierField);
        $this->groupField = $groupField;

        $this->configurationApi = $client->configurations();
    }

    /**
     * @param BehatScenario $scenario
     * @throws \seretos\BehatLoggerExtension\Exception\TestRailException
     */
    public function createConfigs(BehatScenario $scenario){
        $fields = $this->getCustomFieldValues($scenario->getTags());

        $group = $this->fieldApi->findElementNameById($this->groupField,$fields[$this->groupField]);

        $testRailGroup = $this->configurationApi->findByGroupName($this->projectId,$group);
        if(!isset($testRailGroup['id'])){
            $testRailGroup = $this->configurationApi->createGroup($this->projectId,$group);
        }
        foreach ($scenario->getResults() as $environment => $result){
            $element = $this->configurationApi->findByName($this->projectId,$group,$environment);
            if(!isset($element['id'])){
                $this->configurationApi->create($testRailGroup['id'], $environment);
            }
        }
    }
}