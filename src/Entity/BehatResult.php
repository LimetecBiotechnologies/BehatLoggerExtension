<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 17:09
 */

namespace seretos\BehatLoggerExtension\Entity;


use JsonSerializable;

class BehatResult implements JsonSerializable
{
    private $environment;
    private $stepResults;
    private $duration;

    public function __construct(string $environment)
    {
        $this->environment = $environment;
        $this->duration = 0;
        $this->stepResults = [];
    }

    public static function import(array $values){
        $result = new BehatResult($values['environment']);
        $result->setDuration($values['duration']);
        foreach($values['stepResults'] as $stepResult){
            $result->addStepResult(BehatStepResult::import($stepResult));
        }
        return $result;
    }

    public function getDuration(){
        return $this->duration;
    }

    public function setDuration(float $duration){
        $this->duration = $duration;
    }

    public function getEnvironment(){
        return $this->environment;
    }

    public function addStepResult(BehatStepResult $stepResult){
        $this->stepResults[$stepResult->getLine()] = $stepResult;
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
        return ['environment' => $this->environment,'stepResults' => $this->stepResults, 'duration' => number_format($this->duration,2)];
    }
}