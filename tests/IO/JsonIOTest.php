<?php

use seretos\BehatLoggerExtension\Entity\BehatFeature;
use seretos\BehatLoggerExtension\Entity\BehatResult;
use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatStep;
use seretos\BehatLoggerExtension\Entity\BehatStepResult;
use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\IO\JsonIO;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 10.08.18
 * Time: 01:32
 */

class JsonIOTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var JsonIO
     */
    private $jsonIO;

    protected function setUp()
    {
        parent::setUp();
        $this->jsonIO = new JsonIO();
    }

    /**
     * @test
     */
    public function toJson(){
        $suite = $this->createSuite();
        $jsonSuite = $this->jsonIO->toJson([$suite]);
        $this->assertSame('{"suites":[{"name":"bundles","features":{"features\/Navigation.feature":{"title":"Navigationmen\u00fc","filename":"features\/Navigation.feature","description":null,"language":"en","scenarios":{"Aufruf 1":{"title":"Aufruf 1","tags":["fixture_0","priority_low"],"steps":[{"line":0,"text":"test1","keyword":"Given","arguments":[]},{"line":1,"text":"test2","keyword":"And","arguments":[]}],"results":{"firefox":{"environment":"firefox","stepResults":[{"line":0,"passed":true,"screenshot":null},{"line":1,"passed":true,"screenshot":null}]},"ie":{"environment":"ie","stepResults":[{"line":0,"passed":true,"screenshot":null},{"line":1,"passed":true,"screenshot":null}]}}},"Aufruf 2":{"title":"Aufruf 2","tags":["demo_1","priority_high"],"steps":{"3":{"line":3,"text":"test3","keyword":"Given","arguments":[]},"4":{"line":4,"text":"test4","keyword":"And","arguments":[]}},"results":{"firefox":{"environment":"firefox","stepResults":{"3":{"line":3,"passed":true,"screenshot":null},"4":{"line":4,"passed":true,"screenshot":null}}},"ie":{"environment":"ie","stepResults":{"3":{"line":3,"passed":true,"screenshot":null},"4":{"line":4,"passed":false,"screenshot":null}}}}}}},"features\/Administration.feature":{"title":"Administration","filename":"features\/Administration.feature","description":null,"language":"en","scenarios":{"Aufruf 3":{"title":"Aufruf 3","tags":[],"steps":[{"line":0,"text":"test5","keyword":"Given","arguments":[]},{"line":1,"text":"test6","keyword":"And","arguments":[]}],"results":{"firefox":{"environment":"firefox","stepResults":[{"line":0,"passed":false,"screenshot":null},{"line":1,"passed":true,"screenshot":null}]},"ie":{"environment":"ie","stepResults":[{"line":0,"passed":true,"screenshot":null},{"line":1,"passed":false,"screenshot":null}]}}},"Aufruf 4":{"title":"Aufruf 4","tags":[],"steps":{"3":{"line":3,"text":"test7","keyword":"Given","arguments":[]},"4":{"line":4,"text":"test8","keyword":"And","arguments":[]}},"results":{"firefox":{"environment":"firefox","stepResults":{"3":{"line":3,"passed":true,"screenshot":null},"4":{"line":4,"passed":true,"screenshot":null}}},"ie":{"environment":"ie","stepResults":{"3":{"line":3,"passed":true,"screenshot":null},"4":{"line":4,"passed":true,"screenshot":null}}}}}}}}}]}'
            ,$jsonSuite);
    }

    private function createSuite(){
        $bundles = new BehatSuite("bundles");
        $feature1 = new BehatFeature("features/Navigation.feature","Navigationmenü");
        $feature2 = new BehatFeature("features/Administration.feature","Administration");

        $scenario1 = new BehatScenario("Aufruf 1",['fixture_0','priority_low']);
        $scenario2 = new BehatScenario("Aufruf 2",['demo_1','priority_high']);
        $scenario3 = new BehatScenario("Aufruf 3",[]);
        $scenario4 = new BehatScenario("Aufruf 4",[]);

        $scenario1_result_firefox = new BehatResult("firefox");
        $scenario1_result_ie = new BehatResult("ie");

        $scenario2_result_firefox = new BehatResult("firefox");
        $scenario2_result_ie = new BehatResult("ie");

        $scenario3_result_firefox = new BehatResult("firefox");
        $scenario3_result_ie = new BehatResult("ie");

        $scenario4_result_firefox = new BehatResult("firefox");
        $scenario4_result_ie = new BehatResult("ie");

        $scenario1_step1 = new BehatStep(0,"test1","Given");
        $scenario1_step2 = new BehatStep(1,"test2","And");

        $scenario1_step1_result_firefox = new BehatStepResult(0,true);
        $scenario1_step2_result_firefox = new BehatStepResult(1,true);
        $scenario1_step1_result_ie = new BehatStepResult(0,true);
        $scenario1_step2_result_ie = new BehatStepResult(1,true);

        $scenario2_step1 = new BehatStep(3,"test3","Given");
        $scenario2_step2 = new BehatStep(4,"test4","And");

        $scenario2_step1_result_firefox = new BehatStepResult(3,true);
        $scenario2_step2_result_firefox = new BehatStepResult(4,true);
        $scenario2_step1_result_ie = new BehatStepResult(3,true);
        $scenario2_step2_result_ie = new BehatStepResult(4,false);

        $scenario3_step1 = new BehatStep(0,"test5","Given");
        $scenario3_step2 = new BehatStep(1,"test6","And");

        $scenario3_step1_result_firefox = new BehatStepResult(0,false);
        $scenario3_step2_result_firefox = new BehatStepResult(1,true);
        $scenario3_step1_result_ie = new BehatStepResult(0,true);
        $scenario3_step2_result_ie = new BehatStepResult(1,false);

        $scenario4_step1 = new BehatStep(3,"test7","Given");
        $scenario4_step2 = new BehatStep(4,"test8","And");

        $scenario4_step1_result_firefox = new BehatStepResult(3,true);
        $scenario4_step2_result_firefox = new BehatStepResult(4,true);
        $scenario4_step1_result_ie = new BehatStepResult(3,true);
        $scenario4_step2_result_ie = new BehatStepResult(4,true);

        $scenario1->addStep($scenario1_step1);
        $scenario1->addStep($scenario1_step2);

        $scenario2->addStep($scenario2_step1);
        $scenario2->addStep($scenario2_step2);

        $scenario3->addStep($scenario3_step1);
        $scenario3->addStep($scenario3_step2);

        $scenario4->addStep($scenario4_step1);
        $scenario4->addStep($scenario4_step2);

        $scenario1_result_firefox->addStepResult($scenario1_step1_result_firefox);
        $scenario1_result_firefox->addStepResult($scenario1_step2_result_firefox);
        $scenario1_result_ie->addStepResult($scenario1_step1_result_ie);
        $scenario1_result_ie->addStepResult($scenario1_step2_result_ie);

        $scenario2_result_firefox->addStepResult($scenario2_step1_result_firefox);
        $scenario2_result_firefox->addStepResult($scenario2_step2_result_firefox);
        $scenario2_result_ie->addStepResult($scenario2_step1_result_ie);
        $scenario2_result_ie->addStepResult($scenario2_step2_result_ie);

        $scenario3_result_firefox->addStepResult($scenario3_step1_result_firefox);
        $scenario3_result_firefox->addStepResult($scenario3_step2_result_firefox);
        $scenario3_result_ie->addStepResult($scenario3_step1_result_ie);
        $scenario3_result_ie->addStepResult($scenario3_step2_result_ie);

        $scenario4_result_firefox->addStepResult($scenario4_step1_result_firefox);
        $scenario4_result_firefox->addStepResult($scenario4_step2_result_firefox);
        $scenario4_result_ie->addStepResult($scenario4_step1_result_ie);
        $scenario4_result_ie->addStepResult($scenario4_step2_result_ie);

        $scenario1->addResult($scenario1_result_firefox);
        $scenario1->addResult($scenario1_result_ie);

        $scenario2->addResult($scenario2_result_firefox);
        $scenario2->addResult($scenario2_result_ie);

        $scenario3->addResult($scenario3_result_firefox);
        $scenario3->addResult($scenario3_result_ie);

        $scenario4->addResult($scenario4_result_firefox);
        $scenario4->addResult($scenario4_result_ie);

        $feature1->addScenario($scenario1);
        $feature1->addScenario($scenario2);
        $feature2->addScenario($scenario3);
        $feature2->addScenario($scenario4);

        $bundles->addFeature($feature1);
        $bundles->addFeature($feature2);

        return $bundles;
    }
}