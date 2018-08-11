<?php

use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Suite\Suite;
use PHPUnit\Framework\TestCase;
use seretos\BehatLoggerExtension\Formatter\BehatLogFormatter;
use seretos\BehatLoggerExtension\IO\JsonIO;
use seretos\BehatLoggerExtension\Service\BehatLoggerFactory;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 11.08.18
 * Time: 17:03
 */
class BehatLogFormatterTest extends TestCase
{
    /**
     * @var BehatLogFormatter
     */
    private $formatter;
    /**
     * @var BehatLoggerFactory
     */
    private $factory;
    /**
     * @var Mink|PHPUnit_Framework_MockObject_MockObject
     */
    private $minkMock;
    /**
     * @var OutputPrinter
     */
    private $printer;
    /**
     * @var Filesystem|PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystemMock;
    /**
     * @var array
     */
    private $parameters = ['browser_name' => 'test'];
    /**
     * @var string
     */
    private $output = ".";

    protected function setUp()
    {
        parent::setUp();
        $this->minkMock = $this->getMockBuilder(Mink::class)->disableOriginalConstructor()->getMock();

        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();

        $this->factory = new BehatLoggerFactory();
        $this->printer = new JsonIO($this->filesystemMock);

        $this->formatter = new BehatLogFormatter($this->factory, $this->minkMock, $this->printer, $this->output, $this->parameters);
    }

    /**
     * @test
     */
    public function testEmptySuite()
    {
        /**
         * @var $suiteEventMock SuiteTested|PHPUnit_Framework_MockObject_MockObject
         */
        $suiteEventMock = $this->getMockBuilder(SuiteTested::class)->disableOriginalConstructor()->getMock();

        $suiteMock = $this->getMockBuilder(Suite::class)->disableOriginalConstructor()->getMock();
        $suiteMock->expects($this->any())->method('getName')->will($this->returnValue('default'));

        $suiteEventMock->expects($this->any())->method('getSuite')->will($this->returnValue($suiteMock));

        $this->filesystemMock->expects($this->once())->method('mkdir')->with('.');
        $this->filesystemMock->expects($this->once())->method('dumpFile')->with('./default.json','{"suites":[{"name":"default","features":[]}]}');

        $this->formatter->onBeforeSuiteTested($suiteEventMock);
        $this->formatter->onAfterSuiteTested($suiteEventMock);
    }

    /**
     * @test
     */
    public function testEmptyFeature()
    {
        /**
         * @var $suiteEventMock SuiteTested|PHPUnit_Framework_MockObject_MockObject
         * @var $featureEventMock FeatureTested|PHPUnit_Framework_MockObject_MockObject
         */
        $suiteEventMock = $this->getMockBuilder(SuiteTested::class)->disableOriginalConstructor()->getMock();
        $featureEventMock = $this->getMockBuilder(FeatureTested::class)->disableOriginalConstructor()->getMock();

        $suiteMock = $this->getMockBuilder(Suite::class)->disableOriginalConstructor()->getMock();
        $suiteMock->expects($this->any())->method('getName')->will($this->returnValue('default'));

        $featureMock = $this->getMockBuilder(FeatureNode::class)->disableOriginalConstructor()->getMock();

        $featureMock->expects($this->any())->method('getFile')->will($this->returnValue('feature.file'));
        $featureMock->expects($this->any())->method('getTitle')->will($this->returnValue('feature title'));
        $featureMock->expects($this->any())->method('getDescription')->will($this->returnValue(null));
        $featureMock->expects($this->any())->method('getLanguage')->will($this->returnValue('en'));

        $suiteEventMock->expects($this->any())->method('getSuite')->will($this->returnValue($suiteMock));
        $featureEventMock->expects($this->any())->method('getFeature')->will($this->returnValue($featureMock));

        $this->filesystemMock->expects($this->once())->method('mkdir')->with('.');
        $this->filesystemMock->expects($this->once())->method('dumpFile')->with('./default.json','{"suites":[{"name":"default","features":{"feature.file":{"title":"feature title","filename":"feature.file","description":null,"language":"en","scenarios":[]}}}]}');

        $this->formatter->onBeforeSuiteTested($suiteEventMock);
        $this->formatter->onBeforeFeatureTested($featureEventMock);
        $this->formatter->onAfterSuiteTested($suiteEventMock);
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function testScenario(){
        /**
         * @var $suiteEventMock SuiteTested|PHPUnit_Framework_MockObject_MockObject
         * @var $featureEventMock FeatureTested|PHPUnit_Framework_MockObject_MockObject
         * @var $scenarioEventMock ScenarioTested|PHPUnit_Framework_MockObject_MockObject
         */
        $suiteEventMock = $this->getMockBuilder(SuiteTested::class)->disableOriginalConstructor()->getMock();
        $featureEventMock = $this->getMockBuilder(FeatureTested::class)->disableOriginalConstructor()->getMock();
        $scenarioEventMock = $this->getMockBuilder(ScenarioTested::class)->disableOriginalConstructor()->getMock();

        $suiteMock = $this->getMockBuilder(Suite::class)->disableOriginalConstructor()->getMock();
        $suiteMock->expects($this->any())->method('getName')->will($this->returnValue('default'));

        $featureMock = $this->getMockBuilder(FeatureNode::class)->disableOriginalConstructor()->getMock();
        $featureMock->expects($this->any())->method('getFile')->will($this->returnValue('feature.file'));
        $featureMock->expects($this->any())->method('getTitle')->will($this->returnValue('feature title'));
        $featureMock->expects($this->any())->method('getDescription')->will($this->returnValue(null));
        $featureMock->expects($this->any())->method('getLanguage')->will($this->returnValue('en'));

        $scenarioMock = $this->getMockBuilder(ScenarioNode::class)->disableOriginalConstructor()->getMock();
        $scenarioMock->expects($this->any())->method('getTitle')->will($this->returnValue('scenario title'));
        $scenarioMock->expects($this->any())->method('getTags')->will($this->returnValue(['tag1','tag2']));
        $scenarioMock->expects($this->any())->method('getSteps')->will($this->returnValue([]));

        $suiteEventMock->expects($this->any())->method('getSuite')->will($this->returnValue($suiteMock));
        $featureEventMock->expects($this->any())->method('getFeature')->will($this->returnValue($featureMock));
        $scenarioEventMock->expects($this->any())->method('getFeature')->will($this->returnValue($featureMock));
        $scenarioEventMock->expects($this->any())->method('getScenario')->will($this->returnValue($scenarioMock));

        $this->minkMock->expects($this->any())->method('getDefaultSessionName')->will($this->returnValue(null));

        $this->filesystemMock->expects($this->once())->method('mkdir')->with('.');
        $this->filesystemMock->expects($this->once())->method('dumpFile')->with('./default.json','{"suites":[{"name":"default","features":{"feature.file":{"title":"feature title","filename":"feature.file","description":null,"language":"en","scenarios":{"scenario title":{"title":"scenario title","tags":["tag1","tag2"],"steps":[],"results":{"unknown":{"environment":"unknown","stepResults":[],"duration":"0.00"}}}}}}}]}');

        $this->formatter->onBeforeSuiteTested($suiteEventMock);
        $this->formatter->onBeforeFeatureTested($featureEventMock);
        $this->formatter->onBeforeScenarioTested($scenarioEventMock);
        $this->formatter->onAfterScenarioTested($scenarioEventMock);
        $this->formatter->onAfterSuiteTested($suiteEventMock);
    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function testStep(){
        /**
         * @var $suiteEventMock SuiteTested|PHPUnit_Framework_MockObject_MockObject
         * @var $featureEventMock FeatureTested|PHPUnit_Framework_MockObject_MockObject
         * @var $scenarioEventMock ScenarioTested|PHPUnit_Framework_MockObject_MockObject
         */
        $suiteEventMock = $this->getMockBuilder(SuiteTested::class)->disableOriginalConstructor()->getMock();
        $featureEventMock = $this->getMockBuilder(FeatureTested::class)->disableOriginalConstructor()->getMock();
        $scenarioEventMock = $this->getMockBuilder(ScenarioTested::class)->disableOriginalConstructor()->getMock();

        $suiteMock = $this->getMockBuilder(Suite::class)->disableOriginalConstructor()->getMock();
        $suiteMock->expects($this->any())->method('getName')->will($this->returnValue('default'));

        $featureMock = $this->getMockBuilder(FeatureNode::class)->disableOriginalConstructor()->getMock();
        $featureMock->expects($this->any())->method('getFile')->will($this->returnValue('feature.file'));
        $featureMock->expects($this->any())->method('getTitle')->will($this->returnValue('feature title'));
        $featureMock->expects($this->any())->method('getDescription')->will($this->returnValue(null));
        $featureMock->expects($this->any())->method('getLanguage')->will($this->returnValue('en'));

        $scenarioMock = $this->getMockBuilder(ScenarioNode::class)->disableOriginalConstructor()->getMock();

        $stepMock = $this->getMockBuilder(StepNode::class)->disableOriginalConstructor()->getMock();

        $scenarioMock->expects($this->any())->method('getTitle')->will($this->returnValue('scenario title'));
        $scenarioMock->expects($this->any())->method('getTags')->will($this->returnValue(['tag1','tag2']));
        $scenarioMock->expects($this->any())->method('getSteps')->will($this->returnValue([$stepMock]));

        $stepMock->expects($this->any())->method('getLine')->will($this->returnValue(1));
        $stepMock->expects($this->any())->method('getText')->will($this->returnValue("the user 'test' exists"));
        $stepMock->expects($this->any())->method('getKeyword')->will($this->returnValue('Given'));
        $stepMock->expects($this->any())->method('getArguments')->will($this->returnValue([]));

        $suiteEventMock->expects($this->any())->method('getSuite')->will($this->returnValue($suiteMock));
        $featureEventMock->expects($this->any())->method('getFeature')->will($this->returnValue($featureMock));
        $scenarioEventMock->expects($this->any())->method('getFeature')->will($this->returnValue($featureMock));
        $scenarioEventMock->expects($this->any())->method('getScenario')->will($this->returnValue($scenarioMock));

        $this->minkMock->expects($this->any())->method('getDefaultSessionName')->will($this->returnValue(null));

        $this->filesystemMock->expects($this->once())->method('mkdir')->with('.');
        $this->filesystemMock->expects($this->once())->method('dumpFile')->with('./default.json','{"suites":[{"name":"default","features":{"feature.file":{"title":"feature title","filename":"feature.file","description":null,"language":"en","scenarios":{"scenario title":{"title":"scenario title","tags":["tag1","tag2"],"steps":{"1":{"line":1,"text":"the user \'test\' exists","keyword":"Given","arguments":[]}},"results":{"unknown":{"environment":"unknown","stepResults":[],"duration":"0.00"}}}}}}}]}');

        $this->formatter->onBeforeSuiteTested($suiteEventMock);
        $this->formatter->onBeforeFeatureTested($featureEventMock);
        $this->formatter->onBeforeScenarioTested($scenarioEventMock);
        $this->formatter->onAfterScenarioTested($scenarioEventMock);
        $this->formatter->onAfterSuiteTested($suiteEventMock);
    }
}