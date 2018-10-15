<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 11.08.18
 * Time: 16:34
 */

namespace seretos\BehatLoggerExtension\Service;


use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use seretos\BehatLoggerExtension\Entity\BehatFeature;
use seretos\BehatLoggerExtension\Entity\BehatResult;
use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatStep;
use seretos\BehatLoggerExtension\Entity\BehatStepResult;
use seretos\BehatLoggerExtension\Entity\BehatSuite;

class BehatLoggerFactory
{
    private $keywords;
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
     * @param null|string $message
     * @return BehatResult
     */
    public function createResult(string $environment, string $message = null){
        return new BehatResult($environment, $message);
    }

    /**
     * @param int $line
     * @param bool $passed
     * @param string|null $screenshot
     * @param string|null $message
     * @return BehatStepResult
     */
    public function createStepResult(int $line, bool $passed, string $screenshot = null, string $message = null){
        return new BehatStepResult($line, $passed, $screenshot,$message);
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

    public function getKeywords(){
        if($this->keywords === null) {
            $this->keywords = [];
            if (file_exists(__DIR__ . '/../../../../behat/gherkin/i18n.php')) {
                $this->keywords = include(__DIR__ . '/../../../../behat/gherkin/i18n.php');
            } else if (file_exists(__DIR__ . '/../../vendor/behat/gherkin/i18n.php')) {
                $this->keywords = include(__DIR__ . '/../../vendor/behat/gherkin/i18n.php');
            }
        }
        return $this->keywords;
    }

    public function createBehatParser(){
        $keywords = new ArrayKeywords($this->getKeywords());
        $lexer  = new Lexer($keywords);
        $parser = new Parser($lexer);
        return $parser;
    }
}