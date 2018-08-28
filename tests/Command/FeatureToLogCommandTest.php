<?php

use seretos\BehatLoggerExtension\Command\FeatureToLogCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 28.08.18
 * Time: 10:58
 */

class FeatureToLogCommandTest extends KernelTestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function execute_withoutError(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new FeatureToLogCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('feature:to:log');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'suite' => 'test','--regex'=>['tests/logs/features/simple/']
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("want to combine the following features into ".getcwd()."/test.json",$output);
        $this->assertContains(getcwd()."/tests/logs/features/simple/feature1.feature",$output);
        $this->assertContains(getcwd()."/tests/logs/features/simple/feature2.feature",$output);
        $this->assertContains("done.",$output);

        $content = file_get_contents(getcwd().'/test.json');
        $assertContent = '{"suites":[{"name":"test","features":{"'.str_replace('/',"\/",getcwd()).'\/tests\/logs\/features\/simple\/feature2.feature":{"title":"second test feature","filename":"'.str_replace('/',"\/",getcwd()).'\/tests\/logs\/features\/simple\/feature2.feature","description":"this is the second test feature","language":"en","scenarios":{"first scenario in second feature":{"title":"first scenario in second feature","tags":[],"steps":{"5":{"line":5,"text":"The user \"test\" exists","keyword":"Given","arguments":[]},"6":{"line":6,"text":"i logged in as \"test\"","keyword":"And","arguments":[]},"7":{"line":7,"text":"i click button one","keyword":"When","arguments":[]},"8":{"line":8,"text":"i should see the text \"blubb\"","keyword":"Then","arguments":[]}},"results":[]},"second scenario in second feature":{"title":"second scenario in second feature","tags":[],"steps":{"11":{"line":11,"text":"The user \"test2\" exists","keyword":"Given","arguments":[]},"12":{"line":12,"text":"i logged in as \"test2\"","keyword":"And","arguments":[]},"13":{"line":13,"text":"i click button one","keyword":"When","arguments":[]},"14":{"line":14,"text":"i should see the text \"bla\"","keyword":"Then","arguments":[]}},"results":[]}}},"'.str_replace('/',"\/",getcwd()).'\/tests\/logs\/features\/simple\/feature1.feature":{"title":"first test feature","filename":"'.str_replace('/',"\/",getcwd()).'\/tests\/logs\/features\/simple\/feature1.feature","description":"this is the first test feature","language":"en","scenarios":{"first scenario in first feature":{"title":"first scenario in first feature","tags":[],"steps":{"5":{"line":5,"text":"The user \"test\" exists","keyword":"Given","arguments":[]},"6":{"line":6,"text":"i logged in as \"test\"","keyword":"And","arguments":[]},"7":{"line":7,"text":"i click button one","keyword":"When","arguments":[]},"8":{"line":8,"text":"i should see the text \"blubb\"","keyword":"Then","arguments":[]}},"results":[]},"second scenario in first feature":{"title":"second scenario in first feature","tags":[],"steps":{"11":{"line":11,"text":"The user \"test2\" exists","keyword":"Given","arguments":[]},"12":{"line":12,"text":"i logged in as \"test2\"","keyword":"And","arguments":[]},"13":{"line":13,"text":"i click button one","keyword":"When","arguments":[]},"14":{"line":14,"text":"i should see the text \"bla\"","keyword":"Then","arguments":[]}},"results":[]}}}}}]}';

        $this->assertEquals($assertContent, $content);
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withoutScenarios(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new FeatureToLogCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('feature:to:log');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'suite' => 'test','--regex'=>['tests/logs/features/empty/']
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("want to combine the following features into ".getcwd()."/test.json",$output);
        $this->assertContains(getcwd()."/tests/logs/features/empty/feature1.feature",$output);
        $this->assertContains("the file ".getcwd()."/tests/logs/features/empty/feature1.feature has no scenarios!",$output);
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withDoubleScenarios(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new FeatureToLogCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('feature:to:log');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'suite' => 'test','--regex'=>['tests/logs/features/double/']
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("want to combine the following features into ".getcwd()."/test.json",$output);
        $this->assertContains(getcwd()."/tests/logs/features/double/feature1.feature",$output);
        $this->assertContains("the scenario first scenario in first feature is defined more then once",$output);
    }

    /**
     * @return ContainerBuilder
     * @throws Exception
     */
    private function getContainer(){
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config'));
        $loader->load('services.yml');

        return $container;
    }
}