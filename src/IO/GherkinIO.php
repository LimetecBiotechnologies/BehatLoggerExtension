<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 23:59
 */

namespace seretos\BehatLoggerExtension\IO;


use Behat\Gherkin\Gherkin;
use Behat\Gherkin\Keywords\ArrayKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use ReflectionClass;
use seretos\BehatLoggerExtension\Entity\BehatFeature;
use seretos\BehatLoggerExtension\Entity\BehatScenario;
use seretos\BehatLoggerExtension\Entity\BehatStep;
use seretos\BehatLoggerExtension\Entity\BehatSuite;

class GherkinIO
{
    /**
     * @param string $filename
     * @param BehatSuite $suite
     * @throws \ReflectionException
     */
    public function read(string $filename, BehatSuite $suite){
        $reflection = new ReflectionClass(Gherkin::class);
        $libPath = rtrim(dirname($reflection->getFilename()) . '/../../../', DIRECTORY_SEPARATOR).'/i18n.php';
        $keywords = require_once $libPath;
        $arrayKeywords = new ArrayKeywords($keywords);

        $lexer  = new Lexer($arrayKeywords);
        $parser = new Parser($lexer);

        $feature = $parser->parse(file_get_contents($filename));
        $behatFeature = new BehatFeature($filename,
            $feature->getTitle(),
            $feature->getDescription(),
            $feature->getLanguage());

        foreach($feature->getScenarios() as $scenario){
            $behatScenario = new BehatScenario($scenario->getTitle(),$scenario->getTags());
            foreach($scenario->getSteps() as $step){
                $behatStep = new BehatStep($step->getLine(),$step->getText(),$step->getKeyword(),$step->getArguments());
                $behatScenario->addStep($behatStep);
            }
            $behatFeature->addScenario($behatScenario);
        }
        $suite->addFeature($behatFeature);
    }
}