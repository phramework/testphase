<?php
/**
 * Copyright 2015 Xenofon Spafaridis
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
 * Various utility methods
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 * @since 1.0.0
 */
class Util
{
    /**
     * Check if given string is valid JSON string
     * @param  string  $string
     * @return boolean
     */
    public static function isJSON($string)
    {
        $object = json_decode($string);
        
        return (is_string($string) && json_last_error() == JSON_ERROR_NONE
            ? true
            : false
        );
    }

    /**
     * Create a random readable word
     * @param  integer $length *[Optional]* String's length
     * @return string
     * @link https://github.com/phramework/phramework/blob/master/src/Models/Util.php Source
     */
    public static function readableRandomString($length = 8)
    {
        $conso = [
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
        ];
        $vocal = ['a', 'e', 'i', 'o', 'u'];

        $word = '';
        srand((double) microtime() * 1000000);
        $max = $length / 2;

        for ($i = 1; $i <= $max; ++$i) {
            $word .= $conso[rand(0, 19)];
            $word .= $vocal[rand(0, 4)];
        }
        return $word;
    }

    /**
     * Get an array that represents directory tree
     * @param string  $directory     Directory path
     * @param boolean $recursive     *[Optional]* Include sub directories
     * @param boolean $listDirs      *[Optional]* Include directories on listing
     * @param boolean $listFiles     *[Optional]* Include files on listing
     * @param string  $exclude       *[Optional]* Exclude paths that matches this
     * regular expression
     * @param array   $allowed_filetypes *[Optional]* Allowed file extensions,
     * default `[]`` (allow all)
     * @param boolean $relative_path *[Optional]* Return paths in relative form,
     * default `false`
     * @link https://github.com/phramework/phramework/blob/master/src/Models/Util.php Source
     */
    public static function directoryToArray(
        $directory,
        $recursive = false,
        $listDirs = false,
        $listFiles = true,
        $exclude = '',
        $allowed_filetypes = [],
        $relative_path = false
    ) {
        $arrayItems = [];
        $skipByExclude = false;
        $handle = opendir($directory);
        if ($handle) {
            while (false !== ($file = readdir($handle))) {
                preg_match(
                    '/(^(([\.]) {1,2})$|(\.(svn|git|md|htaccess))|(Thumbs\.db|\.DS_STORE|\.|\.\.))$/iu',
                    $file,
                    $skip
                );
                if ($exclude) {
                    preg_match($exclude, $file, $skipByExclude);
                }
                if ($allowed_filetypes && !is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                    $ext = strtolower(preg_replace('/^.*\.([^.]+)$/D', '$1', $file));
                    if (!in_array($ext, $allowed_filetypes)) {
                        $skip = true;
                    }
                }
                if (!$skip && !$skipByExclude) {
                    if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                        if ($recursive) {
                            $arrayItems = array_merge(
                                $arrayItems,
                                self::directoryToArray(
                                    $directory . DIRECTORY_SEPARATOR . $file,
                                    $recursive,
                                    $listDirs,
                                    $listFiles,
                                    $exclude,
                                    $allowed_filetypes,
                                    $relative_path
                                )
                            );
                        }
                        if ($listDirs) {
                            $arrayItems[] = (
                                $relative_path
                                ? $file
                                : $directory . DIRECTORY_SEPARATOR . $file
                            );
                        }
                    } else {
                        if ($listFiles) {
                            $arrayItems[] = (
                                $relative_path
                                ? $file
                                : $directory . DIRECTORY_SEPARATOR . $file
                            );
                        }
                    }
                }
            }
            closedir($handle);
        }
        return $arrayItems;
    }
}
