<?php

namespace THCFrame\Core;

use THCFrame\Core\ArrayMethods;
use THCFrame\Core\StringMethods;

/**
 * The first few methods of our Inspector class use built-in PHP reflection
 * classes to get the string values of Doc Comments,
 * and to get a list of the properties and methods of a class.
 *
 * The public methods of our Inspector class utilize all of our internal methods to return the Doc Comment
 * string values, parse them into associative arrays, and return usable metadata. Since classes cannot change at
 * runtime, all of the public methods cache the results of their first execution within the internal properties.
 *
 * Public methods allow us to list the methods and properties of a class. They also allow us to return the key/value
 * metadata of the class, named methods, and named properties, without the methods or properties needing to be
 * public.
 */
class Inspector
{

    protected $class;
    protected $meta = [
        'class' => [],
        'properties' => [],
        'methods' => []
    ];
    protected $properties = [];
    protected $methods = [];

    /**
     *
     * @param type $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * @return false|string
     * @throws \ReflectionException
     */
    protected function _getClassComment()
    {
        $reflection = new \ReflectionClass($this->class);
        return $reflection->getDocComment();
    }

    /**
     * @return \ReflectionProperty[]
     * @throws \ReflectionException
     */
    protected function _getClassProperties()
    {
        $reflection = new \ReflectionClass($this->class);
        return $reflection->getProperties();
    }

    /**
     * @return \ReflectionMethod[]
     * @throws \ReflectionException
     */
    protected function _getClassMethods()
    {
        $reflection = new \ReflectionClass($this->class);
        return $reflection->getMethods();
    }

    /**
     * @param $property
     * @return false|string
     * @throws \ReflectionException
     */
    protected function _getPropertyComment($property)
    {
        $reflection = new \ReflectionProperty($this->class, $property);
        return $reflection->getDocComment();
    }

    /**
     * @param $method
     * @return false|string
     * @throws \ReflectionException
     */
    protected function _getMethodComment($method)
    {
        $reflection = new \ReflectionMethod($this->class, $method);
        return $reflection->getDocComment();
    }

    /**
     * The internal _parse() method uses a fairly simple regular expression to match key/value pairs
     * within the Doc Comment string returned by any of our _get…Meta() methods
     *
     * @param $comment
     * @return array
     */
    protected function _parse($comment)
    {
        $meta = [];
        $pattern = '(@[a-zá-žA-ZÁ-Ž]+\s*[a-zá-žA-ZÁ-Ž0-9, ()_]*)';
        $matches = StringMethods::match($comment, $pattern);

        if ($matches != null) {
            foreach ($matches as $match) {
                $parts = ArrayMethods::clean(
                                ArrayMethods::trim(
                                        StringMethods::split($match, '[\s]', 2)
                                )
                );

                $meta[$parts[0]] = true;

                if (count($parts) > 1) {
                    $meta[$parts[0]] = ArrayMethods::clean(
                                    ArrayMethods::trim(
                                            StringMethods::split($parts[1], ',')
                                    )
                    );
                }
            }
        }

        return $meta;
    }

    /**
     * @return array|mixed|null
     * @throws \ReflectionException
     */
    public function getClassMeta()
    {
        if (!isset($_meta['class'])) {
            $comment = $this->_getClassComment();

            if (!empty($comment)) {
                $_meta['class'] = $this->_parse($comment);
            } else {
                $_meta['class'] = null;
            }
        }

        return $_meta['class'];
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function getClassProperties()
    {
        if (!isset($_properties)) {
            $properties = $this->_getClassProperties();

            foreach ($properties as $property) {
                $_properties[] = $property->getName();
            }
        }

        return $_properties;
    }

    /**
     * @return array
     * @throws \ReflectionException
     */
    public function getClassMethods()
    {
        if (!isset($_methods)) {
            $methods = $this->_getClassMethods();

            foreach ($methods as $method) {
                $_methods[] = $method->getName();
            }
        }

        return $_methods;
    }

    /**
     * @param $property
     * @return mixed
     * @throws \ReflectionException
     */
    public function getPropertyMeta($property)
    {
        if (!isset($_meta['properties'][$property])) {
            $comment = $this->_getPropertyComment($property);

            if (!empty($comment)) {
                $_meta['properties'][$property] = $this->_parse($comment);
            } else {
                $_meta['properties'][$property] = null;
            }
        }

        return $_meta['properties'][$property];
    }

    /**
     * @param $method
     * @return mixed
     * @throws \ReflectionException
     */
    public function getMethodMeta($method)
    {
        if (!isset($_meta['methods'][$method])) {
            $comment = $this->_getMethodComment($method);

            if (!empty($comment)) {
                $_meta['methods'][$method] = $this->_parse($comment);
            } else {
                $_meta['methods'][$method] = null;
            }
        }

        return $_meta['methods'][$method];
    }

}
