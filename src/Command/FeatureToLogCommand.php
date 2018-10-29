<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 14.08.18
 * Time: 18:25
 */

namespace seretos\BehatLoggerExtension\Command;


use Behat\Gherkin\Node\TableNode;
use seretos\BehatLoggerExtension\IO\JsonIO;
use seretos\BehatLoggerExtension\Service\BehatLoggerFactory;
use SplFileInfo;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class FeatureToLogCommand extends ContainerAwareCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        /* @var $factory BehatLoggerFactory*/
        $factory = $this->getContainer()->get('behat.logger.factory');
        $parser = $factory->createBehatParser();
        $outputFile = rtrim($input->getOption('output'),'/').'/'.$input->getArgument('suite').'.json';

        $files = $this->findFeatureFiles($input->getOption('regex'));

        if(count($files) === 0){
            $output->writeln('<error>no feature files found!</error>');
            return -1;
        }

        $output->writeln('want to combine the following features into '.$outputFile);
        $this->outputFeatureFiles($output,$files);

        $behatSuite = $factory->createSuite($input->getArgument('suite'));

        foreach($files as $file){
            $language = 'en';

            $fileContent = file_get_contents($file);
            $feature = $parser->parse($fileContent);

            if($feature === null || $feature->getScenarios() === null || count($feature->getScenarios()) === 0){
                $output->writeln('<error>the file '.$file.' has no scenarios!</error>');
                return -1;
            }else{
                $language = $feature->getLanguage();

                if(!$behatSuite->hasFeature($file)){
                    $behatFeature = $factory->createFeature($file,$feature->getTitle(),$feature->getDescription(),$language);
                    $behatSuite->addFeature($behatFeature);
                }else{
                    $output->writeln('<error>the feature file '.$file.' is defined more then once</error>');
                    return -1;
                }

                foreach($feature->getScenarios() as $scenario){
                    if(!$behatFeature->hasScenario($scenario->getTitle())){
                        $tags = $scenario->getTags();
                        if(is_array($feature->getTags())) {
                            $tags = array_merge($tags, $feature->getTags());
                        }
                        $behatScenario = $factory->createScenario($scenario->getTitle(),$tags);
                        $behatFeature->addScenario($behatScenario);
                    }else{
                        $output->writeln('<error>the scenario '.$scenario->getTitle().' is defined more then once</error>');
                        return -1;
                    }

                    foreach($scenario->getSteps() as $step){
                        if(!$behatScenario->hasStep($step->getLine())){
                            $arguments = [];
                            foreach ($step->getArguments() as $argument){
                                if($argument instanceof TableNode){
                                    $arguments = $argument->getRows();
                                }
                            }
                            $behatStep = $factory->createStep($step->getLine(),$step->getText(),$step->getKeyword(),$arguments);
                            $behatScenario->addStep($behatStep);
                        }
                    }
                }
            }
            $search = $factory->getKeywords()[$language]['scenario'].'|'.$factory->getKeywords()[$language]['scenario_outline'];
            preg_match_all('/#[\h]*('.$search.'):/', $fileContent, $matches, PREG_SET_ORDER, 0);
            if(count($matches)>0){
                $output->writeln('<error>the file '.$file.' has commented scenarios</error>');
                return -1;
            }
            preg_match_all('/('.$factory->getKeywords()[$language]['scenario_outline'].'):/', $fileContent, $matches, PREG_SET_ORDER, 0);
            if(count($matches)>0){
                $output->writeln('<error>the keyword '.$factory->getKeywords()[$language]['scenario_outline'].' is not allowed!</error>');
                return -1;
            }
        }

        /* @var $printer JsonIO*/
        $printer = $this->getContainer()->get('json.printer');

        $printer->setOutputPath($outputFile);
        $printer->write([$behatSuite]);

        $output->writeln('done.');

        return 0;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('feature:to:log')
            ->setDescription('combine feature files to one json file with one suite and without results')
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
The <info>%command.name%</info> combine feature files to one json file with one suite

Example (<comment>1</comment>): <info>combine all files in features/*.feature to test/default.json with suite name default</info>

    $ %command.full_name% default --output=test/ --regex=features/
EOT
            );
    }

    private function outputFeatureFiles(OutputInterface $output, array $files){
        $table = new Table($output);
        foreach($files as $file) {
            $table->addRow([$file]);
        }
        $table->render();
    }

    private function findFeatureFiles(array $regex){
        $files = [];
        $finder = new Finder();
        foreach ($regex as $reg) {
            foreach($finder->in($reg)->name('*.feature')->files() as $file){
                /* @var $file SplFileInfo*/
                $files[] = $file->getRealPath();
            }
        }
        return $files;
    }
}