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

    public function __construct(int $line, bool $passed, string $screenshot = null)
    {
        $this->line = $line;
        $this->passed = $passed;
        $this->screenshot = $screenshot;
    }

    public static function import(array $values){
        $stepResult = new BehatStepResult($values['line'],$values['passed'],$values['screenshot']);
        return $stepResult;
    }

    public function getLine(){
        return $this->line;
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
        return ['line' => $this->line, 'passed' => $this->passed, 'screenshot' => $this->screenshot];
    }
}