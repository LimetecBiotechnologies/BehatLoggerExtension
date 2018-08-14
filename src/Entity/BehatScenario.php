<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 17:08
 */

namespace seretos\BehatLoggerExtension\Entity;


use JsonSerializable;

class BehatScenario implements JsonSerializable
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var string[]
     */
    private $tags;
    /**
     * @var BehatStep[]
     */
    private $steps;
    /**
     * @var BehatResult[]
     */
    private $results;

    public function __construct(string $title, array $tags)
    {
        $this->title = $title;
        $this->tags = $tags;
        $this->steps = [];
        $this->results = [];
    }

    public static function import(array $values){
        $scenario = new BehatScenario($values['title'],$values['tags']);
        foreach($values['steps'] as $step){
            $scenario->addStep(BehatStep::import($step));
        }
        foreach($values['results'] as $result){
            if(is_array($result['stepResults']) && count($result['stepResults']) > 0) {
                $scenario->addResult(BehatResult::import($result));
            }
        }
        return $scenario;
    }

    public function getTags(){
        return $this->tags;
    }

    public function addStep(BehatStep $step){
        $this->steps[$step->getLine()] = $step;
    }

    public function hasStep(int $line){
        return isset($this->steps[$line]);
    }

    public function getStep(int $line){
        return $this->steps[$line];
    }

    public function getSteps(){
        return $this->steps;
    }

    public function addResult(BehatResult $result){
        $this->results[$result->getEnvironment()] = $result;
    }

    public function hasResult(string $environment){
        return isset($this->results[$environment]);
    }

    public function getResult(string $environment){
        return $this->results[$environment];
    }

    public function getResults(){
        return $this->results;
    }

    public function getTitle(){
        return $this->title;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        ksort($this->results);
        return ['title' => $this->title,'tags' => $this->tags, 'steps' => $this->steps, 'results' => $this->results];
    }
}