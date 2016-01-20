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
     * @note In php 7.0 json_decode has errors when string is empty
     */
    public static function isJSON($string)
    {
        if (strlen($string) === 0) {
            return false;
        }

        $object = json_decode($string);

        return (is_string($string) && json_last_error() == JSON_ERROR_NONE
            ? true
            : false
        );
    }

    /**
     * Create a random readable word
     * @param  int $length *[Optional]* String's length
     * @return string
     * @link https://github.com/phramework/phramework/blob/master/src/Models/Util.php Source
     */
    public static function readableRandomString($length = 8)
    {

        $length = intval($length);

        $consonants = [
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
        ];
        $vowels = ['a', 'e', 'i', 'o', 'u'];

        $string = '';
        srand((double) microtime() * 1000000);
        $max = floor($length / 2);

        for ($i = 0; $i < $max; ++$i) {
            $string .= $consonants[rand(0, count($consonants)-1)];
            $string .= $vowels[rand(0, count($vowels)-1)];
        }

        if (strlen($string) < $length) {
            $string .= $vowels[rand(0, count($vowels)-1)];
        }

        return $string;
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
     * default `[]` (allow all)
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
    /**
     * Cartesian product
     * @param  array $input [description]
     * @return array        [description]
     * @source http://stackoverflow.com/a/6313346/2255129
     * @example
     * ```php
     * $input = [
     *     'arm' => ['A', 'B'],
     *     'gender' => ['Female', 'Male']
     * ];
     *
     * print_r(Util::cartesian($input));
     * ```
     */
    public static function cartesian(array $input)
    {
        $result = array();

        foreach ($input as $key => $values) {
            // If a sub-array is empty, it doesn't affect the cartesian product
            if (empty($values)) {
                continue;
            }

            // Seeding the product array with the values from the first sub-array
            if (empty($result)) {
                foreach ($values as $value) {
                    $result[] = [$key => $value];
                }
            } else {
                // Second and subsequent input sub-arrays work like this:
                //   1. In each existing array inside $product, add an item with
                //      key == $key and value == first item in input sub-array
                //   2. Then, for each remaining item in current input sub-array,
                //      add a copy of each existing array inside $product with
                //      key == $key and value == first item of input sub-array

                // Store all items to be added to $product here; adding them
                // inside the foreach will result in an infinite loop
                $append = [];

                foreach ($result as &$product) {
                    // Do step 1 above. array_shift is not the most efficient, but
                    // it allows us to iterate over the rest of the items with a
                    // simple foreach, making the code short and easy to read.
                    $product[$key] = array_shift($values);

                    // $product is by reference (that's why the key we added above
                    // will appear in the end result), so make a copy of it here
                    $copy = $product;

                    // Do step 2 above.
                    foreach ($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    // Undo the side effecst of array_shift
                    array_unshift($values, $product[$key]);
                }

                // Out of the foreach, we can add to $results now
                $result = array_merge($result, $append);
            }
        }

        return $result;
    }
}
