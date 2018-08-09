<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 17:08
 */

namespace seretos\BehatLoggerExtension\Entity;


use JsonSerializable;

class BehatFeature implements JsonSerializable
{
    /**
     * @var string
     */
    private $filename;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $description;
    /**
     * @var string
     */
    private $language;
    /**
     * @var BehatScenario[]
     */
    private $scenarios;

    public function __construct(string $filename, string $title, string $description = "", string $language = "en")
    {
        $this->filename = $filename;
        $this->title = $title;
        $this->description = $description;
        $this->language = $language;
        $this->scenarios = [];
    }

    public static function import(array $values){
        $feature = new BehatFeature($values['filename'],$values['title'],$values['description'],$values['language']);
        foreach($values['scenarios'] as $scenario){
            $feature->addScenario(BehatScenario::import($scenario));
        }
        return $feature;
    }

    public function getFilename(){
        return $this->filename;
    }

    public function addScenario(BehatScenario $scenario){
        $this->scenarios[$scenario->getTitle()] = $scenario;
    }

    public function getScenario(string $title){
        return $this->scenarios[$title];
    }

    public function getScenarios(){
        return $this->scenarios;
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
        $result = ['title' => $this->title,
            'filename' => $this->filename,
            'description' => $this->description,
            'language' => $this->language,
            'scenarios' => $this->scenarios];
        return $result;
    }
}