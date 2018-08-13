<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 12.08.18
 * Time: 00:18
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\BehatLoggerExtension\Entity\BehatFeature;
use seretos\BehatLoggerExtension\Entity\BehatResult;
use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatStep;
use seretos\BehatLoggerExtension\Entity\BehatStepResult;
use seretos\BehatLoggerExtension\Entity\BehatSuite;
use seretos\BehatLoggerExtension\Exception\BehatLoggerException;
use seretos\BehatLoggerExtension\IO\JsonIO;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class CombineLogsCommand extends ContainerAwareCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     * @throws BehatLoggerException
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        $files = $this->findJsonFiles($input->getOption('regex'));
        $outputFile = rtrim($input->getOption('output'),'/').'/'.$input->getArgument('suite').'.json';
        $output->writeln('want to combine the following logs into '.$outputFile);
        $this->outputJsonFiles($output,$files);

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Continue with this action? [Y/n]', true);

        if (!$helper->ask($input, $output, $question)) {
            return -1;
        }

        $suite = new BehatSuite($input->getArgument('suite'));

        foreach($files as $file){
            $this->importFile($file,$suite);
        }

        /* @var $printer JsonIO*/
        $printer = $this->getContainer()->get('json.printer');

        $printer->setOutputPath($outputFile);
        $printer->write([$suite]);

        $output->writeln('done.');
        return 0;
    }

    /**
     * @param $file
     * @param BehatSuite $suite
     * @throws BehatLoggerException
     */
    private function importFile($file, BehatSuite $suite){
        /* @var $printer JsonIO*/
        $printer = $this->getContainer()->get('json.printer');
        $suites = $printer->toObjects($file);
        foreach($suites as $currentSuite){
            /* @var $currentSuite BehatSuite*/
            foreach($currentSuite->getFeatures() as $feature){
                $behatFeature = null;
                if(!$suite->hasFeature($feature->getFilename())){
                    $behatFeature = new BehatFeature($feature->getFilename(),$feature->getTitle(),$feature->getDescription(),$feature->getLanguage());
                    $suite->addFeature($behatFeature);
                }else{
                    $behatFeature = $suite->getFeature($feature->getFilename());
                }
                foreach($feature->getScenarios() as $currentScenario){
                    $behatScenario = null;
                    if(!$behatFeature->hasScenario($currentScenario->getTitle())){
                        $behatScenario = new BehatScenario($currentScenario->getTitle(),$currentScenario->getTags());
                        $behatFeature->addScenario($behatScenario);
                    }else{
                        $behatScenario = $behatFeature->getScenario($currentScenario->getTitle());
                    }
                    $this->importBehatSteps($currentScenario,$behatScenario);
                    foreach($currentScenario->getResults() as $currentResult){
                        if($behatScenario->hasResult($currentResult->getEnvironment())){
                            throw new BehatLoggerException('the scenario "'.$currentScenario->getTitle().'" in feature "'.$behatFeature->getFilename().'" was executed more then once in environment "'.$currentResult->getEnvironment().'"'.PHP_EOL.'Error found in file "'.$file.'"');
                        }else{
                            $behatResult = new BehatResult($currentResult->getEnvironment());
                            $behatResult->setDuration($currentResult->getDuration());
                            $behatScenario->addResult($behatResult);
                        }
                        $this->importBehatStepResult($currentResult,$behatResult);
                    }
                }
            }
        }
    }

    public function importBehatStepResult(BehatResult $source, BehatResult $target){
        foreach($source->getStepResults() as $sourceStepResult){
            if(!$target->hasStepResult($sourceStepResult->getLine())){
                $targetStepResult = new BehatStepResult($sourceStepResult->getLine(),$sourceStepResult->isPassed(),$sourceStepResult->getScreenshot());
                $target->addStepResult($targetStepResult);
            }
        }
    }

    /**
     * @param BehatScenario $source
     * @param BehatScenario $target
     * @throws BehatLoggerException
     */
    public function importBehatSteps(BehatScenario $source, BehatScenario $target){
        foreach($source->getSteps() as $sourceStep){
            if($target->hasStep($sourceStep->getLine())){
                $targetStep = $target->getStep($sourceStep->getLine());
                if($targetStep->getKeyword() !== $sourceStep->getKeyword()
                    || $targetStep->getText() !== $sourceStep->getText()
                    || $targetStep->getArguments() !== $sourceStep->getArguments()){
                    $message = 'source: '.$sourceStep->getKeyword().' '.$sourceStep->getText().PHP_EOL.implode($sourceStep->getArguments()).PHP_EOL;
                    $message .= 'target: '.$targetStep->getKeyword().' '.$targetStep->getText().PHP_EOL.implode($targetStep->getArguments()).PHP_EOL;
                    throw new BehatLoggerException('the given step is invalid'.PHP_EOL.$message);
                }
            }else{
                $targetStep = new BehatStep($sourceStep->getLine(),$sourceStep->getText(),$sourceStep->getKeyword(),$sourceStep->getArguments());
                $target->addStep($targetStep);
            }
        }
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('combine:logs')
            ->setDescription('combine multiple json files to one json file with one behat suite')
            ->addArgument('suite',
                InputArgument::REQUIRED,
                'the combined suite name')
            ->addOption('output',
                'o',
                InputOption::VALUE_REQUIRED,
                'the combined log path',
                getcwd())
            ->addOption('regex',
                'r',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'a regex path for json file detection',
                [getcwd()])
            ->setHelp(<<<EOT
The <info>%command.name%</info> combine json files to one json file with one suite

Example (<comment>1</comment>): <info>combine all files in build/*.json to test/default.json with suite name default</info>

    $ %command.full_name% default --output=test/ --regex=build/
EOT
            );;
    }

    private function outputJsonFiles(OutputInterface $output, array $files){
        $table = new Table($output);
        foreach($files as $file) {
            $table->addRow([$file]);
        }
        $table->render();
    }

    private function findJsonFiles(array $regex){
        $files = [];
        $finder = new Finder();
        foreach ($regex as $reg) {
            foreach($finder->in($reg)->name('*.json')->files() as $file){
                /* @var $file SplFileInfo*/
                $json = json_decode($file->getContents(),true);
                if(isset($json['suites'])) {
                    $files[] = $file->getRealPath();
                }
            }
        }
        return $files;
    }
}