<?php
/**
 * Created by PhpStorm.
 * User: seredos
 * Date: 13.01.19
 * Time: 06:11
 */

namespace seretos\BehatLoggerExtension\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractTestRailCommand extends ContainerAwareCommand
{
    private $config;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);

        /* @var $fs Filesystem */
        $fs = $this->getContainer()->get('filesystem');
        $this->config = null;
        if ($fs->exists('.testrail.yml')) {
            $this->config = Yaml::parseFile('.testrail.yml');
        }

        if (!is_array($this->config)) {
            $this->config = [];
        }
        if (!isset($this->config['api'])) {
            $this->config['api'] = [];
        }

        if (!isset($this->config['priorities'])) {
            $this->config['priorities'] = [];
        }

        if (!isset($this->config['fields'])) {
            $this->config['fields'] = [];
        }

        $this->config['api']['identifier_regex'] = (isset($this->config['api']['identifier_regex'])) ? $this->config['api']['identifier_regex'] : '/^testrail-case-([0-9]*)$/';
        $this->config['api']['template'] = (isset($this->config['api']['template'])) ? $this->config['api']['template'] : 'Test Case (Steps)';
        $this->config['api']['type'] = (isset($this->config['api']['type'])) ? $this->config['api']['type'] : 'Automated';
        $this->config['api']['title_field'] = (isset($this->config['api']['title_field'])) ? $this->config['api']['title_field'] : null;
        $this->config['api']['default_section'] = (isset($this->config['api']['default_section'])) ? $this->config['api']['default_section'] : null;
        $this->config['api']['identifier_field'] = (isset($this->config['api']['identifier_field'])) ? $this->config['api']['identifier_field'] : null;
    }

    protected function getFieldOptions()
    {
        return $this->config['fields'];
    }

    protected function getPriorityOptions()
    {
        return $this->config['priorities'];
    }

    protected function getTestRailServerOptions(InputInterface $input)
    {
        $result = [];

        $result['server'] = ($input->getOption('server')) ? $input->getOption('server') : $this->config['api']['server'];
        $result['user'] = ($input->getOption('user')) ? $input->getOption('user') : $this->config['api']['user'];
        $result['password'] = ($input->getOption('password')) ? $input->getOption('password') : $this->config['api']['password'];
        $result['project'] = ($input->getOption('project')) ? $input->getOption('project') : $this->config['api']['project'];

        return $result;
    }

    protected function getTestRailIdentifierOptions(InputInterface $input)
    {
        $result = [];

        $result['identifier-regex'] = ($input->getOption('identifier-regex')) ? $input->getOption('identifier-regex') : $this->config['api']['identifier_regex'];
        $result['identifier-field'] = ($input->getOption('identifier-field')) ? $input->getOption('identifier-field') : $this->config['api']['identifier_field'];

        return $result;
    }

    protected function getTestRailSynchronizationOptions(InputInterface $input)
    {
        $result = [];

        $result['title-field'] = ($input->getOption('title-field')) ? $input->getOption('title-field') : $this->config['api']['title_field'];
        $result['template'] = ($input->getOption('template')) ? $input->getOption('template') : $this->config['api']['template'];
        $result['type'] = ($input->getOption('type')) ? $input->getOption('type') : $this->config['api']['type'];
        $result['default-section'] = ($input->getOption('default-section')) ? $input->getOption('default-section') : $this->config['api']['default_section'];

        return $result;
    }

    protected function getGroupFieldOption(InputInterface $input)
    {
        return ($input->getOption('group-field')) ? $input->getOption('group-field') : $this->config['api']['group_field'];
    }

    protected function setGroupFieldOption()
    {
        $this->addOption('group-field', 'g', InputOption::VALUE_REQUIRED, 'the environment group field (eg. firefox, ie e.t.c.)');
    }

    protected function setTestRailServerOptions()
    {
        $this->addOption('server', 's', InputOption::VALUE_REQUIRED, 'the testrail server')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'the testrail user')
            ->addOption('password', 'w', InputOption::VALUE_REQUIRED, 'the testrail password')
            ->addOption('project', 'p', InputOption::VALUE_REQUIRED, 'the testrail project');
    }

    protected function setIdentifierOptions()
    {
        $this->addOption('identifier-regex', 'r', InputOption::VALUE_REQUIRED, 'the identifier regex default: /^testrail-case-([0-9]*)$/')
            ->addOption('identifier-field', 'f', InputOption::VALUE_REQUIRED, 'the identifier field on testrail');
    }

    protected function setSynchronizationOptions()
    {
        $this->addOption('title-field', 'l', InputOption::VALUE_REQUIRED, 'the title-field on testrail')
            ->addOption('template', 't', InputOption::VALUE_REQUIRED, 'the template on testrail default: Test Case(Steps)')
            ->addOption('type', 'y', InputOption::VALUE_REQUIRED, 'the case type default: Automated')
            ->addOption('default-section', 'd', InputOption::VALUE_REQUIRED, 'the case default section');
    }
}