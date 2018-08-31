<?php

use seretos\BehatLoggerExtension\Command\ValidateScenarioTitleCommand;
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
 * Time: 14:48
 */

class ValidateScenarioTitleCommandTest extends KernelTestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function execute_withoutError(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateScenarioTitleCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:scenario:title');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'file' => 'tests/logs/validate_title/simple.json'
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

        $combineLogsCommand = new ValidateScenarioTitleCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:scenario:title');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'file' => 'tests/logs/validate_title/no_title.json'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();

        $this->assertContains("a scenario in file feature1.feature has no title!",$output);
        $this->assertContains("done.",$output);
        $this->assertEquals(-1,$commandTester->getStatusCode());
    }

    /**
     * @test
     * @throws Exception
     */
    public function execute_withDoubleTitle(){
        $application = new Application();
        $container = $this->getContainer();

        $combineLogsCommand = new ValidateScenarioTitleCommand();
        $combineLogsCommand->setContainer($container);

        $application->add($combineLogsCommand);

        $command = $application->find('validate:scenario:title');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array(
            'command'  => $command->getName(),
            'file' => 'tests/logs/validate_title/double_title.json'
        ),['interactive' => false]);

        $output = $commandTester->getDisplay();
        $this->assertContains("the scenario first scenario in first feature in file feature2.feature is already defined in another feature file!",$output);
        $this->assertContains("done.",$output);
        $this->assertEquals(-1,$commandTester->getStatusCode());
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