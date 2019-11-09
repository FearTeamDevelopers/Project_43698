<?php

namespace THCFrame\Core;

use THCFrame\Core\Exception;
use THCFrame\Registry\Registry;

/**
 * StringMethods class
 */
class StringMethods
{

    private static $delimiter = '#';
    private static $singular = [
        '(matr)ices$' => '\\1ix',
        '(vert|ind)ices$' => '\\1ex',
        '^(ox)en' => '\\1',
        '(alias)es$' => '\\1',
        '([octop|vir])i$' => '\\1us',
        '(cris|ax|test)es$' => '\\1is',
        '(shoe)s$' => '\\1',
        '(o)es$' => '\\1',
        '(bus|campus)es$' => '\\1',
        '([m|l])ice$' => '\\1ouse',
        '(x|ch|ss|sh)es$' => '\\1',
        '(m)ovies$' => '\\1\\2ovie',
        '(s)eries$' => '\\1\\2eries',
        '([^aeiouy]|qu)ies$' => '\\1y',
        '([lr])ves$' => '\\1f',
        '(tive)s$' => '\\1',
        '(hive)s$' => '\\1',
        '([^f])ves$' => '\\1fe',
        '(^analy)ses$' => '\\1sis',
        '((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$' => '\\1\\2sis',
        '([ti])a$' => '\\1um',
        '(p)eople$' => '\\1\\2erson',
        '(m)en$' => '\\1an',
        '(s)tatuses$' => '\\1\\2tatus',
        '(c)hildren$' => '\\1\\2hild',
        '(n)ews$' => '\\1\\2ews',
        '([^u])s$' => '\\1'
    ];
    private static $plural = [
        '^(ox)$' => '\\1\\2en',
        '([m|l])ouse$' => '\\1ice',
        '(matr|vert|ind)ix|ex$' => '\\1ices',
        '(x|ch|ss|sh)$' => '\\1es',
        '([^aeiouy]|qu)y$' => '\\1ies',
        '(hive)$' => '\\1s',
        '(?:([^f])fe|([lr])f)$' => '\\1\\2ves',
        'sis$' => 'ses',
        '([ti])um$' => '\\1a',
        '(p)erson$' => '\\1eople',
        '(m)an$' => '\\1en',
        '(c)hild$' => '\\1hildren',
        '(buffal|tomat)o$' => '\\1\\2oes',
        '(bu|campu)s$' => '\\1\\2ses',
        '(alias|status|virus)' => '\\1es',
        '(octop)us$' => '\\1i',
        '(ax|cris|test)is$' => '\\1es',
        's$' => 's',
        '$' => 's'
    ];
    private static $diacriticalConversionTable = [
        'ä' => 'a',
        'Ä' => 'A',
        'á' => 'a',
        'Á' => 'A',
        'à' => 'a',
        'À' => 'A',
        'ã' => 'a',
        'Ã' => 'A',
        'â' => 'a',
        'Â' => 'A',
        'č' => 'c',
        'Č' => 'C',
        'ć' => 'c',
        'Ć' => 'C',
        'ď' => 'd',
        'Ď' => 'D',
        'ě' => 'e',
        'Ě' => 'E',
        'é' => 'e',
        'É' => 'E',
        'ë' => 'e',
        'Ë' => 'E',
        'è' => 'e',
        'È' => 'E',
        'ê' => 'e',
        'Ê' => 'E',
        'í' => 'i',
        'Í' => 'I',
        'ï' => 'i',
        'Ï' => 'I',
        'ì' => 'i',
        'Ì' => 'I',
        'î' => 'i',
        'Î' => 'I',
        'ľ' => 'l',
        'Ľ' => 'L',
        'ĺ' => 'l',
        'Ĺ' => 'L',
        'ń' => 'n',
        'Ń' => 'N',
        'ň' => 'n',
        'Ň' => 'N',
        'ñ' => 'n',
        'Ñ' => 'N',
        'ó' => 'o',
        'Ó' => 'O',
        'ö' => 'o',
        'Ö' => 'O',
        'ô' => 'o',
        'Ô' => 'O',
        'ò' => 'o',
        'Ò' => 'O',
        'õ' => 'o',
        'Õ' => 'O',
        'ő' => 'o',
        'Ő' => 'O',
        'ř' => 'r',
        'Ř' => 'R',
        'ŕ' => 'r',
        'Ŕ' => 'R',
        'š' => 's',
        'Š' => 'S',
        'ś' => 's',
        'Ś' => 'S',
        'ť' => 't',
        'Ť' => 'T',
        'ú' => 'u',
        'Ú' => 'U',
        'ů' => 'u',
        'Ů' => 'U',
        'ü' => 'u',
        'Ü' => 'U',
        'ù' => 'u',
        'Ù' => 'U',
        'ũ' => 'u',
        'Ũ' => 'U',
        'û' => 'u',
        'Û' => 'U',
        'ý' => 'y',
        'Ý' => 'Y',
        'ž' => 'z',
        'Ž' => 'Z',
        'ź' => 'z',
        'Ź' => 'Z'
    ];

    /**
     * @var array
     */
    private static $stopwordsEn = ['a', 'able', 'about', 'across', 'after', 'all', 'almost', 'also', 'am', 'among', 'an', 'and', 'any', 'are', 'as', 'at',
        'be', 'because', 'been', 'but', 'by', 'can', 'cannot', 'could', 'dear', 'did', 'do', 'does', 'either', 'else', 'ever', 'every', 'for', 'from', 'get', 'got',
        'had', 'has', 'have', 'he', 'her', 'hers', 'him', 'his', 'how', 'however', 'i', 'if', 'in', 'into', 'is', 'it', 'its', 'just', 'least', 'let', 'like', 'likely',
        'may', 'me', 'might', 'most', 'must', 'my', 'neither', 'no', 'nor', 'not', 'of', 'off', 'often', 'on', 'only', 'or', 'other', 'our', 'own', 'rather',
        'said', 'say', 'says', 'she', 'should', 'since', 'so', 'some', 'than', 'that', 'the', 'their', 'them', 'then', 'there', 'these', 'they', 'this', 'tis', 'to', 'too', 'twas',
        'us', 'wants', 'was', 'we', 'were', 'what', 'when', 'where', 'which', 'while', 'who', 'whom', 'why', 'will', 'with', 'would', 'yet', 'you', 'your',
        "ain't", "aren't", "can't", "could've", "couldn't", "didn't", "doesn't", "don't", "hasn't", "he'd", "he'll", "he's", "how'd", "how'll", "how's",
        "i'd", "i'll", "i'm", "i've", "isn't", "it's", "might've", "mightn't", "must've", "mustn't", "shan't", "she'd", "she'll", "she's", "should've",
        "shouldn't", "that'll", "that's", "there's", "they'd", "they'll", "they're", "they've", "wasn't", "we'd", "we'll", "we're", "weren't", "what'd",
        "what's", "when'd", "when'll", "when's", "where'd", "where'll", "where's", "who'd", "who'll", "who's", "why'd", "why'll", "why's", "won't", "would've",
        "wouldn't", "you'd", "you'll", "you're", "you've",];

    /**
     * @var array
     */
    private static $stopwordsCs = [
        'com', 'net', 'org', 'div', 'nbsp', 'http', 'jeden', 'jedna', 'dva', 'tri', 'ctyri', 'pet', 'sest', 'sedm', 'osm',
        'devet', 'deset', 'dny', 'den', 'dne', 'dni', 'dnes', 'timto', 'budes', 'budem', 'byli', 'jses', 'muj', 'svym',
        'tomto', 'tam', 'tohle', 'tuto', 'tyto', 'jej', 'zda', 'proc', 'mate', 'tato', 'kam', 'tohoto', 'kdo', 'kteri',
        'nam', 'tom', 'tomuto', 'mit', 'nic', 'proto', 'kterou', 'byla', 'toho', 'protoze', 'asi', 'nasi', 'napiste',
        'coz', 'tim', 'takze', 'svych', 'jeji', 'svymi', 'jste', 'tedy', 'teto', 'bylo', 'kde', 'prave', 'nad', 'nejsou',
        'pod', 'tema', 'mezi', 'pres', 'pak', 'vam', 'ani', 'kdyz', 'vsak', 'jsem', 'tento', 'clanku', 'clanky', 'aby',
        'jsme', 'pred', 'pta', 'jejich', 'byl', 'jeste', 'bez', 'take', 'pouze', 'prvni', 'vase', 'ktera', 'nas', 'novy',
        'tipy', 'pokud', 'muze', 'design', 'strana', 'jeho', 'sve', 'jine', 'zpravy', 'nove', 'neni', 'vas', 'jen', 'podle',
        'zde', 'clanek', 'email', 'byt', 'vice', 'bude', 'jiz', 'nez', 'ktery', 'ktere', 'nebo', 'ten', 'tak', 'pri', 'jsou',
        'jak', 'dalsi', 'ale', 'jako', 'zpet', 'pro', 'www', 'atd', 'cca', 'cili', 'dal', 'der', 'des', 'det', 'druh', 'faq',
        'hot', 'for', 'info', 'ing',
    ];

    /**
     *
     */
    private function __construct()
    {

    }

    /**
     *
     */
    private function __clone()
    {

    }

    /**
     * Normalization of regular expression strings, so that the remaining
     * methods can operate on them without first having to check or normalize them
     *
     * @param string $pattern
     * @return string
     */
    private static function _normalize($pattern)
    {
        return self::$delimiter . trim($pattern, self::$delimiter) . self::$delimiter;
    }

    /**
     * Return delimiter
     *
     * @return string
     */
    public static function getDelimiter()
    {
        return self::$delimiter;
    }

    /**
     * Set delimiter
     *
     * @param string $delimiter
     */
    public static function setDelimiter($delimiter)
    {
        self::$delimiter = $delimiter;
    }

    /**
     * Methods perform similarly to the preg_match_all() function, but require less
     * formal structure to the regular expressions, and return a more predictable set of results.
     * The match() method will return the first captured substring,
     * the entire substring match, or null.
     *
     * @param string $string
     * @param string $pattern
     * @return mixed
     */
    public static function match($string, $pattern)
    {
        preg_match_all(self::_normalize($pattern), $string, $matches, PREG_PATTERN_ORDER);

        if (!empty($matches[1])) {
            return $matches[1];
        }

        if (!empty($matches[0])) {
            return $matches[0];
        }

        return [];
    }

    /**
     * Return hash of given string or object
     *
     * @param string|object $string
     * @param string        $algo
     * @return string
     */
    public static function getHash($string, $algo = null)
    {
        if ($algo === null) {
            $algo = Registry::get('configuration')->security->encoder;
        }

        return hash_hmac($algo, $string, '');
    }

    /**
     * Methods perform similarly to the preg_split() functions, but require less
     * formal structure to the regular expressions, and return a more predictable set of results.
     * The split() method will return the results of a call to the preg_split() function,
     * after setting some flags and normalizing the regular expression.
     *
     * @param $string
     * @param $pattern
     * @param null $limit
     * @return array[]|false|string[]
     */
    public static function split($string, $pattern, $limit = null)
    {
        $flags = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
        return preg_split(self::_normalize($pattern), $string, $limit, $flags);
    }

    /**
     * Method loops through the characters of a string, replacing them with
     * regular expression friendly character representations
     *
     * @param string $string
     * @param mixed $mask
     * @return string
     */
    public static function sanitize($string, $mask)
    {
        if (is_array($mask)) {
            $parts = $mask;
        } else if (is_string($mask)) {
            $parts = str_split($mask);
        } else {
            return $string;
        }

        foreach ($parts as $part) {
            $normalized = self::_normalize("\\{$part}");
            $string = preg_replace("{$normalized}m", "\\{$part}", $string);
        }

        unset($normalized);
        return $string;
    }

    /**
     * Method eliminates all duplicated characters in a string
     *
     * @param string $string
     * @return string
     */
    public static function unique($string)
    {
        $unique = '';
        $parts = str_split($string);

        foreach ($parts as $part) {
            if (!strstr($unique, $part)) {
                $unique .= $part;
            }
        }

        return $unique;
    }

    /**
     * Method returns the position of a substring within a larger string,
     * or -1 if the substring isn’t found
     *
     * @param string $string
     * @param string $substring
     * @param type $offset
     * @return int
     */
    public static function indexOf($string, $substring, $offset = null)
    {
        $position = strpos($string, $substring, $offset);
        if (!is_int($position)) {
            return -1;
        }
        return $position;
    }

    /**
     *
     * @param string $string
     * @param string $substring
     * @param type $offset
     * @return int
     */
    public static function lastIndexOf($string, $substring, $offset = null)
    {
        $position = strrpos($string, $substring, $offset);
        if (!is_int($position)) {
            return -1;
        }
        return $position;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    public static function singular($string)
    {
        $result = $string;

        foreach (self::$singular as $rule => $replacement) {
            $rule = self::_normalize($rule);

            if (preg_match($rule, $string)) {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }

        return $result;
    }

    /**
     *
     * @param string $string
     * @return string
     */
    public static function plural($string)
    {
        $result = $string;

        foreach (self::$plural as $rule => $replacement) {
            $rule = self::_normalize($rule);

            if (preg_match($rule, $string)) {
                $result = preg_replace($rule, $replacement, $string);
                break;
            }
        }

        return $result;
    }

    /**
     * Method remove diacritical marks form string
     *
     * @param string $string
     * @return string
     */
    public static function removeDiacriticalMarks($string)
    {
        return strtr($string, self::$diacriticalConversionTable);
    }

    /**
     *
     * @param string $string
     * @param array $badChars
     * @param string $replace
     * @return string
     */
    public static function fastClean($string, $badChars = [], $replace = '', $keepDiacritic = false)
    {
        if (empty($badChars)) {
            $badChars = ['.', ',', '_', '(', ')', '[', ']', '|', ';',
                '?', '<', '>', '/', '\\', '!', '@', '&', '*', ':', '+', '^',
                '=', '°', '´', '`', '%', "'", '"', '$', '#',
                '≤', '&le;', '≥', '&ge;', '≠', '&ne;',
                '‘', '&lsquo;', '’', '&rsquo;', '“', '&ldquo;', '”', '&rdquo;', '‚', '&sbquo;',
                '„', '&bdquo;', '′', '&prime;', '″', '&Prime;', '—', '&mdash;',
                '˜', '&tilde;', '‹', '&lsaquo;', '›', '&rsaquo;', '«', '&laquo;', '»', '&raquo;'
            ];
            //'‐', '–', '&ndash;'
        }

        if ($keepDiacritic === false) {
            $noDiacriticString = self::removeDiacriticalMarks($string);
        } else {
            $noDiacriticString = $string;
        }

        $cleanString = trim(str_replace($badChars, $replace, $noDiacriticString));

        unset($noDiacriticString);
        return $cleanString;
    }

    /**
     * Version of stripos with needles as an array or string
     *
     * @param $haystack
     * @param $needles
     * @return bool|false|int
     */
    public static function striposArray($haystack, $needles)
    {
        if (is_array($needles)) {
            foreach ($needles as $str) {
                if (is_array($str)) {
                    $pos = self::striposArray($haystack, $str);
                } else {
                    $pos = stripos($haystack, $str);
                }
                if ($pos !== false) {
                    return $pos;
                }
            }
            return false;
        } else {
            return stripos($haystack, $needles);
        }
    }

    /**
     * truncateHtml can truncate a string up to a number of characters while preserving whole words and HTML tags
     *
     * @param string $text String to truncate
     * @param integer $length Length of returned string, including ellipsis
     * @param string $ending Ending to be appended to the trimmed string
     * @param boolean $exact If false, $text will not be cut mid-word
     * @param boolean $considerHtml If true, HTML tags would be handled correctly
     *
     * @return string Trimmed string
     */
    public static function truncateHtml($text, $length = 100, $ending = '...', $exact = false, $considerHtml = true)
    {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);
            $total_length = mb_strlen($ending);
            $open_tags = [];
            $truncate = '';
            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                        // if tag is a closing tag
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                        // if tag is an opening tag
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }
                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length + $content_length > $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += mb_strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left + $entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }
                // if the maximum length is reached, get off the loop
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - mb_strlen($ending));
            }
        }
        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }
        // add the defined ending to the text
        $truncate .= $ending;
        if ($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }

    /**
     * Returns XSS-safe equivalent of string
     *
     * @param mixed $data
     */
    protected static function xss_safe($data)
    {
        if (func_num_args() > 1) {
            $args = func_get_args();
            $out = [];

            foreach ($args as $arg) {
                $out[] = self::xss_safe($arg);
            }

            return implode("", $out);
        }

        if (defined("ENT_HTML401")) {
            $t = htmlspecialchars($data, ENT_QUOTES | ENT_HTML401, "UTF-8");
        } else {
            $t = htmlspecialchars($data, ENT_QUOTES, "UTF-8");
        }

        return $t;
    }

    /**
     * XSS-safe replacement for echo.
     * Basically you should never use echo or print in your project, instead use php tags and this
     *
     * @param mixed $data
     */
    public static function exho($data)
    {
        echo self::xss_safe($data);
    }

    /**
     * XSS-safe replacement for echo, with formatting and ability to dump elements and attributes
     * Usage: echo_param("Hello, you're number <strong>?</strong>",$number);
     *
     * @param $string the format string
     */
    public static function echof($string)
    {
        if (substr_count($string, "?") !== func_num_args() - 1) {
            throw new Exception\Implementation("Number of arguments doesn't match number of ?s in format string.");
        }

        $out = $string;
        $args = func_get_args();
        array_shift($args);

        foreach ($args as $arg) {
            $formatPosition = strpos($out, "?");
            $out = substr($out, 0, $formatPosition) . self::xss_safe($arg) . substr($out, $formatPosition + 1);
        }
        echo($out);
    }

    /**
     * Safe printf. Escapes all arguments
     * The format string should not contain any concatenations or variables, just plain text
     *
     * @param string $formatString
     */
    public static function printf($formatString)
    {
        $args = func_get_args();
        $flag = 0;
        foreach ($args as &$arg) {
            if (!$flag++)
                continue; //skip first arg, format str
            $arg = self::xss_safe($arg);
        }
        call_user_func_array("\\printf", $args);
    }

    /**
     * Safe vprintf. Escapes all arguments
     * The format string should not contain any concatenations or variables, just plain text
     *
     * @param string $formatString
     * @param array args
     */
    public static function vprintf($formatString, $args)
    {
        foreach ($args as &$arg) {
            $arg = self::xss_safe($arg);
        }
        call_user_func_array("\\vprintf", [$formatString, $args]);
    }

    /**
     * This one replaces NewLines with <br/>
     *
     * @param unknown $data
     */
    public static function echo_br($data)
    {
        echo nl2br(self::xss_safe($data));
    }

    /**
     * Prepare text for inserting into email template
     *
     * @param string $text
     * @return string
     */
    public static function prepareEmailText($text)
    {
        $prepared = str_replace(['</p>', '</div>'], '<br/>', $text);
        $prepared = strip_tags($prepared, '<br/><br><a><img/><img><table><tr><td><tbody><meta/><meta>');
        $prepared = preg_replace('/\t+/', ' ', $prepared);
        $prepared = preg_replace('/\s+/', ' ', $prepared);

        return $prepared;
    }

    /**
     * @param $string
     * @return string
     */
    public static function createUrlKey($string)
    {
        $printableChars = preg_replace('/[[:^print:]]/', "", $string);
        $neutralChars = ['.', ',', '_', '(', ')', '[', ']', '|', ' '];
        $preCleaned = static::fastClean($printableChars, $neutralChars, '-');
        $cleaned = static::fastClean($preCleaned);
        $return = mb_ereg_replace('[\-]+', '-', trim(trim($cleaned), '-'));

        return strtolower($return);
    }

    public static function getEnStopwords()
    {
        return self::$stopwordsEn;
    }

    public static function getCsStopwords()
    {
        return self::$stopwordsCs;
    }

    /**
     * Clean string. Cleaned string contains only [a-z0-9\s].
     *
     * @param string     $str
     * @param bool       $removeStopwords
     *
     * @return string
     */
    public static function cleanString($str, $removeStopwords = false)
    {
        $cleanStr = self::removeDiacriticalMarks($str);
        $cleanStr = strtolower(strip_tags(trim($cleanStr)));
        $cleanStr = preg_replace('/[^a-z0-9\s]+/', ' ', $cleanStr);

        if ($removeStopwords) {
            $cleanStr = preg_replace('/\b(' . implode('|', self::$stopwordsCs) . ')\b/', ' ', $cleanStr);
            $cleanStr = preg_replace('/\b(' . implode('|', self::$stopwordsEn) . ')\b/', ' ', $cleanStr);
        }

        $cleanStr2 = self::removeMultipleSpaces($cleanStr);

        unset($cleanStr);

        return $cleanStr2;
    }

    /**
     * @param $string
     * @return string|string[]|null
     */
    public static function removeMultipleSpaces($string)
    {
        return preg_replace('/(\s|&nbsp;)+/', ' ', $string);
    }

}
