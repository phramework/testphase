<?php
/**
 * Copyright 2015-2016 Xenofon Spafaridis
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

/**
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @todo add log
 */
class Server
{
    /**
     * @var string
     */
    protected $pid;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var string
     */
    protected $root;

    /**
     * Server constructor.
     * @param string $host
     * @param string $root
     */
    public function __construct(string $host, string $root = './public')
    {
        $this->host = $host;
        $this->root = $root;
    }

    /**
     * Start server
     */
    public function start()
    {
        $command = sprintf(
            'php -S %s -t %s',
            $this->host,
            $this->root
        );

        echo sprintf(
            'Start server %s at root %s...',
            $this->host,
            $this->root
        ) . PHP_EOL;

        $this->pid = exec("$command > /dev/null 2>&1 & echo $!; ", $output);
    }

    /**
     * Stop server
     */
    public function stop()
    {
        echo 'Stop server...' . PHP_EOL;

        $command = sprintf(
            'kill -9 %s',
            $this->pid
        );

        exec($command);
    }
}