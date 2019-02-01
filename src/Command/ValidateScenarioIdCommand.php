<?php
/**
 * Created by PhpStorm.
 * User: seredos
 * Date: 12.01.19
 * Time: 01:22
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Exception\TestRailException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateScenarioIdCommand extends AbstractTestRailCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $printer = $this->getContainer()->get('json.printer');
        /* @var $suites BehatSuite[] */
        $suites = $printer->toObjects($input->getArgument('file'));
        $identifier = $this->getTestRailIdentifierOptions($input);

        $returnCode = 0;
        $allIds = [];

        foreach ($suites as $suite) {
            foreach ($suite->getFeatures() as $feature) {
                foreach ($feature->getScenarios() as $scenario) {
                    $id = null;
                    try {
                        $id = $scenario->getTestRailId($identifier['identifier-regex']);
                    } catch (TestRailException $e) {
                        $output->writeln('<error>'.$e->getMessage().'</error>');
                    }

                    if ($id === null) {
                        $returnCode = 1;
                    } else if (isset($allIds[$id])) {
                        $output->writeln('<error>the following testrail id are already defined in another scenario</error>');
                        $output->writeln('<error>source:</error>');
                        $output->writeln('<error>' . $allIds[$id]->getTitle() . '</error>');
                        $output->writeln('<error>target:</error>');
                        $output->writeln('<error>' . $scenario->getTitle() . '</error>');
                        $output->writeln('');
                        $returnCode = 1;
                    } else {
                        $allIds[$id] = $scenario;
                    }
                }
            }
        }

        ksort($allIds);
        end($allIds);
        $lastId = key($allIds);
        $output->writeln('<info>------------------------------------------------------------------</info>');
        $output->writeln('<info>NEXT AVAILABLE TESTRAIL-ID: ' . ($lastId + 1) . '</info>');
        $output->writeln('<info>------------------------------------------------------------------</info>');

        if($returnCode === 0){
            $output->writeln('done.');
        }

        return $returnCode;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure()
    {
        $this->setIdentifierOptions();
        $this->setName('validate:scenario:id')
            ->setDescription('check that all scenarios in the log-file has an unique id')
            ->addArgument('file',
                InputArgument::REQUIRED,
                'the log file')
            ->setHelp(<<<EOT
The <info>%command.name%</info> check that all scenarios have an unique id

Example (<comment>1</comment>): <info>check all scenarios</info>

    $ %command.full_name% default.json
EOT
            );
    }
}