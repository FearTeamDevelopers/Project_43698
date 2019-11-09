<?php

namespace THCFrame\Model;

use THCFrame\Core\Base;

/**
 * Writer class definition text to the file
 */
class Modelwriter extends Base
{

    private $use = [];
    private $property = [];

    /**
     * @readwrite
     * @var string
     */
    protected $namespace;

    /**
     * @readwrite
     * @var string
     */
    protected $extends;

    /**
     * @readwrite
     * @var array
     */
    protected $implements = [];

    /**
     * @readwrite
     * @var string
     */
    protected $filename;

    /**
     * @readwrite
     * @var string
     */
    protected $classname;

    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    /**
     * Add class property
     *
     * @param string $propertyName
     * @param string|array $propertyAnnotations
     * @return Modelwriter
     */
    public function addProperty($propertyName, $propertyAnnotations)
    {
        $this->property[$propertyName] = $propertyAnnotations;

        return $this;
    }

    /**
     * Add implements to the class header
     *
     * @param array $implements
     * @return Modelwriter
     */
    public function addImplements($implements)
    {
        $this->implements[] = $implements;
        return $this;
    }

    /**
     * Add use to the class header
     *
     * @param string $use
     * @param string $useAlias
     * @return Modelwriter
     */
    public function addUse($use, $useAlias = null)
    {
        if ($useAlias !== null) {
            $this->use[$useAlias] = $use;
        } else {
            $this->use[] = $use;
        }

        return $this;
    }

    /**
     * Write class header to the file
     */
    private function _writeHeader()
    {
        $extends = !empty($this->extends) ? 'extends ' . $this->extends : '';
        $implements = !empty($this->implements) ? 'implements ' . implode(',', $this->implements) : '';
        $useStr = '';

        foreach ($this->use as $key => $value) {
            if (strlen($key) > 3) {
                $useStr .= 'use ' . $value . ' as ' . $key . ';' . PHP_EOL;
            } else {
                $useStr .= 'use ' . $value . ';' . PHP_EOL;
            }
        }

        $contentModel = <<<MODEL
<?php

namespace {$this->getNamespace()};

{$useStr}
class {$this->getClassname()} {$extends} {$implements}
{

MODEL;

        file_put_contents($this->filename, $contentModel);
    }

    /**
     * Write class properties to the file
     */
    private function _writeProperties()
    {
        if (!empty($this->property)) {
            foreach ($this->property as $name => $annotation) {
                $property = <<<PROPERTY

{$annotation}
    protected \$_{$name};

PROPERTY;
                file_put_contents($this->filename, $property, FILE_APPEND);
            }
        }
    }

    /**
     * Write property getter and setter method
     */
    private function _writeGettersSetters()
    {
        if (!empty($this->property)) {
            foreach ($this->property as $name => $annotation) {
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
                file_put_contents($this->filename, $getterSetter, FILE_APPEND);
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
        file_put_contents($this->filename, $classEnd, FILE_APPEND);
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

    public function getUse()
    {
        return $this->use;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function getExtends()
    {
        return $this->extends;
    }

    public function getImplements()
    {
        return $this->implements;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getClassname()
    {
        return $this->classname;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function setExtends(string $extends)
    {
        $this->extends = $extends;
        return $this;
    }

    public function setImplements(array $implements)
    {
        $this->implements = $implements;
        return $this;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
        return $this;
    }

    public function setClassname(string $classname)
    {
        $this->classname = $classname;
        return $this;
    }

}
