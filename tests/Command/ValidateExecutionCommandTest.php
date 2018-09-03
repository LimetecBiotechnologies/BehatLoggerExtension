<?php

use seretos\BehatLoggerExtension\Command\ValidateExecutionCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;

/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 31.08.18
 * Time: 09:42
 */

class ValidateExecutionCommandTest extends KernelTestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function execute_withoutEnvironments(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateExecutionCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:execution');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'expected-file' => 'tests/logs/execution/expected/simple.json',
            'actual-file' => 'tests/logs/execution/actual/simple_no_results.json'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("the scenario \"first scenario in first feature\" has no environments!",$output);
        $this->assertContains("the scenario \"second scenario in first feature\" has no environments!",$output);
        $this->assertContains("the scenario \"first scenario in second feature\" has no environments!",$output);
        $this->assertContains("the scenario \"second scenario in second feature\" has no environments!",$output);
        $this->assertContains("done.",$output);
        $this->assertEquals(-1,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withOneEnvironment(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateExecutionCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:execution');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'expected-file' => 'tests/logs/execution/expected/simple.json',
            'actual-file' => 'tests/logs/execution/actual/simple_results.json'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("done.",$output);
        $this->assertEquals(0,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_notExecuted(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateExecutionCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:execution');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'expected-file' => 'tests/logs/execution/expected/simple.json',
            'actual-file' => 'tests/logs/execution/actual/simple_not_executed.json'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("the scenario \"second scenario in second feature\" was not executed!",$output);
        $this->assertContains("done.",$output);
        $this->assertEquals(-1,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withInvalidEnvironment(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateExecutionCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:execution');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'expected-file' => 'tests/logs/execution/expected/simple.json',
            'actual-file' => 'tests/logs/execution/actual/simple_results.json',
            '--environments' => ['firefox','chrome']
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("the scenario \"first scenario in first feature\" was not executed on environment chrome!",$output);
        $this->assertContains("the scenario \"second scenario in first feature\" was not executed on environment chrome!",$output);
        $this->assertContains("the scenario \"first scenario in second feature\" was not executed on environment chrome!",$output);
        $this->assertContains("the scenario \"second scenario in second feature\" was not executed on environment chrome!",$output);
        $this->assertContains("done.",$output);
        $this->assertEquals(-1,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withGuotte(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateExecutionCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:execution');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'expected-file' => 'tests/logs/execution/expected/advanced.json',
            'actual-file' => 'tests/logs/execution/actual/advanced.json',
            '--tags' => ['~javascript','~nightly'],
            '--environments' => ['unknown']
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("done.",$output);
        $this->assertEquals(0,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withJavascript(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateExecutionCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:execution');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'expected-file' => 'tests/logs/execution/expected/advanced.json',
            'actual-file' => 'tests/logs/execution/actual/advanced.json',
            '--tags' => ['javascript','~nightly'],
            '--environments' => ['firefox','chrome']
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("done.",$output);
        $this->assertEquals(0,$commandTester->getStatusCode());
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