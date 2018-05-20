<?php

namespace THCFrame\Router\Route;

use THCFrame\Router as Router;
use THCFrame\Router\Exception;
use THCFrame\Request\RequestMethods;

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
    protected $dynamicElements = [];

    /**
     * Stores any arguments found when mapping
     *
     * @var array
     * @readwrite
     */
    protected $mapArguments = [];

    /**
     * Adds a found argument to the _mapArguments array
     *
     * @param string $key
     * @param mixed $value
     */
    private function addMapArguments($key, $value)
    {
        $this->mapArguments[$key] = $value;
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
        $key = str_replace('?', '', $key);
        $this->dynamicElements[$key] = $value;

        return $this;
    }

    /**
     * Get the dynamic elements array
     *
     * @return array
     */
    public function getDynamicElements()
    {
        return $this->dynamicElements;
    }

    /**
     * Gets the _mapArguments array
     *
     * @return array
     */
    public function getMapArguments()
    {
        return $this->mapArguments;
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
        $foundDynamicArgs = [];

        $httpMethod = RequestMethods::server('REQUEST_METHOD');
        if ($httpMethod == 'POST' && RequestMethods::issetserver('HTTP_X_HTTP_METHOD')) {
            if (RequestMethods::server('HTTP_X_HTTP_METHOD') == 'DELETE') {
                $httpMethod = 'DELETE';
            } else if (RequestMethods::server('HTTP_X_HTTP_METHOD') == 'PUT') {
                $httpMethod = 'PUT';
            } else {
                throw new Exception('Unexpected Header');
            }
        }

        //Ignore query parameters during matching
        $parsed = parse_url($pathToMatch);
        $pathToMatch = $parsed['path'];

        //The process of matching is easier if there are no preceding slashes
        $tempThisPath = preg_replace('/^\//', '', $this->getPattern());
        $tempPathToMatch = preg_replace('/^\//', '', $pathToMatch);

        //Get the path elements used for matching later
        $thisPathElements = explode('/', $tempThisPath);
        $matchPathElements = explode('/', $tempPathToMatch);

        //Check if request method and route method match
        if ($this->getMethod() !== null && $this->getMethod() !== $httpMethod) {
            return false;
        }

        if (count($thisPathElements) < count($matchPathElements)) {
            return false;
        }

        //Construct a path string that will be used for matching
        $possibleMatchString = '';
        foreach ($thisPathElements as $i => $thisPathElement) {
            $isOptional = stripos($thisPathElement, '?') !== false;

            if ($isOptional) {
                $thisPathElement = str_replace('?', '', $thisPathElement);
            }

            // ':'s are never allowed at the beginning of the path element
            if (isset($matchPathElements[$i]) && preg_match('/^:/', $matchPathElements[$i])) {
                return false;
            }

            //This element may simply be static, if so the direct comparison
            // will discover it.
            if (isset($matchPathElements[$i]) && $thisPathElement === $matchPathElements[$i]) {
                $possibleMatchString .= "/{$matchPathElements[$i]}";
                continue;
            }

            //Consult the dynamic array for help in matching
            if (isset($this->dynamicElements[$thisPathElement])) {
                if($isOptional && !isset($matchPathElements[$i])){
                    continue;
                }

                //The dynamic array either contains a key like ':id' or a
                // regular expression. In the case of a key, the key matches
                // anything
                if (str_replace('?', '', $this->dynamicElements[$thisPathElement]) === $thisPathElement) {
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
                $regexp = '/' . $this->dynamicElements[$thisPathElement] . '/';
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

            foreach ($foundDynamicArgs as $key => $foundDynamicArg) {
                $this->addMapArguments($key, $foundDynamicArg);
            }
        }

        return ( $possibleMatchString === $pathToMatch );
    }

}
