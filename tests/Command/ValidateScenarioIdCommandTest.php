<?php

use seretos\BehatLoggerExtension\Command\ValidateScenarioIdCommand;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Created by PhpStorm.
 * User: seredos
 * Date: 13.01.19
 * Time: 00:38
 */

class ValidateScenarioIdCommandTest extends KernelTestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function execute_withoutError(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateScenarioIdCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:scenario:id');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'file' => 'tests/logs/validate_title/simple.json',
            '--identifier_tag_regex' => '/^testrail-([0-9]*)$/'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("done.",$output);
        $this->assertEquals(0,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withoutTitle(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateScenarioIdCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:scenario:id');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'file' => 'tests/logs/validate_title/no_title.json'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("the following scenario has no testrail id",$output);
        $this->assertContains("second scenario in second feature",$output);
        $this->assertContains("NEXT AVAILABLE TESTRAIL-ID: 4",$output);
        $this->assertEquals(1,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withDoubleTitle(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateScenarioIdCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:scenario:id');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'file' => 'tests/logs/validate_title/double_title.json'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();
        $this->assertContains("the following testrail id are already defined in another scenario",$output);
        $this->assertContains('first scenario in first feature',$output);
        $this->assertContains("NEXT AVAILABLE TESTRAIL-ID: 5",$output);
        $this->assertEquals(1,$commandTester->getStatusCode());
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