<?php
/**
 * Created by PhpStorm.
 * User: seredos
 * Date: 12.01.19
 * Time: 01:22
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\BehatLoggerExtension\Entity\BehatSuite;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ValidateScenarioIdCommand extends ContainerAwareCommand
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

        $idRegex = '';
        if (file_exists('.testrail.yml')) {
            $config = Yaml::parseFile('.testrail.yml');
            if (isset($config['api']['identifier_tag_regex'])) {
                $idRegex = $config['api']['identifier_tag_regex'];
            }
        }
        if ($input->getOption('identifier_tag_regex') !== null) {
            $idRegex = $input->getOption('identifier_tag_regex');
        }
        if($idRegex === ''){
            $idRegex = '/^testrail-case-([0-9]*)$/';
            $output->writeln('<comment>id regex not setted, using default '.$idRegex.'</comment>');
        }

        $returnCode = 0;
        $allIds = [];

        foreach ($suites as $suite) {
            foreach ($suite->getFeatures() as $feature) {
                foreach ($feature->getScenarios() as $scenario) {
                    $id = $scenario->getTestRailId($idRegex, $output);
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
        $this->setName('validate:scenario:id')
            ->setDescription('check that all scenarios in the log-file has an unique id')
            ->addArgument('file',
                InputArgument::REQUIRED,
                'the log file')
            ->addOption('identifier_tag_regex', null, InputOption::VALUE_REQUIRED, 'the tag identifier regex')
            ->setHelp(<<<EOT
The <info>%command.name%</info> check that all scenarios have an unique id

Example (<comment>1</comment>): <info>check all scenarios</info>

    $ %command.full_name% default.json
EOT
            );
    }
}