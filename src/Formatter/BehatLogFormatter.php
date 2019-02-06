<?php

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 16:01
 */
namespace seretos\BehatLoggerExtension\Formatter;

use Behat\Behat\EventDispatcher\Event\AfterOutlineTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\BeforeOutlineTested;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
use Behat\Behat\Tester\Result\SkippedStepResult;
use Behat\Behat\Tester\Result\UndefinedStepResult;
use Behat\Gherkin\Node\BackgroundNode;
use Behat\Gherkin\Node\ScenarioInterface;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Mink;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use ReflectionClass;
use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatStepResult;
use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Service\BehatLoggerFactory;
use seretos\BehatLoggerExtension\Service\ScreenshotPrinter;

class BehatLogFormatter implements Formatter
{
    /**
     * @var BehatSuite
     */
    private $currentSuite;
    /**
     * @var BehatScenario
     */
    private $currentScenario;
    /**
     * @var BehatStepResult[]
     */
    private $currentStepResults;
    /**
     * @var OutputPrinter
     */
    private $printer;
    /**
     * @var string
     */
    private $output;
    /**
     * @var string
     */
    private $message;
    /**
     * @var string
     */
    private $browser;
    /**
     * @var Mink
     */
    private $mink;
    /**
     * @var BehatLoggerFactory
     */
    private $factory;
    private $startTime = 0;
    /**
     * @var ScreenshotPrinter
     */
    private $screenshotPrinter;

    public function __construct(BehatLoggerFactory $factory, Mink $mink,OutputPrinter $printer, ScreenshotPrinter $screenshotPrinter, string $output, string $message, array $parameters)
    {
        $this->currentSuite = null;
        $this->printer = $printer;
        $this->output = rtrim($output, '/').'/';
        $this->message = $message;
        $this->mink = $mink;
        $this->browser = $parameters['browser_name'];
        $this->factory = $factory;
        $this->screenshotPrinter = $screenshotPrinter;
    }

    /**
     * @param SuiteTested $event
     */
    public function onBeforeSuiteTested(SuiteTested $event) {
        $this->currentSuite = $this->factory->createSuite($event->getSuite()->getName());
    }

    /**
     * @param SuiteTested $event
     */
    public function onAfterSuiteTested(SuiteTested $event) {
        if($event !== null) {
            $file = $this->currentSuite->getName().'.json';
            $this->printer->setOutputPath($this->output.$file);
            $this->printer->write([$this->currentSuite]);
        }
    }

    /**
     * @param FeatureTested $event
     */
    public function onBeforeFeatureTested(FeatureTested $event) {
        $feature = $this->factory->createFeature(realpath($event->getFeature()->getFile()),
            $event->getFeature()->getTitle(),
            $event->getFeature()->getDescription(),
            $event->getFeature()->getLanguage());
        $this->currentSuite->addFeature($feature);
    }

    /**
     * @param ScenarioTested $event
     * @throws \ReflectionException
     */
    public function onBeforeScenarioTested(ScenarioTested $event) {
        $browser = $this->getBrowser();
        $tags = $event->getScenario()->getTags();
        if(is_array($event->getFeature()->getTags())) {
            $tags = array_merge($tags, $event->getFeature()->getTags());
        }
        $scenario = $this->factory->createScenario($event->getScenario()->getTitle(),$tags);
        $scenarioResult = $this->factory->createResult($browser,$this->message);
        $scenario->addResult($scenarioResult);

        $this->importSteps($scenario,
            $event->getScenario(),
            $event->getFeature()->getBackground());

        $feature = $this->currentSuite->getFeature(realpath($event->getFeature()->getFile()));

        $feature->addScenario($scenario);
        $this->currentScenario = $scenario;
        $this->currentStepResults = [];
        $this->startTime = microtime(true);
    }

    /**
     * @param ScenarioTested $event
     * @throws \ReflectionException
     */
    public function onAfterScenarioTested(ScenarioTested $event) {
        $browser = $this->getBrowser();
        $result = $this->currentScenario->getResult($browser);
        $duration = microtime(true) - $this->startTime;
        $result->setDuration($duration);
        foreach($this->currentStepResults as $stepResult){
            $result->addStepResult($stepResult);
        }
        $this->currentStepResults = [];
        $this->currentScenario = null;
    }

    /**
     * @param AfterStepTested $event
     */
    public function onAfterStepTested(AfterStepTested $event) {
        $file = null;
        $message = null;
        if(!$event->getTestResult()->isPassed()
            && $event->getTestResult()->getResultCode() == 99
            && $this->mink->getSession()->getDriver() instanceof Selenium2Driver){
            $screenshot = $this->mink->getSession()->getScreenshot();
            $file = $this->screenshotPrinter->takeScreenshot($this->output,$this->browser,$screenshot);
        }

        $result = $event->getTestResult();

        if($result instanceof UndefinedStepResult){
            $message = 'undefined step '.$event->getStep()->getText();
        }else if($result instanceof ExecutedStepResult){
            if($result->getException()){
                $message = $result->getException()->getMessage();
            }else{
                $message = $result->getCallResult()->getStdOut();
            }
        }
        //else if($result instanceof SkippedStepResult){
        //    $message = 'step skipped!';
        //}

        $stepResult = $this->factory->createStepResult($event->getStep()->getLine(),$event->getTestResult()->isPassed(),$file,$message);
        $this->currentStepResults[] = $stepResult;
    }

    /**
     * @param BeforeOutlineTested $event
     * @throws \ReflectionException
     */
    public function onBeforeOutlineTested(BeforeOutlineTested $event) {
        $browser = $this->getBrowser();
        $scenario = $this->factory->createScenario($event->getOutline()->getTitle(),$event->getOutline()->getTags());
        $scenarioResult = $this->factory->createResult($browser);
        $scenario->addResult($scenarioResult);

        $this->importSteps($scenario,
            $event->getOutline(),
            $event->getFeature()->getBackground());

        $feature = $this->currentSuite->getFeature(realpath($event->getFeature()->getFile()));

        $feature->addScenario($scenario);
        $this->currentScenario = $scenario;
        $this->currentStepResults = [];
    }

    /**
     * @param AfterOutlineTested $event
     * @throws \ReflectionException
     */
    public function onAfterOutlineTested(AfterOutlineTested $event) {
        $browser = $this->getBrowser();
        $result = $this->factory->createResult($browser);
        foreach($this->currentScenario->getSteps() as $step){
            $stepResult = $this->factory->createStepResult($step->getLine(),$event->getTestResult()->isPassed());
            $result->addStepResult($stepResult);
        }
        $this->currentScenario->addResult($result);
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return array(
//            'tester.exercise_completed.before' => 'onBeforeExercise',
//            'tester.exercise_completed.after' => 'onAfterExercise',
            'tester.suite_tested.before' => 'onBeforeSuiteTested',
            'tester.suite_tested.after' => 'onAfterSuiteTested',
            'tester.feature_tested.before' => 'onBeforeFeatureTested',
            //'tester.feature_tested.after' => 'onAfterFeatureTested',
            'tester.scenario_tested.before' => 'onBeforeScenarioTested',
            'tester.scenario_tested.after' => 'onAfterScenarioTested',
            'tester.outline_tested.before' => 'onBeforeOutlineTested',
            'tester.outline_tested.after' => 'onAfterOutlineTested',
            'tester.step_tested.after' => 'onAfterStepTested',
        );
    }

    /**
     * Returns formatter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return "logger";
    }

    /**
     * Returns formatter description.
     *
     * @return string
     */
    public function getDescription()
    {
        return "log the test results in json format";
    }

    /**
     * Returns formatter output printer.
     *
     * @return OutputPrinter
     */
    public function getOutputPrinter()
    {
        return $this->printer;
    }

    /**
     * Sets formatter parameter.
     *
     * @param string $name
     * @param mixed $value
     */
    public function setParameter($name, $value)
    {
        // TODO: Implement setParameter() method.
    }

    /**
     * Returns parameter name.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return [];
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    private function getBrowser(){
        $browser = $this->browser;
        if($this->mink->getDefaultSessionName() === null || !$this->mink->getSession()->getDriver() instanceof Selenium2Driver){
            $browser = 'unknown';
        }else{
            /* @var $driver Selenium2Driver*/
            $driver = $this->mink->getSession()->getDriver();
            $reflection = new ReflectionClass(Selenium2Driver::class);
            $property = $reflection->getProperty('desiredCapabilities');
            $property->setAccessible(true);
            $values = $property->getValue($driver);
            if(isset($values['version']) && $values['version'] !== ''){
                $browser .= ' '.$values['version'];
            }
        }
        return $browser;
    }

    private function importSteps(BehatScenario $scenario,
                                 ScenarioInterface $scenarioNode,
                                 BackgroundNode $backgroundNode = null){
        if($backgroundNode!==null){
            foreach($backgroundNode->getSteps() as $step){
                $scenario->addStep($this->convertStep($step));
            }
        }
        foreach($scenarioNode->getSteps() as $step){
            $scenario->addStep($this->convertStep($step));
        }
    }

    private function convertStep(StepNode $step){
        $arguments = [];
        foreach ($step->getArguments() as $argument){
            if($argument instanceof TableNode){
                $arguments = $argument->getRows();
            }
        }
        $importStep = $this->factory->createStep($step->getLine(),$step->getText(),$step->getKeyword(),$arguments);
        return $importStep;
    }
}