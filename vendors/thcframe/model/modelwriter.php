<?php

namespace THCFrame\Model;

use THCFrame\Core\Base;

/**
 * Writer class definition text to the file
 *
 * @author Tomy
 */
class Modelwriter extends Base
{
    private $_use = array();
    private $_property = array();

    /**
     * @readwrite
     * @var type
     */
    protected $_namespace;

    /**
     * @readwrite
     * @var type
     */
    protected $_extends;

    /**
     * @readwrite
     * @var type
     */
    protected $_implements = array();

    /**
     * @readwrite
     * @var string
     */
    protected $_filename;

    /**
     * @readwrite
     * @var string
     */
    protected $_classname;


    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    /**
     * Add class property
     *
     * @param type $propertyName
     * @param type $propertyAnnotations
     * @return \THCFrame\Model\Modelwriter
     */
    public function addProperty($propertyName, $propertyAnnotations)
    {
        $this->_property[$propertyName] = $propertyAnnotations;

        return $this;
    }

    /**
     * Add implements to the class header
     *
     * @param type $implements
     * @return \THCFrame\Model\Modelwriter
     */
    public function addImplements($implements)
    {
        $this->_implements[] = $implements;
        return $this;
    }

    /**
     * Add use to the class header
     *
     * @param type $use
     * @param type $useAlias
     * @return \THCFrame\Model\Modelwriter
     */
    public function addUse($use, $useAlias = null)
    {
        if($useAlias !== null){
            $this->_use[$useAlias] = $use;
        }else{
            $this->_use[] = $use;
        }

        return $this;
    }

    /**
     * Write class header to the file
     */
    private function _writeHeader()
    {
        $extends = !empty($this->_extends) ? 'extends '.$this->_extends: '';
        $implements = !empty($this->_implements)? implode(',', $this->_implements) : '';
        $useStr = '';

        foreach ($this->_use as $key => $value){
            if(strlen($key) > 3){
                $useStr .= 'use '.$value.' as '.$key.';'.PHP_EOL;
            }else{
                $useStr .= 'use '.$value.';'.PHP_EOL;
            }

        }

        $contentModel = <<<MODEL
<?php

namespace {$this->getNamespace()};

{$useStr}
class {$this->getClassname()} {$extends} {$implements}
{

MODEL;

        file_put_contents($this->_filename, $contentModel);
    }

    /**
     * Write class properties to the file
     */
    private function _writeProperties()
    {
        if(!empty($this->_property)){
            foreach($this->_property as $name => $annotation){
                $property = <<<PROPERTY

{$annotation}
    protected \$_{$name};

PROPERTY;
                file_put_contents($this->_filename, $property, FILE_APPEND);
            }
        }
    }

    /**
     * Write property getter and setter method
     */
    private function _writeGettersSetters()
    {
        if(!empty($this->_property)){
            foreach($this->_property as $name => $annotation){
                $normalizedName = ucfirst($name);

                $getterSetter = <<<BASICMETHODS

    public function get{$normalizedName}()
    {
        return \$this->_{$name};
    }

    public function set{$normalizedName}(\$value)
    {
        \$this->_{$name} = \$value;
        return \$this;
    }

BASICMETHODS;
                file_put_contents($this->_filename, $getterSetter, FILE_APPEND);
            }
        }
    }

    /**
     * Add footer to the file
     */
    private function _writeFooter()
    {
        $classEnd = <<<END

}
END;
        file_put_contents($this->_filename, $classEnd, FILE_APPEND);
    }

    /**
     * Public wrapper for write methods
     */
    public function writeModel()
    {
        $this->_writeHeader();
        $this->_writeProperties();
        $this->_writeGettersSetters();
        $this->_writeFooter();
    }
}
