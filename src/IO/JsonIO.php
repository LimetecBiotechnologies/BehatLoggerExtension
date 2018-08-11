<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 18:32
 */

namespace seretos\BehatLoggerExtension\IO;

use Behat\Testwork\Output\Printer\OutputPrinter;
use seretos\BehatLoggerExtension\Entity\BehatSuite;
use Symfony\Component\Filesystem\Filesystem;

class JsonIO implements OutputPrinter
{
    private $path;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function toJson(array $suites){
        return json_encode(['suites' => $suites]);
    }

    public function toObjects(string $filename){
        $suites = [];
        $content = file_get_contents($filename);
        $jsonContent = json_decode($content,true);
        foreach($jsonContent['suites'] as $jsonSuite){
            $suites[] = BehatSuite::import($jsonSuite);
        }
        return $suites;
    }

    /**
     * Writes newlined message(s) to output stream.
     *
     * @param string|array $messages message or array of messages
     */
    public function writeln($messages = '')
    {

    }

    /**
     * Clear output stream, so on next write formatter will need to init (create) it again.
     */
    public function flush()
    {
        // TODO: Implement flush() method.
    }

    /**
     * Writes message(s) to output stream.
     *
     * @param string|array $messages message or array of messages
     */
    public function write($messages)
    {
        $this->filesystem->mkdir(dirname($this->path));
        $this->filesystem->dumpFile($this->path, $this->toJson($messages));
    }

    /**
     * Sets output path.
     *
     * @param string $path
     */
    public function setOutputPath($path)
    {
        $this->path = $path;
    }

    /**
     * Returns output path.
     *
     * @return null|string
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputPath()
    {
        return $this->path;
    }

    /**
     * Sets output styles.
     *
     * @param array $styles
     */
    public function setOutputStyles(array $styles)
    {
        // TODO: Implement setOutputStyles() method.
    }

    /**
     * Returns output styles.
     *
     * @return array
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputStyles()
    {
        return [];
    }

    /**
     * Forces output to be decorated.
     *
     * @param Boolean $decorated
     */
    public function setOutputDecorated($decorated)
    {
        // TODO: Implement setOutputDecorated() method.
    }

    /**
     * Returns output decoration status.
     *
     * @return null|Boolean
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function isOutputDecorated()
    {
        return true;
    }

    /**
     * Sets output verbosity level.
     *
     * @param integer $level
     */
    public function setOutputVerbosity($level)
    {
        // TODO: Implement setOutputVerbosity() method.
    }

    /**
     * Returns output verbosity level.
     *
     * @return integer
     *
     * @deprecated since 3.1, to be removed in 4.0
     */
    public function getOutputVerbosity()
    {
        return 0;
    }
}