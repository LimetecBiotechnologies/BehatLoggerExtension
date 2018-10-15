<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 17:09
 */

namespace seretos\BehatLoggerExtension\Entity;


use JsonSerializable;

class BehatStepResult implements JsonSerializable
{
    private $line;
    private $passed;
    private $screenshot;
    private $message;

    public function __construct(int $line, bool $passed, string $screenshot = null, string $message = null)
    {
        $this->line = $line;
        $this->passed = $passed;
        $this->screenshot = $screenshot;
        $this->message = $message;
    }

    public static function import(array $values){
        $line  =$values['line'];
        $passed = $values['passed'];
        $screenshot = $values['screenshot'];
        $message = null;
        if(isset($values['message'])){
            $message = $values['message'];
        }
        $stepResult = new BehatStepResult($line,$passed,$screenshot,$message);
        return $stepResult;
    }

    public function getLine(){
        return $this->line;
    }

    public function isPassed(){
        return $this->passed;
    }

    public function getScreenshot(){
        return $this->screenshot;
    }

    public function getMessage(){
        return $this->message;
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
        return ['line' => $this->line, 'passed' => $this->passed, 'screenshot' => $this->screenshot,'message' => $this->message];
    }
}