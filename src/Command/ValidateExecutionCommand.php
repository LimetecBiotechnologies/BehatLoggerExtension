<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 28.08.18
 * Time: 15:53
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatSuite;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateExecutionCommand extends ContainerAwareCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $printer = $this->getContainer()->get('json.printer');
        /**
         * @var $actualSuites BehatSuite[]
         * @var $expectedSuites BehatSuite[]
         */
        $actualSuites = $printer->toObjects($input->getArgument('actual-file'));
        $expectedSuites = $printer->toObjects($input->getArgument('expected-file'));

        $err = 0;
        foreach ($expectedSuites as $expectedSuite) {
            foreach ($expectedSuite->getFeatures() as $feature) {
                foreach ($feature->getScenarios() as $scenario) {
                    if ($this->checkScenarioTags($scenario, $input->getOption('tags'))) {
                        $actualScenario = $this->findScenario($actualSuites, $scenario->getTitle(), $feature->getFilename());
                        if ($actualScenario === null) {
                            $output->writeln('the scenario "' . $scenario->getTitle() . '" was not executed!');
                            $err = -1;
                        } else if (count($actualScenario->getResults()) === 0) {
                            $output->writeln('the scenario "' . $scenario->getTitle() . '" has no environments!');
                            $err = -1;
                        } else {
                            $accepted = true;
                            foreach ($input->getOption('environments') as $environment) {
                                if (!$actualScenario->hasResult($environment)) {
                                    $output->writeln('the scenario "' . $scenario->getTitle() . '" was not executed on environment ' . $environment . '!');
                                    $err = -1;
                                    $accepted = false;
                                }
                            }
                            if ($accepted) {
                                $output->writeln('<info>the scenario "' . $scenario->getTitle() . '" was executed on all required environments (' . implode(",", $input->getOption('environments')) . ')</info>', OutputInterface::VERBOSITY_VERBOSE);
                            }
                        }
                    } else {
                        $output->writeln('<info>the scenario "' . $scenario->getTitle() . '" are skipped</info>', OutputInterface::VERBOSITY_VERBOSE);
                    }
                }
            }
        }

        $output->writeln('done.');

        return $err;
    }

    private function checkScenarioTags(BehatScenario $scenario, array $tags)
    {
        $accept = true;
        foreach ($tags as $tag) {
            $currentTag = $tag;
            if (substr($tag, 0, 1) === '~') {
                $currentTag = substr($tag, 1, strlen($tag));
                if ($scenario->hasTag($currentTag)) {
                    $accept = false;
                }
            } else {
                if (!$scenario->hasTag($currentTag)) {
                    $accept = false;
                }
            }
        }

        return $accept;
    }

    private function findScenario(array $suites, string $title, string $featureFile)
    {
        foreach ($suites as $suite) {
            /* @var $suite BehatSuite */
            foreach ($suite->getFeatures() as $feature) {
                if ($feature->getFilename() === $featureFile) {
                    if ($feature->hasScenario($title)) {
                        return $feature->getScenario($title);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure()
    {
        $this->setName('validate:execution')
            ->setDescription('compare two log files and check that all tests are executed')
            ->addArgument('actual-file',
                InputArgument::REQUIRED,
                'the actual log file')
            ->addArgument('expected-file',
                InputArgument::REQUIRED,
                'the expected log file')
            ->addOption('tags', 't',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'the behat tags to filter')
            ->addOption('environments', 'e',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'the execution environments')
            ->setHelp(<<<EOT
The <info>%command.name%</info> check that all tests are executed

Example (<comment>1</comment>): <info>check all tests without the tags "javascript" and "nightly" are executed in environment unknown</info>

    $ %command.full_name% expected.json actual.json --tags=~javascript --tags=~nightly --environments=unknown

Example (<comment>2</comment>): <info>check all tests with the tag "javascript" and without "nightly" are executed in environment firefox and chrome</info>

    $ %command.full_name% expected.json actual.json --tags=javascript --tags=~nightly --environments=firefox --environments=chrome
EOT
            );
    }
}