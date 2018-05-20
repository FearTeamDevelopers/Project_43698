<?php

namespace THCFrame\Core;

use THCFrame\Core\Inspector;
use THCFrame\Core\StringMethods;
use THCFrame\Core\Exception as Exception;

/**
 * Base class can create getters/setters simply by adding comments around the
 * protected properties.
 *
 * In order for us to achieve this sort of thing, we would need to determine the name of the property that must
 * be read/modified, and also determine whether we are allowed to read/modify it,
 * based on the @read/@write/@readwrite flags in the comments.
 */
class Base
{

    /**
     * Inspector instance
     *
     * @var THCFrame\Core\Inspector
     */
    private $inspector;

    /**
     * Storage for dynamicly created variables mainly from database joins
     *
     * @var array
     */
    protected $dataStore = [];

    /**
     *
     * @param string $property
     * @return \THCFrame\Core\Exception\ReadOnly
     */
    protected function _getReadonlyException($property)
    {
        return new Exception\ReadOnly(sprintf('%s is read-only', $property));
    }

    /**
     *
     * @param string $property
     * @return \THCFrame\Core\Exception\WriteOnly
     */
    protected function _getWriteonlyException($property)
    {
        return new Exception\WriteOnly(sprintf('%s is write-only', $property));
    }

    /**
     *
     * @return \THCFrame\Core\Exception\Property
     */
    protected function _getPropertyException()
    {
        return new Exception\Property('Invalid property');
    }

    /**
     *
     * @param string $method
     * @return \THCFrame\Core\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Object constructor
     *
     * @param $options $options
     */
    public function __construct($options = [])
    {
        $this->inspector = new Inspector($this);

        if (is_array($options) || is_object($options)) {
            foreach ($options as $key => $value) {
                $key = ucfirst($key);
                $method = "set{$key}";
                $this->$method($value);
            }
        }
    }

    /**
     * There are four basic parts to our __call() method:
     * checking to see that the inspector is set,
     * handling the getProperty() methods, handling the setProperty() methods and
     * handling the unsProperty() methods
     *
     * @param string $name
     * @param string $arguments
     * @return null|\THCFrame\Core\Base
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (empty($this->inspector)) {
            throw new Exception('Call parent::__construct!');
        }

        $getMatches = StringMethods::match($name, '#^get([a-zA-Z0-9_]+)$#');
        if (count($getMatches) > 0) {
            $normalized = lcfirst($getMatches[0]);
            $property = "_{$normalized}";
            $property2 = "{$normalized}";

            if (property_exists($this, $property)) {
                $meta = $this->inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@read'])) {
                    throw $this->_getWriteonlyException($normalized);
                }

                unset($meta);

                if (isset($this->$property)) {
                    return $this->$property;
                } else {
                    return null;
                }
            } elseif (property_exists($this, $property2)) {
                $meta = $this->inspector->getPropertyMeta($property2);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) {
                    throw $this->_getReadonlyException($normalized);
                }

                unset($meta);

                if (isset($this->$property2)) {
                    return $this->$property2;
                } else {
                    return null;
                }
                return $this;
            } elseif (array_key_exists($normalized, $this->dataStore)) {
                return $this->dataStore[$normalized];
            } else {
                return null;
            }
        }

        unset($getMatches);

        $setMatches = StringMethods::match($name, '#^set([a-zA-Z0-9_]+)$#');
        if (count($setMatches) > 0) {
            $normalized = lcfirst($setMatches[0]);
            $property = "_{$normalized}";
            $property2 = "{$normalized}";

            if (property_exists($this, $property)) {
                $meta = $this->inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) {
                    throw $this->_getReadonlyException($normalized);
                }

                unset($meta);

                $this->$property = $arguments[0];
                return $this;
            } elseif (property_exists($this, $property2)) {
                $meta = $this->inspector->getPropertyMeta($property2);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) {
                    throw $this->_getReadonlyException($normalized);
                }

                unset($meta);

                $this->$property2 = $arguments[0];
                return $this;
            } else {
                //if variable is not class property its stored into _dataStore array
                $this->dataStore[$normalized] = $arguments[0];
                return $this;
            }
        }

        unset($setMatches);

        $unsetMatches = StringMethods::match($name, '#^uns([a-zA-Z0-9_]+)$#');
        if (count($unsetMatches) > 0) {
            $normalized = lcfirst($setMatches[0]);
            $property = "_{$normalized}";
            $property2 = "{$normalized}";

            if (property_exists($this, $property)) {
                $meta = $this->inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) {
                    throw $this->_getReadonlyException($normalized);
                }

                unset($meta);

                unset($this->$property);
                return $this;
            } elseif (property_exists($this, $property2)) {
                $meta = $this->inspector->getPropertyMeta($property2);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) {
                    throw $this->_getReadonlyException($normalized);
                }

                unset($meta);

                unset($this->$property2);
                return $this;
            } else {
                unset($this->dataStore[$normalized]);
                return $this;
            }
        }

        unset($unsetMatches);

        throw $this->_getImplementationException($name);
    }

    /**
     * The __get() method accepts an argument that
     * represents the name of the property being set.
     * __get() method then converts this to getProperty,
     * which matches the pattern we defined in the __call() method
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $function = 'get' . ucfirst($name);
        return $this->$function();
    }

    /**
     * The __set() method accepts a second argument,
     * which defines the value to be set.
     * __set() method then converts this to setProperty($value),
     * which matches the pattern we defined in the __call() method
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function __set($name, $value)
    {
        $function = 'set' . ucfirst($name);
        return $this->$function($value);
    }

    /**
     * The __unset() method accepts an argument that
     * represents the name of the property being set.
     * __unset() method then converts this to unsProperty,
     * which matches the pattern we defined in the __call() method
     *
     * @param string $name
     * @return mixed
     */
    public function __unset($name)
    {
        $function = 'uns' . ucfirst($name);
        return $this->$function();
    }

    public function getInspector()
    {
        return $this->inspector;
    }

    public function getDataStore()
    {
        return $this->dataStore;
    }

    public function setInspector(THCFrame\Core\Inspector $inspector)
    {
        $this->inspector = $inspector;
        return $this;
    }

    public function setDataStore($dataStore)
    {
        $this->dataStore = $dataStore;
        return $this;
    }

}
