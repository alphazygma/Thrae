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
 * The <kbd>Thrae_Config_Services</kbd> parses a configuration file containing
 * REST url mappings and parses the URL definitions with the appropriate regular
 * expressions.
 * <p>The services to be parsed should be a definition on the INI file with the
 * following pattern:
 * <code>services.{Name_of_the_class_that_serves} = '/uri/to/listen'</code>
 * Example:
 * <code>services.User_Group_Benefits = '/usr/{$usr[int]}/add/{$benefit}'
 * </code>
 * </p>
 * <p>The example above would be parsed as:
 * <code>array(
 *    'services' => array(
 *        0 => array(
 *            'uri'       => '#^/user/([0-9]+)/add/([a-zA-Z]+)$#',
 *            'className' => 'User_Group_Benefits'
 *            'variables' => array(
 *                0 => 'usr',
 *                1 => 'benefit'
 *            )
 *        ),
 *        ...
 *     )
 * );</code>
 * </p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @version Thrae/Config
 * @version 0.1
 * @since 0.1
 */
class Thrae_Config_Services extends Thrae_Config
{
    const REGEX_INT      = '[-+]?[0-9]+';
    const REGEX_FLOAT    = '[-+]?([0-9]*\.[0-9]+|[0-9]+)';
    const REGEX_ALPHA    = '[A-Za-z]+';
    const REGEX_ALPHANUM = '[A-Za-z0-9_]+';
    const REGEX_STRING   = '[A-Za-z0-9-+_,\$\.\!\*\(\)]+';
    
    const TYPE_INT      = 'int';
    const TYPE_FLOAT    = 'float';
    const TYPE_ALPHA    = 'alpha';
    const TYPE_ALPHANUM = 'alphanum';
    const TYPE_STRING   = 'string';
    
    /**
     * Regular expression string used to match a URI chunk, the chunk does NOT
     * represent a variable to be parsed.
     * <p>This chunks have to follow the following rules:
     * <ul>
     *   <li>Starts with a slash "/"</li>
     *   <li>A string that contains letters (uppercase or lowercase), numbers
     *       or the underscore.
     *   </li>
     *   <li>Such string must not be only underscores</li>
     * </ul>
     * </p>
     * <p>Examples: /path or /_someName_5_</p>
     * @var String
     */
    const URI_CHUNK_REGEX_SIMPLE = '/([A-Za-z0-9_]*[A-Za-z0-9][_]*)';
    /**
     * Regular expression string used to match a URI chunk, the chunk REPRESENTS
     * a variable to be parsed.
     * <p>This chunks have to follow the following rules:
     * <ul>
     *   <li>Starts with a "/{$"</li>
     *   <li>has a variable name, which follows the next rules:
     *       <ul>
     *         <li>A string that contains letters (uppercase or lowercase), 
     *             numbers or the underscore.
     *         </li>
     *         <li>Such string must not be only underscores</li>
     *       </ul>
     *   </li>
     *   <li>Optionally may contain restriction types surrounded by square
     *       brackets, where each type is any of "int", "float", "string", 
     *       "alpha", "alphanum", and each type is separated by a pipe "|". 
     *       <br/>
     *       example: [int|alpha|float]
     *   </li>
     *   <li>Ends with a "}"</li>
     * </ul>
     * </p>
     * <p>Examples: /{$myVar_03} or /{$user[int|float]}</p>
     * <p>Compared to the consolidated constant URI_VALID_REGEX, an extra set
     * of parenthesis are added to surround the whole optional types as a group
     * so when matched, the whole types can be retrieved<br/>
     * <b>(</b>
     *  (int|float|string|alpha|alphanum)(\|(int|float|string|alpha|alphanum))*
     * <b>)</b>
     * </p>
     * @var String
     */
    const URI_CHUNK_REGEX_VAR = 
        '/{\$([A-Za-z0-9_]*[A-Za-z0-9][_]*)(\[((int|float|string|alpha|alphanum)(\|(int|float|string|alpha|alphanum))*)\])?}';
    /**
     * Merges both types of URI chunks into a single regular expression that
     * checks that the full URI is valid.
     * @var String
     */
    const URI_VALID_REGEX = 
        '^((/([A-Za-z0-9_]*[A-Za-z0-9][_]*))|(/{\$([A-Za-z0-9_]*[A-Za-z0-9][_]*)(\[(int|float|string|alpha|alphanum)(\|(int|float|string|alpha|alphanum))*\])?}))+$';
    
    /**
     * Constructs a new <kbd>Thrae_Config_Services</kbd> object and parses the
     * given  file represented by <kbd>$filename</kbd> on the given environment 
     * <kbd>$section</kbd>, then parses the services into the respective regular
     * expression mappings while keeping track of the variables requested.
     * 
     * @param string $fileName
     * @param string $section 
     */
    public function __construct($fileName, $section)
    {
        parent::__construct($fileName, $section);
        
        $this->_transform();
    }
    
    /**
     * Parses the 'service' node from the given class names and URIs into
     * array that contains the URI with equivalente RegEx, the class name and
     * the desired variables requested by the developer.
     */
    protected function _transform()
    {
        if (empty($this->_data['service'])) {
            throw new Thrae_Config_Exception('No Services found');
        }
        
        $parsedServices = array();
        foreach ($this->_data['service'] as $className => $uri) {
            if (!$this->_isValidUri($uri)) {
                throw new Thrae_Config_Exception(
                    "The URI '{$uri}' is not valid"
                );
            } else {
                $varsParsed = $this->_parseUri($uri);
                $varsParsed['className'] = $className;
                
                $parsedServices[] = $varsParsed;
                unset($varsParsed);
            }
        }
        unset($this->_data['service']);
        $this->_data['service'] = $parsedServices;
    }
    
    /**
     * Validates if the given URI is valid by checking that each of the chunks
     * is a valid string or a variable.
     * <p>Some examples of valid URIs
     * <ul>
     *    <li>/some/uri</li>
     *    <li>/some/other/with/{$variable}</li>
     *    <li>/more/{$var1}/{$var2}/_with_2_varibles</li>
     *    <li>/uri/with/_variables_and_types/{$myVar[int|alpha}</li>
     * </ul>
     * <p/>
     * <p>URIs cannot end up in a "/" character and if they don't follow the
     * URL RFC (http://www.ietf.org/rfc/rfc1738.txt).</p>
     * 
     * @param type $uri
     * @return type 
     * @see ::URI_VALID_REGEX
     * @see http://www.ietf.org/rfc/rfc1738.txt
     */
    protected function _isValidUri($uri)
    {
        return 1 == preg_match('#' . self::URI_VALID_REGEX . '#', $uri);
    }
    
    /**
     * Parses a developer defined URI into an equivalent URI with regular
     * expressions.
     * <p>It retrieves only the URI chunks that represents a variable and
     * extracts the variable name and the restriction types if supplied.</p>
     * <p>A URI chunk is defined as a section of the URI that starts with a "/"
     * and ends before the next "/" or the end of the URI.<br/>
     * So, the following example contains 3 URI chunks:<br/>
     * e.g. /some/{$random[string]}/uri<br/>
     * <ol>
     *    <li>/some</li>
     *    <li>/{$random[string]}</li>
     *    <li>/uri</li>
     * </ol>
     * </p>
     * <p>Returns an array fo the following type:
     * <code>
     * array(
     *     'uriParser' => string,  // the RegEx to match the URI
     *     'varParser' => string,  // the RegEx to parse the URI variables
     *     'varNames' => array     // contains the variable names
     * );
     * </code>
     * </p>
     * 
     * @param string $uri 
     * @return array
     */
    protected function _parseUri($uri)
    {
        $matches = null;
        
        // Using regular expression that checks for URI chunks that match a
        // variable
        $pattern = '#'.self::URI_CHUNK_REGEX_VAR.'#';
        
        /* After applying the RegEx, here is what groups would look like for
         * a URI chunk match:
         * /{$uriVar}
         *      0 => /{$uriVar}
         *      1 => uriVar
         * /{$x[int|string]}
         *      0 => /{$x[int|string]}
         *      1 => x
         *      2 => [int|string]
         *      3 => int|string
         *      4 => int
         *      5 => |string
         *      6 => string
         * /{$y[int|float|alpha|alphanum]}
         *      0 => /{$y[int|float|alpha|alphanum]}
         *      1 => y
         *      2 => [int|float|alpha|alphanum]
         *      3 => int|float|alpha|alphanum
         *      4 => int
         *      5 => |alphanum
         *      6 => alphanum
         * /{$myVar[int]}
         *      0 => /{$myVar[int]}
         *      1 => myVar
         *      2 => [int]
         *      3 => int
         *      4 => int
         */
        $matchNum = preg_match_all($pattern, $uri, $matches, PREG_SET_ORDER);
        
        // If $matchNum = 0, the URI is plain
        // If $matchNum != 0, the URI contained variables
        if ($matchNum === false) {
            throw new Thrae_Config_Exception(
                'Problem with parsing URI definition, this should not happen ' .
                'for a validation in the Thrae_Config_Service constructor ' .
                'was made to check for valid URLs before this - Time to debug'
            );
        } else if ($matchNum == 0) {
            return array(
                'uriParser' => $uri,
                'varParser' => null,
                'varNames'  => null // indicates is a URI with no variables
            );
        } else {
            // Variable used to replace into, the regex that match the variable
            // types defined by the developer.
            $parsedUri = $uri;
            $variableParser = $uri;
            
            // Variable used to store the variable names in order of how they
            // were found in the URI definition
            $variableNames = array();
            
            for ($i = 0; $i < $matchNum; $i++) {
                $matchGroups = $matches[$i];
                
                $parsedUriChunk = $this->_parseUriChunk($matchGroups);
                
                // Replace the uri chunk with the equivalent regular expression
                $parsedUri = preg_replace(
                    '#' . preg_quote($parsedUriChunk['uriChunk']) . "#",
                    '/' . $parsedUriChunk['varRegEx'],
                    $parsedUri
                );
                $variableParser = preg_replace(
                    '#' . preg_quote($parsedUriChunk['uriChunk']) . "#",
                    '/(' . self::REGEX_STRING . ')',
                    $variableParser
                );
                
                $variableNames[] = $parsedUriChunk['varName'];
            }
            
            return array(
                'uriParser' => $parsedUri,
                'varParser' => $variableParser,
                'varNames'  => $variableNames
            );
        }
    }
    
    /**
     * Parses an array containing the matches of a URI chunk that represents
     * the variable name and the restriction types supplied.
     * <p>Returns an array of the following type:
     * <code>array(
     *     'uriChunk' => string,  // the URI chunk that had the variable
     *     'varName'  => string,  // the variable name defined in the URI chunk
     *     'varRegEx' => string   // the regular expression for the URI chunk
     * );
     * </code>
     * </p>
     * 
     * @param array  $uriChunkMatchGroups
     * @return array 
     */
    protected function _parseUriChunk($uriChunkMatchGroups)
    {
        $typesArray = null;
        
        // Check if there were any types defined for the variable to parse into
        // the respective matching regular expression
        // If there were types, this means that index 2 and 3 exist
        //    $varMatchGroups[2] = '[type|type|type]'
        //    $varMatchGroups[3] = 'type|type|type'
        // If there were no types, the "string" type is assumed
        if (count($uriChunkMatchGroups) >= 4) {
            // To improve performance, the simplest a regular expression is the
            // best, so reduce whenever a more generic type is found.
            
            // "string" type covers all types
            if (strpos($uriChunkMatchGroups[3], self::TYPE_STRING)) {
                $typesArray = array(self::TYPE_STRING);
            } else {
                // explode types into array and avoid repeated
                $typesArray = array_unique(
                    explode('|', $uriChunkMatchGroups[3])
                );

                // Reducing other types
                // "float"  type covers "int"
                // "alphanum" type covers "alpha"
                if (in_array(self::TYPE_FLOAT, $typesArray)){
                    $intKey = array_search(self::TYPE_INT, $typesArray);
                    if ($intKey !== false) {
                        unset($typesArray[$intKey]);
                    } unset($intKey);
                }
                if (in_array(self::TYPE_ALPHANUM, $typesArray)){
                    $alphaKey = array_search(self::TYPE_ALPHA, $typesArray);
                    if ($alphaKey !== false) {
                        unset($typesArray[$alphaKey]);
                    } unset($alphaKey);
                }
            }
        } else {
            $typesArray = array(self::TYPE_STRING);
        }
        
        // parsing types into regexes
        $typesRegexArray = array();
        foreach ($typesArray as $type) {
            switch ($type) {
                case self::TYPE_INT:
                    $typesRegexArray[] = self::REGEX_INT;
                    break;
                case self::TYPE_FLOAT:
                    $typesRegexArray[] = self::REGEX_FLOAT;
                    break;
                case self::TYPE_ALPHA:
                    $typesRegexArray[] = self::REGEX_ALPHA;
                    break;
                case self::TYPE_ALPHANUM:
                    $typesRegexArray[] = self::REGEX_ALPHANUM;
                    break;
                case self::TYPE_STRING:
                    $typesRegexArray[] = self::REGEX_STRING;
                    break;
                
            }
        }
        
        // Creating the consolidated RegEx
        // 1 type   = ('type_regex')
        // 2+ types = ( ('type_regex') | ('type_regex') | ... )
        $typesRegExString = implode(')|(', $typesRegexArray);
        if (count($typesRegexArray) == 1) {
            $typesRegExString = '(' . $typesRegExString . ')';
        } else {
            $typesRegExString = '((' . $typesRegExString . '))';
        }
        
        return array(
            'uriChunk' => $uriChunkMatchGroups[0],
            'varName'  => $uriChunkMatchGroups[1],
            'varRegEx' => $typesRegExString
        );
    }
}
