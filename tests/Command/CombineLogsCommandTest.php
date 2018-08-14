<?php

use seretos\BehatLoggerExtension\Command\CombineLogsCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 13.08.18
 * Time: 23:58
 */

class CombineLogsCommandTest extends KernelTestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function execute_withoutError(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new CombineLogsCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('combine:logs');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'suite' => 'test','--regex'=>['tests/logs/combine/simple/']
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("want to combine the following logs into ".getcwd()."/test.json",$output);
        $this->assertContains(getcwd()."/tests/logs/combine/simple/simple2.json",$output);
        $this->assertContains(getcwd()."/tests/logs/combine/simple/simple1.json",$output);
        $this->assertContains("done.",$output);

        $content = file_get_contents(getcwd().'/test.json');
        $this->assertEquals('{"suites":[{"name":"test","features":{"feature1.feature":{"title":"feature 1","filename":"feature1.feature","description":"this is feature one","language":"en","scenarios":{"scenario 1":{"title":"scenario 1","tags":[],"steps":[{"line":0,"text":"the user \"root\" exists","keyword":"Given","arguments":[]},{"line":1,"text":"i logged in as \"root\"","keyword":"And","arguments":[]}],"results":{"firefox":{"environment":"firefox","stepResults":[{"line":0,"passed":true,"screenshot":null},{"line":1,"passed":true,"screenshot":null}],"duration":"0.00"},"chrome":{"environment":"chrome","stepResults":[{"line":0,"passed":true,"screenshot":null},{"line":1,"passed":true,"screenshot":null}],"duration":"0.00"}}},"scenario 2":{"title":"scenario 2","tags":[],"steps":[{"line":0,"text":"the user \"test\" exists","keyword":"Given","arguments":[]},{"line":1,"text":"i logged in as \"test\"","keyword":"And","arguments":[]}],"results":{"firefox":{"environment":"firefox","stepResults":[{"line":0,"passed":true,"screenshot":null},{"line":1,"passed":true,"screenshot":null}],"duration":"0.00"}}}}}}}]}',
            $content);
    }

    /**
     * @test
     * @expectedException \seretos\BehatLoggerExtension\Exception\BehatLoggerException
     * @throws Exception
     */
    public function execute_withDoubleExecution(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new CombineLogsCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('combine:logs');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'suite' => 'test','--regex'=>['tests/logs/combine/double/']
        ),['interactive' => false]);
    }

    /**
     * @test
     * @expectedException \seretos\BehatLoggerExtension\Exception\BehatLoggerException
     * @throws Exception
     */
    public function execute_withInvalidStep(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new CombineLogsCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('combine:logs');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'suite' => 'test','--regex'=>['tests/logs/combine/invalid/']
        ),['interactive' => false]);
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