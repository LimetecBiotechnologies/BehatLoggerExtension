<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 08.08.18
 * Time: 17:04
 */

namespace seretos\BehatLoggerExtension\Entity;


use JsonSerializable;

class BehatSuite implements JsonSerializable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var BehatFeature[]
     */
    private $features;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->features = [];
    }

    public static function import(array $values){
        $suite = new BehatSuite($values['name']);
        foreach($values['features'] as $feature){
            $suite->addFeature(BehatFeature::import($feature));
        }
        return $suite;
    }

    public function addFeature(BehatFeature $feature){
        $this->features[$feature->getFilename()] = $feature;
    }

    public function hasFeature(string $filename){
        return isset($this->features[$filename]);
    }

    public function getFeature(string $filename){
        return $this->features[$filename];
    }

    public function getFeatures(){
        return $this->features;
    }

    public function getName(){
        return $this->name;
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
        $result = ['name' => $this->name,'features' => $this->features];
        return $result;
    }
}