<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 28.08.18
 * Time: 14:16
 */

namespace seretos\BehatLoggerExtension\Command;


use seretos\BehatLoggerExtension\Entity\BehatSuite;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateScenarioTitleCommand extends ContainerAwareCommand
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    public function execute (InputInterface $input, OutputInterface $output) {
        @trigger_error("this command is deprecated. please use validate:scenario:id in future!", E_USER_DEPRECATED);
        $output->writeln("this command is deprecated. please use validate:scenario:id in future!");

        $printer = $this->getContainer()->get('json.printer');
        /* @var $suites BehatSuite[]*/
        $suites = $printer->toObjects($input->getArgument('file'));

        $titles = [];
        $error = 0;
        foreach($suites as $suite){
            foreach($suite->getFeatures() as $feature){
                foreach ($feature->getScenarios() as $scenario) {
                    if(trim($scenario->getTitle()) === ''){
                        $output->writeln('a scenario in file '.$feature->getFilename().' has no title!');
                        $error = -1;
                    }else{
                        if(in_array($scenario->getTitle(),$titles)){
                           $output->writeln('the scenario '.$scenario->getTitle().' in file '.$feature->getFilename().' is already defined in another feature file!');
                           $error = -1;
                        }else{
                            $titles[] = $scenario->getTitle();
                        }
                    }
                }
            }
        }

        $output->writeln('done.');

        return $error;
    }

    /**
     * Configure this Command.
     * @return void
     */
    protected function configure () {
        $this->setName('validate:scenario:title')
            ->setDescription('check that all scenarios in the log-file has an unique title')
            ->addArgument('file',
                InputArgument::REQUIRED,
                'the log file')
            ->setHelp(<<<EOT
The <info>%command.name%</info> check that all scenarios have an unique title

Example (<comment>1</comment>): <info>check all scenarios</info>

    $ %command.full_name% default.json
EOT
            );
    }
}