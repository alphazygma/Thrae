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
 * The <kbd>Thrae_Parser_Xml</kbd> class wraps the functionality of the native 
 * <kbd>Simple XML</kbd> functions.
 * <p>It adds functions to facilitate validation or correct parsing of String
 * messages or Associative Arrays.</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @version Thrae/Parser
 * @version 0.1
 * @since 0.1
 */
class Thrae_Parser_Xml
{
    /**
     * Validates if the String is XML parsable.
     * 
     * @param  string  $xmlString
     * @return boolean
     */
    public static function validateString($xmlString)
    {
        // Turning the use of Internal error handling ON to avoid Exceptions
        $originalUserErrorValue = libxml_use_internal_errors(TRUE);

        $isValidXml = TRUE;

        // Silencing the xml parsing to avoid Warnings
        @simplexml_load_string($xmlString);

        $errors = libxml_get_errors();
        if (!empty($errors)) {
            $isValidXml = FALSE;
        }

        // Resetting the Internal error handling to the original value
        libxml_use_internal_errors($originalUserErrorValue);

        return $isValidXml;
    }
    
    /**
     * Parses a String or an Associative Array into a XML string.
     * 
     * @param  mixed $data A String or an Associative Array
     * @throws Thrae_Parser_Exception if data is not of the supported types
     * @return string
     */
    public static function toXml($data, $rootNodeName=null, $prettyFormat=false)
    {
        if (!empty($rootNodeName)) {
            if (!is_string($rootNodeName)) {
                throw new Thrae_Parser_Exception(
                    'Root node name was not a string'
                );
            }
        } else {
            $rootNodeName = 'root';
        }
        
        $xml = NULL;
        if (empty($data)) {
            $xml = @simplexml_load_string(
                "<?xml version='1.0' encoding='utf-8'?><{$rootNodeName} />"
            );
        } else if (is_string($data)) {
            $xml = @simplexml_load_string(
                '<?xml version=\'1.0\' encoding=\'utf-8\'?>' .
                "<{$rootNodeName}>{$data}</{$rootNodeName}>"
            );
        } else if (is_array($data)) {
            if (count($data) == 1) {
                $key = array_keys($data);
                $key = $key[0];
                $value = $data[$key];
                if (is_string($value)) {
                    $xml = @simplexml_load_string(
                        '<?xml version="1.0" encoding="utf-8"?>' .
                        "<{$key}>{$value}</{$key}>"
                    );
                } else {
                    $xml = self::_toXML($value, $key);
                }
            } else {
                $xml = self::_toXML($data, $rootNodeName);
            }
        } else {
            throw new Thrae_Parser_Exception(
                'Parse data was not a String or Array'
            );
        }
        
        if ($prettyFormat) {
            $dom = dom_import_simplexml($xml)->ownerDocument;
            $dom->formatOutput = true;
            return $dom->saveXML();
        } else {
            return $xml->asXML();
        }
    }
    
    /**
     * Converts a <kbd>SimpleXMLElement</kbd> object or a well-formed SML string
     * into an associative array.
     * @param  mixed $xmlObj <kbd>SimpleXMLElement</kbd> or string
     * @throws Thrae_Parser_Exception if data is not of the supported types
     * @return array
     */
    public static function toArray($xml)
    {
        if (is_string($xml)) {
            $xml = new SimpleXMLElement($xml);
        }
        
        if (!($xml instanceof SimpleXMLElement)) {
            throw new Thrae_Parser_Exception(
                'Parse data was not a String or SimpleXMLElement'
            );
        }
        
        $resultArray = array();
        self::_convertXmlObj2Array($xml, $resultArray);

        return $resultArray;
    }
    
    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recursively loops through and
     * builds up an XML document.
     *
     * @param  array  $data
     * @param  string $rootNodeName The Name for the root node.
     * @param  SimpleXMLElement $xml Used for recursion when building
     * @return SimpleXMLElement
     */
    private static function _toXML($data, $rootNodeName, &$xml=null)
    {
        // If it is the first call to the function, $xml is null so we need to
        // create the root node
        if (is_null($xml)) {
            $xml = @simplexml_load_string(
                "<?xml version='1.0' encoding='utf-8'?><{$rootNodeName} />"
            );
        }
        
        // loop through the data passed in.
        foreach ($data as $key => $value) {
            // no numeric keys in our xml please!
            // otherwise they will be understood as an array of the same node
            if (is_numeric($key)) {
                $numeric = 1;
                $key = $rootNodeName;
            }
            // delete any char not allowed in XML element names
            $key = preg_replace('/[^a-z0-9\-\_\.\:]/i', '', $key);

            // if there is another array found, recrusively call this function
            if (is_array($value)) {
                $node = NULL;
                // if the key was numeric, add support for XML elements with the
                // same name, e.g.:
                // <root>
                //    <myNode>x</myNode>
                //    <myNode>y</myNode>
                // </root>
                if (self::_isAssoc($value) || !empty($numeric)) {
                    $node = $xml->addChild($key);
                } else {
                    $node = $xml;
                }

                // recrusive call.
                self::_toXml($value, $key, $node);
            } else {
                // add single node.
                $value = htmlentities($value);
                $xml->addChild($key, $value);
            }
        }
        
        return $xml;
    }
    
    /**
     * Returns <kbd>true</kbd> if the given value is an associative array and
     * <kbd>false</kbd> if it is a simple array where all the keys are numeric
     * and sequential starting from 0.
     * <p>This method only checks the first level of the array.</p>
     * <p>A simple array is one where the user just add elements without
     * explicitly declaring any keys, example:<br/>
     * <code>array(1, 'hello', true, 59)</code>
     * <br/>
     * Which internally <b>PHP</b> will create an array that looks like:<br/>
     * <code>array(
     *    0 => 1,
     *    1 => 'hello',
     *    2 => true,
     *    3 => 59
     * )</code>
     * 
     * @param  array   $array
     * @return boolean 
     */
    private static function _isAssoc($array)
    {
        if (is_array($array) && !empty($array)) {
            $i = 0;
            foreach ($array as $key => $val) {
                // The first key that doesn't match the sequence, indicates that
                // it is an associative array
                if ($key !== $i) {
                    return true;
                }   
                $i++;
            }   
        }   
        return false;
    }

    /**
     * Converts a SimpleXMLElement object into a compatible associative array.
     * <p>This method is recursive so it modifies the $array parameter passed.
     * </p>
     * 
     * @param SimpleXMLElement $xmlObj
     * @param array $arr (by reference)
     */
    private static function _convertXmlObj2Array($xmlObj, &$arr)
    {
        $nodeName = $xmlObj->getName();
        $children = $xmlObj->children();

        if (count($children) > 0) {
            // Variable used to know if a Child of this node has siblings of the
            // same name and thus the named array for the Child has been 
            // converted into a subarray of sibilings identified by the same
            // name.
            // In otherwords, if we have an XML like
            //     <parent><child>value1</child><child>value2</child><parent>
            // We end up with
            //     $xmlObj = array(
            //         'parent' => array(
            //              'child' => array(
            //                  [0] => 'value1',
            //                  [1] => 'value2',
            //               )
            //          )
            //     );
            $convertedChildren = array();

            $arr[$nodeName] = array();

            foreach ($children as $elementName => $node) {
                //--------------------------------------------------------------
                // Here we are verifying if the current node read is a sibiling
                // of a previous one (meaning, have the same node name), so we
                // convert it into an array of sibilings
                $addAsSubArray = FALSE;
                if (isset($arr[$nodeName][$elementName])) {
                    // Now that we know this node is a sibiling of the same
                    // name, verify if the containing array has been converted,
                    // if not, do so:
                    // Changes it from
                    //     array( 'childName' => 'value' );
                    // into
                    //     array( 'childName' => array(
                    //                 [0] => 'value'
                    //          ) );
                    if (!key_exists($elementName, $convertedChildren)) {
                        $subArr = $arr[$nodeName][$elementName];
                        $arr[$nodeName][$elementName] = array();
                        $arr[$nodeName][$elementName][] = $subArr;
                        unset( $subArr );

                        $convertedChildren[$elementName] = 1;
                    }
                    $addAsSubArray = TRUE;
                }
                //--------------------------------------------------------------

                // if the node was identified as a sibiling of another with the
                // same name then add this node as a sub-array so that it 
                // doesn't override it's sibiling
                if ($addAsSubArray) {
                    $subArr = array();
                    self::_convertXmlObj2Array($node, $subArr);
                    $arr[$nodeName][$elementName][] = $subArr[$elementName];
                    unset($subArr);
                } else {
                    self::_convertXmlObj2Array($node, $arr[$nodeName]);
                }
            }
            //UNTESTED CODE, something similar may work to read the attributes
            //of the XML nodes
            //$arr[$nextIdx]['@attributes'] = array();
            //$attributes = $node->attributes();
            //foreach ($attributes as $attributeName => $attributeValue)
            //{
            //    $attribName = strtolower(trim((string)$attributeName));
            //    $attribVal = trim((string)$attributeValue);
            //    $arr[$nextIdx]['@attributes'][$attribName] = $attribVal;
            //}
        } else {
            $text = (string)$xmlObj;
            $text = trim($text);
            $arr[$nodeName] = (strlen($text) > 0)? $text : null;
        }
    }
}
