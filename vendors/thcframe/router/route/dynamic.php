<?php

namespace THCFrame\Router\Route;

use THCFrame\Router as Router;

/**
 * Dynamic route class
 */
class Dynamic extends Router\Route
{

    /**
     * Stores any set dynamic elements
     * 
     * @var array
     * @readwrite
     */
    protected $_dynamicElements = array();

    /**
     * Stores any arguments found when mapping
     * 
     * @var array 
     * @readwrite
     */
    protected $_mapArguments = array();

    /**
     * Adds a found argument to the _mapArguments array
     * 
     * @param string $key
     * @param mixed $value
     */
    private function _addMapArguments($key, $value)
    {
        $this->_mapArguments[$key] = $value;
    }

    /**
     * Adds a dynamic element to the Route
     * 
     * @param string $key
     * @param mixed $value
     * @return \THCFrame\Router\Route\Dynamic
     */
    public function addDynamicElement($key, $value)
    {
        $this->_dynamicElements[$key] = $value;

        return $this;
    }

    /**
     * Get the dynamic elements array
     * 
     * @return array
     */
    public function getDynamicElements()
    {
        return $this->_dynamicElements;
    }

    /**
     * Gets the _mapArguments array
     * 
     * @return array
     */
    public function getMapArguments()
    {
        return $this->_mapArguments;
    }

    /**
     * Attempt to match this route to a supplied path
     * 
     * @param string $pathToMatch
     * @return boolean
     */
    public function matchMap($pathToMatch)
    {
        $foundDynamicModule = NULL;
        $foundDynamicClass = NULL;
        $foundDynamicMethod = NULL;
        $foundDynamicArgs = array();

        //Ignore query parameters during matching
        $parsed = parse_url($pathToMatch);
        $pathToMatch = $parsed['path'];

        //The process of matching is easier if there are no preceding slashes
        $tempThisPath = preg_replace('/^\//', '', $this->pattern);
        $tempPathToMatch = preg_replace('/^\//', '', $pathToMatch);

        //Get the path elements used for matching later
        $thisPathElements = explode('/', $tempThisPath);
        $matchPathElements = explode('/', $tempPathToMatch);

        //If the number of elements in each path is not the same, there is no
        // way this could be it.
        if (count($thisPathElements) !== count($matchPathElements)) {
            return false;
        }

        //Construct a path string that will be used for matching
        $possibleMatchString = '';
        foreach ($thisPathElements as $i => $thisPathElement) {
            // ':'s are never allowed at the beginning of the path element
            if (preg_match('/^:/', $matchPathElements[$i])) {
                return false;
            }

            //This element may simply be static, if so the direct comparison
            // will discover it.
            if ($thisPathElement === $matchPathElements[$i]) {
                $possibleMatchString .= "/{$matchPathElements[$i]}";
                continue;
            }

            //Consult the dynamic array for help in matching
            if (isset($this->_dynamicElements[$thisPathElement])) {
                //The dynamic array either contains a key like ':id' or a
                // regular expression. In the case of a key, the key matches
                // anything
                if ($this->_dynamicElements[$thisPathElement] === $thisPathElement) {
                    $possibleMatchString .= "/{$matchPathElements[$i]}";

                    //The class and/or method may be getting set dynamically. If so
                    // extract them and set them
                    if (':module' === $thisPathElement && $this->module === null) {
                        $foundDynamicModule = $matchPathElements[$i];
                    } elseif (':controller' === $thisPathElement && $this->controller === null) {
                        $foundDynamicClass = $matchPathElements[$i];
                    } elseif (':action' === $thisPathElement && $this->action === null) {
                        $foundDynamicMethod = $matchPathElements[$i];
                    } elseif (':module' !== $thisPathElement && ':controller' !== $thisPathElement && ':action' !== $thisPathElement) {
                        $foundDynamicArgs[$thisPathElement] = $matchPathElements[$i];
                    }

                    continue;
                }

                //Attempt a regular expression match
                $regexp = '/' . $this->_dynamicElements[$thisPathElement] . '/';
                if (preg_match($regexp, $matchPathElements[$i]) > 0) {
                    //The class and/or method may be getting set dynamically. If so
                    // extract them and set them
                    if (':module' === $thisPathElement && $this->module === null) {
                        $foundDynamicModule = $matchPathElements[$i];
                    } elseif (':controller' === $thisPathElement && $this->controller === null) {
                        $foundDynamicClass = $matchPathElements[$i];
                    } elseif (':method' === $thisPathElement && $this->action === null) {
                        $foundDynamicMethod = $matchPathElements[$i];
                    } elseif (':module' !== $thisPathElement && ':controller' !== $thisPathElement && ':action' !== $thisPathElement) {
                        $foundDynamicArgs[$thisPathElement] = $matchPathElements[$i];
                    }

                    $possibleMatchString .= "/{$matchPathElements[$i]}";

                    continue;
                }
            }

            // In order for a full match to succeed, all iterations must match.
            // Because we are continuing with the next loop if any conditions
            // above are met, if this point is reached, this route cannot be
            // a match.
            return false;
        }

        //Do the final comparison and return the result
        if ($possibleMatchString === $pathToMatch) {
            if (NULL !== $foundDynamicModule) {
                $this->setModule($foundDynamicModule);
            }

            if (NULL !== $foundDynamicClass) {
                $this->setController($foundDynamicClass);
            }

            if (NULL !== $foundDynamicMethod) {
                $this->setAction($foundDynamicMethod);
            }

            foreach ($foundDynamicArgs as $key => $found_dynamic_arg) {
                $this->_addMapArguments($key, $found_dynamic_arg);
            }
        }

        return ( $possibleMatchString === $pathToMatch );
    }

}
