<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 28.09.18
 * Time: 16:30
 */

namespace seretos\BehatLoggerExtension\Command;

use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Exception\TestRailException;
use seretos\BehatLoggerExtension\IO\JsonIO;
use seretos\BehatLoggerExtension\Service\TestRailSuiteImporter;
use seretos\testrail\Client;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestRailCasePushCommand extends AbstractTestRailCommand
{
    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setTestRailServerOptions();
        $this->setIdentifierOptions();
        $this->setSynchronizationOptions();
        $this->setName('testrail:push:cases')
            ->addArgument('suite',
                InputArgument::REQUIRED,
                'the suite name')
            ->addArgument('json-file',
                InputArgument::REQUIRED,
                'the behat json log file')
            ->setDescription('create an suite, testcases and sections in testrail')
            ->setHelp(<<<EOT
The <info>%command.name%</info> sends the cases to an testrail instance

Example (<comment>1</comment>): <info>send your result to testrail. create or update the suite testsuite. requires a .testrail.yml in the current working dir</info>

    $ %command.full_name% testSuite result.json

Example (<comment>2</comment>): <info>send your result to testrail. create or update the suite testsuite. without .testrail.yml</info>

    $ %command.full_name% testSuite result.json --server=http://your.testrail.instance/testrail \
                                                --user=yourUser \
                                                --passwod=yourPassword \
                                                --project=yourTestRailProject \
                                                --default-section=yourDefaultImportSection \
                                                --identifier-field=yourTestRailIdentifierField
EOT
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws TestRailException
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        $fields = $this->getFieldOptions();
        $priorities = $this->getPriorityOptions();

        $server = $this->getTestRailServerOptions($input);
        $identifier = $this->getTestRailIdentifierOptions($input);
        $synchronization = $this->getTestRailSynchronizationOptions($input);

        if($server['project'] === null){
            $output->writeln('<error>the project is required!</error>');
            return -1;
        }

        if($identifier['identifier-field'] === null){
            $output->writeln('<error>the identifier field is required!</error>');
            return -1;
        }

        if($synchronization['default-section'] === null){
            $output->writeln('<error>the default section is required!</error>');
            return -1;
        }

        $client = null;
        try{
            $client = Client::create($server['server'],$server['user'],$server['password']);
        }catch (\Throwable $e){
            $output->writeln('<error>cant connect to server '.$server['server'].'</error>');
            return -1;
        }

        $importer = new TestRailSuiteImporter($client,
            $server['project'],
            $input->getArgument('suite'),
            $fields,
            $priorities,
            $synchronization['title-field'],
            $identifier['identifier-regex'],
            $identifier['identifier-field'],$synchronization['default-section']);
        $importer->setTemplate($synchronization['template']);
        $importer->setType($synchronization['type']);

        /* @var $printer JsonIO*/
        $printer = $this->getContainer()->get('json.printer');
        $jsonSuites = $printer->toObjects($input->getArgument('json-file'));

        $cnt = 0;
        foreach ($jsonSuites as $currentSuite) {
            /* @var $currentSuite BehatSuite */
            $cnt += $currentSuite->getScenarioCount();
        }

        $output->writeln('<comment>sending scenario data to testrail!</comment>');
        $output->writeln('info:');
        $output->writeln('server: '.$server['server']);
        $output->writeln('user: '.$server['user']);
        $output->writeln('project: '.$server['project']);
        $output->writeln('');

        $progressBar = new ProgressBar($output, $cnt);
        $progressBar->setFormat(' %current%/%max% [%bar%] %message%');
        $progressBar->setMessage('start');
        foreach ($jsonSuites as $currentSuite){
            /* @var $currentSuite BehatSuite*/
            foreach ($currentSuite->getFeatures() as $feature){
                foreach ($feature->getScenarios() as $scenario) {
                    $importer->pushTest($scenario);
                    $progressBar->advance();
                    $progressBar->setMessage($feature->getFilename());
                }
            }
        }
        $progressBar->finish();
        $output->writeln('');

        return 0;
    }
}