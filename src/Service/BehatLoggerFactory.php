<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 11.08.18
 * Time: 16:34
 */

namespace seretos\BehatLoggerExtension\Service;


use seretos\BehatLoggerExtension\Entity\BehatFeature;
use seretos\BehatLoggerExtension\Entity\BehatResult;
use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatStep;
use seretos\BehatLoggerExtension\Entity\BehatStepResult;
use seretos\BehatLoggerExtension\Entity\BehatSuite;

class BehatLoggerFactory
{
    /**
     * @param string $name
     * @return BehatSuite
     */
    public function createSuite(string $name){
        return new BehatSuite($name);
    }

    /**
     * @param string $filename
     * @param string $title
     * @param string|null $description
     * @param string $language
     * @return BehatFeature
     */
    public function createFeature(string $filename, string $title, string $description = null, string $language = 'en'){
        return new BehatFeature($filename, $title, $description, $language);
    }

    /**
     * @param string $title
     * @param array $tags
     * @return BehatScenario
     */
    public function createScenario(string $title, array $tags = []){
        return new BehatScenario($title, $tags);
    }

    /**
     * @param string $environment
     * @return BehatResult
     */
    public function createResult(string $environment){
        return new BehatResult($environment);
    }

    /**
     * @param int $line
     * @param bool $passed
     * @param string|null $screenshot
     * @return BehatStepResult
     */
    public function createStepResult(int $line, bool $passed, string $screenshot = null){
        return new BehatStepResult($line, $passed, $screenshot);
    }

    /**
     * @param int $line
     * @param string $text
     * @param string $keyword
     * @param array $arguments
     * @return BehatStep
     */
    public function createStep(int $line, string $text, string $keyword, array $arguments = []){
        return new BehatStep($line, $text, $keyword, $arguments);
    }
}