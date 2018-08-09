<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 17:09
 */

namespace seretos\BehatLoggerExtension\Entity;


use JsonSerializable;

class BehatStep implements JsonSerializable
{
    private $line;
    private $text;
    private $keyword;
    private $arguments;

    public function __construct(int $line, string $text, string $keyword, array $arguments = [])
    {
        $this->line = $line;
        $this->text = $text;
        $this->keyword = $keyword;
        $this->arguments = $arguments;
    }

    public static function import(array $values){
        $step = new BehatStep($values['line'],$values['text'],$values['keyword'],$values['arguments']);
        return $step;
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
        return ['line' => $this->line, 'text' => $this->text, 'keyword' => $this->keyword,'arguments' => $this->arguments];
    }
}