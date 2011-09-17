<?php
// Copyright (C) 2010-2011 Alejandro Salazar <alphazygma@gmail.com>

/*
 * Thrae PHP Web Service Framework
 * 
 * This library is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation; either version 2.1 of the License, or (at your option)
 * any later version.
 * 
 * This library is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library; if not, write to 
 *     Free Software Foundation, Inc.
 *     51 Franklin Street, Fifth Floor
 *     Boston, MA  02110-1301  USA
 */

/**
 * The <kbd>Thrae_Parser_Json</kbd> class wraps the functionality of the native
 * <kbd>json_encode</kbd> and <kbd>json_decode</kbd>.
 * <p>It adds functions to facilitate validation or correct parsing of String
 * messages or Associative Arrays.</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae/Parser
 * @version 0.1
 * @since 0.1
 */
class Thrae_Parser_Json
{
    /**
     * Validates if the JSON string can be parsed.
     * 
     * @param  string $jsonString
     * @return bool
     */
    public static function validateString($jsonString)
    {
        $result = json_decode($jsonString, true);
        return is_array($result);
    }
    
    /**
     * Converts a simple String or an Associative Array into a JSON string.
     * 
     * @param  mixed $data A String or an Associative Array
     * @throws Thrae_Parser_Exception if data is not of the supported types
     * @return string
     */
    public static function toJson($data)
    {
        if (empty($data)) {
            return '{}';
        } else if (is_string($data)) {
            return '{0:' . json_encode($data) . '}';
        } else if (is_array($data)) {
            return json_encode($data);
        } else {
            throw new Thrae_Parser_Exception(
                'Parse data was not a String or Array'
            );
        }
    }
    
    /**
     * Converts an JSON string into an associative array.
     * <p>If the JSON string is not parsable meaning the <kbd>json_decode</kbd>
     * function returns <kbd>null</kbd> or the parsed value is a string meaning
     * the <kbd>json_decode</kbd> function determined the parameter was not even
     * a JSON value, an Exception is thrown.</p>
     * 
     * @param  string $json
     * @return array
     * @throws Thrae_Parser_Exception If the JSON string is not parsable or the
     *      parsing result is a string.
     */
    public static function toArray($json)
    {
        $result = json_decode($json, true);

        if (is_null($result) || is_string($result)) {
            throw new Thrae_Parser_Exception('JSON string could not be parsed');
        }

        return $result;
    }
}
