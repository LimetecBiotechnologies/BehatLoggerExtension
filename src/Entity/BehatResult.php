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
    private $message;

    public function __construct(string $environment, string $message = null)
    {
        $this->environment = $environment;
        $this->duration = 0;
        $this->stepResults = [];
        $this->message = $message;
    }

    public static function import(array $values){
        $message = null;
        if(isset($values['message'])){
            $message = $values['message'];
        }
        $result = new BehatResult($values['environment'],$message);
        if(isset($values['duration'])) {
            $result->setDuration($values['duration']);
        }
        foreach($values['stepResults'] as $stepResult){
            $result->addStepResult(BehatStepResult::import($stepResult));
        }
        return $result;
    }

    public function getDuration(){
        return $this->duration;
    }

    public function setDuration(float $duration = null){
        $this->duration = $duration;
    }

    public function getEnvironment(){
        return $this->environment;
    }

    public function getMessage(){
        return $this->message;
    }

    /**
     * @return BehatStepResult[]
     */
    public function getStepResults(){
        return $this->stepResults;
    }

    public function hasStepResult($line){
        return isset($this->stepResults[$line]);
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
        return ['environment' => $this->environment,'stepResults' => $this->stepResults, 'duration' => number_format($this->duration,2),'message' => $this->message];
    }
}