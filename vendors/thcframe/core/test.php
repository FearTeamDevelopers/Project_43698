<?php

namespace THCFrame\Core;

/**
 * Test class
 */
class Test
{

    private static $_tests = [];

    /**
     * @param $callback
     * @param string $title
     * @param string $set
     */
    public static function add($callback, $title = 'Unnamed Test', $set = 'General')
    {
        self::$_tests[] = [
            'set' => $set,
            'title' => $title,
            'callback' => $callback
        ];
    }

    /**
     * @param null $before
     * @param null $after
     * @return array
     */
    public static function run($before = null, $after = null)
    {
        if ($before) {
            $before(self::$_tests);
        }

        $passed = [];
        $failed = [];
        $exceptions = [];

        foreach (self::$_tests as $test) {
            try {
                $result = call_user_func($test['callback']);

                if ($result) {
                    $passed[] = [
                        'set' => $test['set'],
                        'title' => $test['title']
                    ];
                } else {
                    $failed[] = [
                        'set' => $test['set'],
                        'title' => $test['title']
                    ];
                }
            } catch (\Exception $e) {
                $exceptions[] = [
                    'set' => $test['set'],
                    'title' => $test['title'],
                    'type' => get_class($e),
                    'message' => $e->getMessage()
                ];
            }
        }

        if ($after) {
            $after(self::$_tests);
        }

        return [
            'passed' => $passed,
            'failed' => $failed,
            'exceptions' => $exceptions
        ];
    }

}
