<?php

use PHPUnit\Framework\TestCase;
use seretos\BehatLoggerExtension\Entity\BehatFeature;
use seretos\BehatLoggerExtension\Entity\BehatResult;
use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatStep;
use seretos\BehatLoggerExtension\Entity\BehatStepResult;
use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Service\BehatLoggerFactory;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 11.08.18
 * Time: 16:46
 */

class BehatLoggerFactoryTest extends TestCase
{
    /**
     * @var BehatLoggerFactory
     */
    private $factory;
    protected function setUp()
    {
        parent::setUp();
        $this->factory = new BehatLoggerFactory();
    }

    /**
     * @test
     */
    public function createSuite(){
        $this->assertInstanceOf(BehatSuite::class,$this->factory->createSuite("test"));
    }

    /**
     * @test
     */
    public function createFeature(){
        $this->assertInstanceOf(BehatFeature::class,$this->factory->createFeature("file.name","title",null,'en'));
    }

    /**
     * @test
     */
    public function createScenario(){
        $this->assertInstanceOf(BehatScenario::class,$this->factory->createScenario("title",["tag1","tag2"]));
    }

    /**
     * @test
     */
    public function createResult_test(){
        $this->assertInstanceOf(BehatResult::class,$this->factory->createResult('environment'));
    }

    /**
     * @test
     */
    public function createStepResult(){
        $this->assertInstanceOf(BehatStepResult::class,$this->factory->createStepResult(0,true,null));
    }

    /**
     * @test
     */
    public function createStep(){
        $this->assertInstanceOf(BehatStep::class,$this->factory->createStep(0,'text','Given',[]));
    }
}