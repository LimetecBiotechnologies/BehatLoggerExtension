<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 10.08.18
 * Time: 03:04
 */

namespace seretos\BehatLoggerExtension;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Application extends BaseApplication implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Throwable
     */
    public function doRun (InputInterface $input, OutputInterface $output) {
        $this->registerCommands();
        return parent::doRun($input, $output);
    }

    /**
     * Sets the container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer (ContainerInterface $container = null) {
        $this->container = $container;
    }

    public function getContainer(){
        return $this->container;
    }

    protected function registerCommands () {
//        $this->addCommands([new DoubleResultCheckCommand(),
//            new MergeResultCommand(),
//            new ValidateResultCommand(),
//            new HtmlResultCommand()]);
    }
}