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

use Phramework\Testphase\Exceptions\UnsetGlobalException;
use Phramework\Testphase\Report\StatusReport;
use Phramework\Testphase\Testphase;
use Phramework\Testphase\TestParser;
use Phramework\Testphase\Util;
use Phramework\Exceptions\MissingParametersException;
use Phramework\Exceptions\IncorrectParametersException;
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionParser;
use GetOptionKit\OptionPrinter\ConsoleOptionPrinter;
use Rs\Json\Pointer;

/**
 * This class is used by the script executed as binary.
 * Construct method is responsible to parse the arguments passed to script.
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @version 1.0.0
 * @since 1.0.0
 * @todo Add time and memory statistics
 */
class Binary
{
    /**
     * Parsed arguments passed to script
     * @var \GetOptionKit\OptionResult
     */
    protected $arguments;

    protected $server = null;

    /**
     * Get argument specifications
     * @return OptionCollection
     */
    public static function getArgumentSpecifications()
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

        $specs->add('report:?', 'Report output directory')
            ->isa('String')
            ->defaultValue(null);

        $specs->add('show-globals', 'Show values of global variables')->defaultValue(false);
        $specs->add('debug', 'Show debug messages')->defaultValue(false);
        $specs->add('h|help', 'Show help')->defaultValue(false);
        $specs->add('no-colors', 'No colors')->defaultValue(false);
        $specs->add('i|immediate', 'Show error output immediately as it appears')->defaultValue(false);

        $specs->add('server-host?', 'Server host')->defaultValue(null);
        $specs->add('server-root',  'Server root path, default is ./public')->defaultValue('./public');

        return $specs;
    }

    /**
     * @param array $argv Array of arguments passed to script
     * @example
     * ```php
     * $binary = new Binary([
     *     __FILE__,
     *     '-d',
     *     './tests/'
     * ]);
     *
     * $binary->invoke();
     * ```
     */
    public function __construct($argv)
    {
        $parser = new OptionParser(static::getArgumentSpecifications());

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
        $arguments = $this->arguments;

        //Start build-in php server if server-host is set
        if (($serverHost = $arguments->{'server-host'}) !== null) {
            $this->server = new Server($serverHost, $arguments->{'server-root'});
            $this->server->start();
        }

        //Include bootstrap file if set
        if (($bootstrapFile = $arguments->bootstrap)) {
            require $bootstrapFile;
        }

        echo 'testphase v' . Testphase::getVersion() . PHP_EOL;

        if ($arguments->help) {
            echo 'Help:' . PHP_EOL;
            $printer = new ConsoleOptionPrinter;
            echo $printer->render(static::getArgumentSpecifications());
            return 0;
        } elseif ($arguments->debug) {
            echo 'Enabled options: ' . PHP_EOL;

            foreach ($arguments as $key => $spec) {
                echo $spec;
            }
        }

        try {
            $testParserCollection = $this->getTestParserCollection();
        } catch (\Exception $e) {
            echo $e->getMessage();
            return $this->stop(1);
        }

        //Statistics object
        $stats = (object)[
            'tests' => count($testParserCollection),
            'success' => 0,
            'error' => 0,
            'failure' => 0,
            'ignore' => 0,
            'incomplete' => 0,
            'errors' => []
        ];

        $testIndex = 0;
        /**
         * @var string[]
         */
        $executedTestparserFiles = [];
        /**
         * @var StatusReport[]
         */
        $executedTestphase = [];

        foreach ($testParserCollection as $testParser) {
            //Check if subdir (sub directory) argument is set
            if (isset($arguments->subdir) && $arguments->subdir !== null) {
                //If so check if file name passes the given pattern

                //Remove base dir from filename
                $cleanFilename = trim(
                    str_replace(
                        $arguments->dir,
                        '',
                        $testParser->getFilename()
                    ),
                    '/'
                );

                $match = false;

                //Check if file name matches any of the subdir patterns
                foreach ($arguments->subdir as $pattern) {
                    $pattern = '@' . $pattern . '@';
                    if (!!preg_match($pattern, $cleanFilename)) {
                        $match = $match || true;
                        break;
                    }
                }

                if (!$match) {
                    //Ignore
                    $stats->ignore += 1;
                    if ($arguments->verbose) {
                        echo sprintf(
                            'I %s',
                            $testParser->getFilename()
                        ) . PHP_EOL;
                    } else {
                        echo 'I';
                    }
                    continue;
                }
            }

            $meta = $testParser->getMeta();

            if (isset($meta->ignore) && $meta->ignore) {
                if ($arguments->verbose) {
                    echo sprintf(
                        'I %s',
                        $testParser->getFilename()
                    ) . PHP_EOL;
                } else {
                    echo 'I';
                }
                $stats->ignore += 1;
                continue;
            }

            if (isset($meta->incomplete) && $meta->incomplete !== false) {
                $stats->incomplete += 1;
            }

            if (!empty($meta->dependencies)) {
                //TODO
                var_dump('dependencies are set!');
            }

            try {
                //Complete test's testphase collection
                $testParser->createTest();
            } catch (\Exception $e) {
                echo sprintf(
                    'Unable to create test from file "%s" %s With message: "%s"',
                    $testParser->getFilename(),
                    PHP_EOL,
                    $e->getMessage()
                ) . PHP_EOL;
                return $this->stop(1);
            }

            $testphaseCollection = $testParser->getTest();

            //Include number of additional testphase collections (-1 because, one is already counted)
            $stats->tests += count($testphaseCollection) - 1;

            //Used when a TestParser contains multiple Testphase objects
            $testphaseIndex = 0;

            //Iterate though test parser's testphase collection
            foreach ($testphaseCollection as $testphase) {
                try {
                    /**
                     * @var StatusReport
                     */
                    $statusReport = $testphase->run();

                    $executedTestphase[] = $statusReport;

                    if ($statusReport->getStatus() == StatusReport::STATUS_SUCCESS) {
                        $stats->success += 1;

                        //Echo successful char
                        if ($arguments->verbose) {
                            echo sprintf(
                                    '. %s%s',
                                    $testParser->getFilename(),
                                    (
                                    $testphaseIndex === 0
                                        ? ''
                                        : ' (' . $testphaseIndex . ')'
                                    )
                                ) . PHP_EOL;
                        } else {
                            echo '.';
                        }
                    } elseif ($statusReport->getStatus() == StatusReport::STATUS_FAILURE) {
                        $stats->failure += 1;

                        //Echo unsuccessful char
                        if ($arguments->verbose) {
                            echo sprintf(
                                'F %s',
                                $testParser->getFilename()
                            ) . PHP_EOL;
                        } else {
                            echo 'F';
                        }
                    }
                    
                    $responseBody = $statusReport->getResponse()->getBody();

                    if ($testParser->getMeta()->JSONbody) {
                        //TODO only when is json
                        $export = $testParser->getExport();

                        $jsonPointer = new Pointer($responseBody);

                        //Fetch all test exports and add them as globals
                        foreach ($export as $globalKey => $pointer) {
                            $value = $jsonPointer->get($pointer);
                            Globals::set($globalKey, $value);
                        }
                    }

                    if ($arguments->debug) {
                        //TODO
                    }

                } catch (UnsetGlobalException $e) {

                    //Error message
                    $message = $e->getMessage();

                    $message = sprintf(
                        self::colored('Test "%s" failed with message', 'red') . PHP_EOL . ' %s' . PHP_EOL,
                        $testParser->getFilename(),
                        $message
                    );

                    //push message to error message
                    $stats->errors[] = $message;

                    //Echo unsuccessful char
                    if ($arguments->verbose) {
                        echo sprintf(
                            'F %s',
                            $testParser->getFilename()
                        ) . PHP_EOL;
                    } else {
                        echo 'F';
                    }

                    //print if immediate
                    if ($arguments->immediate) {
                        echo PHP_EOL . $message . PHP_EOL;
                    }

                    //Error message
                    $stats->error += 1;
                } catch (\Exception $e) {

                    //Error message
                    $message = $e->getMessage();

                    if ($arguments->debug) {
                        $message .= PHP_EOL . $testphase->getResponseBody();
                    }

                    $message = sprintf(
                        self::colored('Test "%s" failed with message', 'red') . PHP_EOL . ' %s' . PHP_EOL,
                        $testParser->getFilename(),
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
                            'F %s',
                            $testParser->getFilename()
                        ) . PHP_EOL;
                    } else {
                        echo 'F';
                    }

                    //print if immediate
                    if ($arguments->immediate) {
                        echo PHP_EOL . $message . PHP_EOL;
                    }

                    $stats->failure += 1;
                }

                ++$testIndex;
                //Show only 80 characters per line
                if (!$arguments->verbose && !($testIndex % 79)) {
                    echo PHP_EOL;
                }

                ++$testphaseIndex;
            }

            $executedTestparserFiles[] = $testParser->getFilename();
        }

        var_dump($executedTestparserFiles);

        echo PHP_EOL;

        if ($arguments->{'show-globals'}) {
            echo 'Globals:' . PHP_EOL;
            echo Globals::toString() . PHP_EOL;
        }

        //don't print if immediate is true
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
        Binary::output('Incomplete: ' . $stats->incomplete, 'yellow');
        echo ', ';
        Binary::output('Error: ' . $stats->error, 'red');
        echo ', ';
        Binary::output('Failure: ' . $stats->failure . PHP_EOL, 'red');


        echo 'Memory usage: ' . (int)(memory_get_usage(true)/1048576) . ' MB' . PHP_EOL;
        echo 'Elapsed time: ' . (time() - $_SERVER['REQUEST_TIME']) . ' s' . PHP_EOL;

        if ($arguments->report !== null) {
            Util::deleteDirectoryContents($arguments->report);

            $i = 0;
            foreach ($executedTestphase as $executed) {


                $report = json_encode($executed, JSON_PRETTY_PRINT);

                $f = fopen($arguments->report .  $i . '.json', 'w');

                fputs($f, $report);
                fclose($f);
                ++$i;
            }
        }

        if ($stats->error > 0) {
            return $this->stop(1);
        }

        if ($stats->failure > 0) {
            return $this->stop(2);
        }

        return $this->stop(0);
    }

    /**
     * Prepare to close
     * @param $returnCode
     * @return mixed
     */
    protected function stop($returnCode) {
        if ($this->server !== null) {
            $this->server->stop();
        }

        return $returnCode;
    }

    /**
     * @return TestParser[]
     * @throws \Exception
     */
    protected function getTestParserCollection()
    {
        $dir = $this->arguments->dir;

        //Get all .json files in given directory
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
         * @var TestParser[]
         */
        $testParserCollection = [];

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

                throw new \Exception($message);
            }
            $testParserCollection[] = $testParser;
        }

        //Sort tests by order
        uasort($testParserCollection, [self::class, 'sortTestParser']);

        return $testParserCollection;
    }

    /**
     * Sort TestParsers ascending
     * @param \Phramework\Testphase\TestParser $a
     * @param \Phramework\Testphase\TestParser $b
     * @return int Returns 1 if order of first TestParser is larger
     */
    public static function sortTestParser(TestParser $a, TestParser $b)
    {
        return (
            $a->getMeta()->order < $b->getMeta()->order
            ? -1
            : 1
        );
    }

    /**
     * Returned colored text
     * @param string $text
     * @param string $color
     * @return string
     */
    public function colored($text, $color)
    {
        $colors = [
            'black'  => '0;30',
            'red'    => '0;31',
            'green'  => '0;32',
            'blue'   => '1;34',
            'yellow' => '1;33'
        ];

        $c = (
            array_key_exists($color, $colors)
            ? $colors[$color]
            : $colors['black']
        );

        if ($this->arguments->{'no-colors'}) {
            return $text;
        } else {
            return "\033[". $c . 'm' . $text . "\033[0m";
        }
    }

    /**
     * Print colored text
     * @param string $text
     * @param string $color
     */
    public function output($text, $color)
    {
        echo $this->colored($text, $color);
    }
}
