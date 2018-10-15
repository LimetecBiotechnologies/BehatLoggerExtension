<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 16:22
 */
namespace seretos\BehatLoggerExtension;
use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use seretos\BehatLoggerExtension\Exception\BehatLoggerException;
use seretos\BehatLoggerExtension\Formatter\BehatLogFormatter;
use seretos\BehatLoggerExtension\Service\ScreenshotPrinter;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class BehatLoggerExtension implements ExtensionInterface
{

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        // TODO: Implement process() method.
    }

    /**
     * Returns the extension config key.
     *
     * @return string
     */
    public function getConfigKey()
    {
        return "logger";
    }

    /**
     * Initializes other extensions.
     *
     * This method is called immediately after all extensions are activated but
     * before any extension `configure()` method is called. This allows extensions
     * to hook into the configuration of other extensions providing such an
     * extension point.
     *
     * @param ExtensionManager $extensionManager
     */
    public function initialize(ExtensionManager $extensionManager)
    {
        // TODO: Implement initialize() method.
    }

    /**
     * Setups configuration for the extension.
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()
            ->scalarNode('output_path')
            ->defaultValue('.');
        $builder->children()
            ->scalarNode('log_format')
            ->defaultValue('json');
        $builder->children()
            ->scalarNode('message')
            ->defaultValue('');
    }

    /**
     * Loads extension services into temporary container.
     *
     * @param ContainerBuilder $container
     * @param array $config
     * @throws \Exception
     */
    public function load(ContainerBuilder $container, array $config)
    {
        if($config['log_format'] !== 'json'){
            throw new BehatLoggerException("ERROR: currently is json the only valid log format!");
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $screenshotPrinterDefinition = new Definition(ScreenshotPrinter::class);
        $container->setDefinition('behat.logger.screenshot',$screenshotPrinterDefinition);

        $definition = new Definition(BehatLogFormatter::class);
        $definition->addArgument(new Reference('behat.logger.factory'));
        $definition->addArgument(new Reference('mink'));
        $definition->addArgument(new Reference('json.printer'));
        $definition->addArgument(new Reference('behat.logger.screenshot'));
        $definition->addArgument($config['output_path']);
        $definition->addArgument($config['message']);
        $definition->addArgument('%mink.parameters%');
        $container->setDefinition("log.formatter", $definition)
            ->addTag("output.formatter");
    }
}