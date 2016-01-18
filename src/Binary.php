<?php
/**
 * Copyright 2015 - 2016 Xenofon Spafaridis
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Phramework\Testphase;

use \Phramework\Testphase\Testphase;
use \Phramework\Testphase\TestParser;
use \Phramework\Testphase\Util;
use \Phramework\Exceptions\MissingParametersException;
use \Phramework\Exceptions\IncorrectParametersException;
use \GetOptionKit\OptionCollection;
use \GetOptionKit\OptionParser;
use \GetOptionKit\OptionPrinter\ConsoleOptionPrinter;

/**
 * This class is used by the script executed as binary.
 * Construct method is responsible to parse the arguments passed to script.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 1.0.0
 * @since 1.0.0
 */
class Binary
{
    /**
     * @var GetOptionKit\OptionResult
     */
    protected $arguments;

    /**
     * @param array $argv Array of arguments passed to script
     */
    public function __construct($argv)
    {
        $specs = new OptionCollection;
        $specs->add('d|dir:', 'Tests directory path')
            ->isa('String');

        $specs->add('s|subdir+', 'Optional, subdirectory pattern, can be used multiple times as OR expression')
            ->isa('String')
            ->defaultValue(null);

        $specs->add('b|bootstrap?', 'Bootstrap file path')
            ->isa('File')
            ->defaultValue(null);

        $specs->add('v|verbose', 'Verbose output')
            ->defaultValue(false);

        $specs->add('show-globals', 'Show values of global variables')->defaultValue(false);
        $specs->add('debug', 'Show debug messages')->defaultValue(false);
        $specs->add('h|help', 'Show help')->defaultValue(false);
        $specs->add('no-colors', 'No colors')->defaultValue(false);
        $specs->add('i|immediate', 'Show error output immediately as it appears')->defaultValue(false);

        $parser = new OptionParser($specs);

        $this->arguments = $parser->parse($argv);

        unset($parser);
    }

    /**
     * Invoke scripts
     * @return integer Returns indicate how the script exited.
     * Normal exit is generally represented by a 0 return.
     */
    public function invoke()
    {
        echo 'testphase v' . Testphase::getVersion() . PHP_EOL;

        $arguments = $this->arguments;

        if ($arguments->help) {
            echo 'Help:' . PHP_EOL;
            $printer = new ConsoleOptionPrinter;
            echo $printer->render($specs);
            return 0;
        } elseif ($arguments->debug) {
            echo 'Enabled options: ' . PHP_EOL;

            foreach ($arguments as $key => $spec) {
                echo $spec . PHP_EOL;
            }
        }

        $dir = $arguments->dir;

        $bootstrapFile = $arguments->bootstrap;

        if ($bootstrapFile) {
            require $bootstrapFile;
        }

        //Get all .json files in directory
        $testFiles = array_map(
            function ($f) {
                return str_replace('//', '/', $f);
            },
            Util::directoryToArray(
                $dir,
                true,
                false,
                true,
                '/^\.|\.\.$/',
                ['json'], //Only .json files
                false
            )
        );

        /**
         * @var TestParser
         */
        $tests = [];

        foreach ($testFiles as $filename) {
            try {
                $testParser = new TestParser($filename);
            } catch (\Exception $e) {
                $message = sprintf(
                    'Failed to parse file "%s" %s With message: "%s"',
                    $filename,
                    PHP_EOL,
                    $e->getMessage()
                ) . PHP_EOL;

                if (get_class($e) == IncorrectParametersException::class) {
                    $message .= PHP_EOL . 'Incorrect:' . PHP_EOL
                        . json_encode($e->getParameters(), JSON_PRETTY_PRINT) . PHP_EOL;
                } elseif (get_class($e) == MissingParametersException::class) {
                    $message .= PHP_EOL . 'Missing:' . PHP_EOL
                        . json_encode($e->getParameters(), JSON_PRETTY_PRINT) . PHP_EOL;
                }

                echo $message;

                return 1;
            }
            $tests[] = $testParser;
        }

        //Sort tests by order
        uasort($tests, [self::class, 'sortTests']);

        //Statistics object
        $stats = (object)[
            'tests' => count($tests),
            'success' => 0,
            'error' => 0,
            'ignore' => 0,
            'errors' => []
        ];

        $testIndex = 0;

        //Execute tests
        foreach ($tests as $test) {

            //Check if subdir argument is set
            if (isset($arguments->subdir) && $arguments->subdir !== null) {
                //If so check if file name passes the given pattern

                //Remove base dir from filename
                $cleanFilename = trim(
                    str_replace(
                        $arguments->dir,
                        '',
                        $test->getFilename()
                    ),
                    '/'
                );

                $match = false;

                //Check if any of the patterns ar matching
                foreach ($arguments->subdir as $pattern) {
                    $pattern = '@' . $pattern . '@';
                    if (!!preg_match($pattern, $cleanFilename)) {
                        $match = $match || true;
                        break;
                    }
                }

                if (!$match) {
                    $stats->ignore += 1;
                    if ($arguments->verbose) {
                        echo sprintf(
                            'I %s',
                            $test->getFilename()
                        ) . PHP_EOL;
                    } else {
                        echo 'I';
                    }
                    continue;
                }
            }

            $meta = $test->getMeta();

            if (isset($meta->ignore) && $meta->ignore) {
                if ($arguments->verbose) {
                    echo sprintf(
                        'I %s',
                        $test->getFilename()
                    ) . PHP_EOL;
                } else {
                    echo 'I';
                }
                $stats->ignore += 1;
                continue;
            }


            try {
                //Complete test's testphase collection
                $test->createTest();
            } catch (\Exception $e) {
                echo sprintf(
                    'Unable to create test from file "%s" %s With message: "%s"',
                    $test->getFilename(),
                    PHP_EOL,
                    $e->getMessage()
                ) . PHP_EOL;
                return 1;
            }

            $testphaseCollection = $test->getTest();

            //Include number of additional testphase collections
            $stats->tests += count($testphaseCollection) - 1;

            foreach ($testphaseCollection as $testphase) {
                try {
                    $ok = $testphase->run(function (
                        $responseStatusCode,
                        $responseHeaders,
                        $responseBody,
                        $responseBodyObject = null
                    ) use (
                        $test,
                        $arguments
                    ) {
                        //global $arguments;
                        //global $test;
                        //todo move to TestParser
                        $export = $test->getExport();

                        //Fetch all teest exports and add them as globals
                        foreach ($export as $key => $value) {
                            $path = explode('.', $value);

                            $pathValue = $responseBodyObject;

                            foreach ($path as $p) {
                                //@todo array index
                                $arrayIndex = 0;

                                if (is_array($pathValue)) {
                                    $pathValue = $pathValue[$arrayIndex]->{$p};
                                } else {
                                    $pathValue = $pathValue->{$p};
                                }
                            }

                            Globals::set($key, $pathValue);
                        }

                        if ($arguments->debug) {
                            echo 'Response Status Code:' . PHP_EOL;
                            echo $responseStatusCode . PHP_EOL;
                            echo 'Response Headers:' . PHP_EOL;
                            print_r($responseHeaders);
                            echo PHP_EOL;
                            echo 'Response Body:' . PHP_EOL;
                            echo json_encode($responseBodyObject, JSON_PRETTY_PRINT) . PHP_EOL;
                        }
                    });

                    //Echo successful char
                    if ($arguments->verbose) {
                        echo sprintf(
                            '. %s',
                            $test->getFilename()
                        ) . PHP_EOL;
                    } else {
                        echo '.';
                    }
                    $stats->success += 1;
                } catch (\Exception $e) {

                    //@todo if verbose show more details (trace)
                    $message = $e->getMessage();

                    if ($arguments->debug) {
                        $message .= PHP_EOL . $testphase->getResponseBody();
                    }
                    $message = sprintf(
                        self::colored('Test "%s" failed with message', 'red') . PHP_EOL . ' %s' . PHP_EOL,
                        $test->getFilename(),
                        $message
                    );

                    if (get_class($e) == IncorrectParametersException::class) {
                        $message .= 'Incorrect:' . PHP_EOL
                            . json_encode($e->getParameters(), JSON_PRETTY_PRINT) . PHP_EOL;
                    } elseif (get_class($e) == MissingParametersException::class) {
                        $message .= 'Missing:' . PHP_EOL
                            . json_encode($e->getParameters(), JSON_PRETTY_PRINT) . PHP_EOL;
                    }

                    //push message to error message
                    $stats->errors[] = $message;

                    //Echo unsuccessful char
                    if ($arguments->verbose) {
                        echo sprintf(
                            'E %s',
                            $test->getFilename()
                        ) . PHP_EOL;
                    } else {
                        echo 'E';
                    }

                    //print if immediate
                    if ($arguments->immediate) {
                        echo PHP_EOL . $message . PHP_EOL;
                    }

                    $stats->error += 1;
                }
                ++$testIndex;
                //Allow only 80 characters per line
                if (!($testIndex % 79)) {
                    echo PHP_EOL;
                }
            }
        }

        echo PHP_EOL;

        if ($arguments->{'show-globals'}) {
            echo 'Globals:' . PHP_EOL;
            echo Globals::toString() . PHP_EOL;
        }

        //dont print if immediate is true
        if (!$arguments->immediate && !empty($stats->errors)) {
            echo 'Errors:' . PHP_EOL;
            foreach ($stats->errors as $e) {
                echo $e . PHP_EOL;
            }
        }

        echo 'Complete!' . PHP_EOL;
        echo 'Tests:' . $stats->tests . ', ';
        Binary::output('Successful: ' . $stats->success, 'green');
        echo ', ';
        Binary::output('Ignored: ' . $stats->ignore, 'yellow');
        echo ', ';
        Binary::output('Unsuccessful: ' . $stats->error . PHP_EOL, 'red');

        if ($stats->error > 0) {
            return (1);
        }

        return 0;
    }

    protected static function sortTests($a, $b)
    {
        return (
            $a->getMeta()->order < $b->getMeta()->order
            ? -1
            : 1
        );
    }

    /**
     * @todo add no-colors
     */
    public static function colored($text, $color)
    {
        $colors = [
            'black' => '0;30',
            'red' => '0;31',
            'green' => '0;32',
            'blue' => '1;34',
            'yellow' => '1;33'
        ];

        $c = (
            array_key_exists($color, $colors)
            ? $colors[$color]
            : $colors['black']
        );

        if (false && $arguments['no-colors']->value) {
            return $text;
        } else {
            return "\033[". $c . "m" . $text . "\033[0m";
        }
    }

    public static function output($text, $color)
    {
        echo Binary::colored($text, $color);
    }
}
