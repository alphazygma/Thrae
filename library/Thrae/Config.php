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
 * The <kbd>Thrae_Config</kbd> class is based on Zend's Config and Config_Ini
 * classes but at a much simplified version, it only loads INI files.
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae
 * @version 0.1
 * @since 0.1
 */
class Thrae_Config
{
    /**
     * String that separates the parent section name
     * @var string
     */
    const SECTION_SEPARATOR = ':';
    /**
     * String that separates nesting levels of configuration data identifiers
     * @var string
     */
    const NEST_SEPARATOR = '.';

    /**
     * Contains array of configuration data
     * @var array
     */
    protected $_data;
    /**
     * This is used to track section inheritance. The keys are names of sections
     * that extend other sections, and the values are the extended sections.
     * @var array
     */
    protected $_extends = array();
    /**
     * Is null if there was no error while file loading
     * @var string
     */
    protected $_loadFileErrorStr = null;
    
    /**
     * Constructs a new <kbd>Thrae_Config</kbd> object and parses the given
     * file represented by <kbd>$filename</kbd> on the given environment 
     * <kbd>$section</kbd>.
     * 
     * @param string $fileName
     * @param string $section 
     */
    public function __construct($fileName, $section)
    {
        if (empty($fileName)) {
            throw new Thrae_Config_Exception('Filename is not set');
        }
        if (empty($section)) {
            throw new Thrae_Config_Exception('Section is not set');
        }

        $iniArray = $this->_loadIniFile($fileName);
        $dataArray = array();
        
        if (is_null($section)) {
            // Load entire file
            
            foreach ($iniArray as $sectionName => $sectionData) {
                if (!is_array($sectionData)) {
                    $dataArray = $this->_arrayMergeRecursive(
                        $dataArray, 
                        $this->_processKey(array(), $sectionName, $sectionData)
                    );
                } else {
                    $dataArray[$sectionName] = 
                        $this->_processSection($iniArray, $sectionName);
                }
            }
        } else {
            // Load one or more sections
            if (!is_array($section)) {
                $section = array($section);
            }
            
            foreach ($section as $sectionName) {
                if (!isset($iniArray[$sectionName])) {
                    throw new Thrae_Config_Exception(
                        "Section '{$sectionName}' can't be found in {$fileName}"
                    );
                }
                $dataArray = $this->_arrayMergeRecursive(
                    $this->_processSection($iniArray, $sectionName), $dataArray
                );

            }
        }
        
        $this->_data = $dataArray;
    }
    
    public function getDataArray()
    {
        return $this->_data;
    }
    
    /**
     * Handle any errors from parse_ini_file
     *
     * @param int    $errorNo
     * @param string $errorStr
     * @param string $errorFile
     * @param int    $errorLine
     */
    protected function _loadFileErrorHandler(
            $errorNo, $errorStr, $errorFile, $errorLine)
    {
        if ($this->_loadFileErrorStr === null) {
            $this->_loadFileErrorStr = $errorStr;
        } else {
            $this->_loadFileErrorStr .= (PHP_EOL . $errorStr);
        }
    }
    
    /**
     * Load the ini file and preprocess the section separator ':' in the
     * section name (that is used for section extension) so that the resultant
     * array has the correct section names and the extension information is
     * stored in a sub-key called ';extends'.
     * <p>We use ';extends' as this can never be a valid key name in an INI file
     * that has been loaded using <kbd>parse_ini_file()</kbd>.</p>
     *
     * @param  string $fileName
     * @throws Thrae_Config_Exception
     * @return array
     */
    protected function _loadIniFile($fileName)
    {
        $loadedFile = $this->_parseIniFile($fileName);
        $iniArray = array();
        
        foreach ($loadedFile as $key => $data) {
            $pieces = explode(self::SECTION_SEPARATOR, $key);
            $thisSection = trim($pieces[0]);
            
            switch (count($pieces)) {
                case 1:
                    $iniArray[$thisSection] = $data;
                    break;
                case 2:
                    $extendedSection = trim($pieces[1]);
                    $iniArray[$thisSection] = array_merge(
                        array(';extends' => $extendedSection),
                        $data
                    );
                    break;
                default:
                    throw new Thrae_Config_Exception(
                        "Section '{$thisSection}' may not extend multiple " .
                        "sections in {$fileName}"
                    );
            }
        }

        return $iniArray;
    }
    
    /**
     * Load the INI file from disk using <kbd>parse_ini_file()</kbd>.
     * <p>Use a private error handler to convert any loading errors into a 
     * <kbd>Thrae_Config_Exception</kbd></p>
     *
     * @param  string $fileName
     * @throws Thrae_Config_Exception
     * @return array
     */
    protected function _parseIniFile($fileName)
    {
        set_error_handler(array($this, '_loadFileErrorHandler'));
        
        // Warnings and errors are suppressed
        $iniArray = parse_ini_file($fileName, true);
        
        restore_error_handler();

        // Check if there was a error while loading file
        if ($this->_loadFileErrorStr !== null) {
            throw new Thrae_Config_Exception($this->_loadFileErrorStr);
        }

        return $iniArray;
    }
    
    /**
     * Process each element in the section and handle the ";extends" inheritance
     * key.
     * <p>Passes control to _processKey() to handle the nest separator
     * sub-property syntax that may be used within the key name.</p>
     *
     * @param  array  $iniArray
     * @param  string $section
     * @param  array  $config
     * @throws Thrae_Config_Exception
     * @return array
     */
    protected function _processSection($iniArray, $section, $config = array())
    {
        $thisSection = $iniArray[$section];

        foreach ($thisSection as $key => $value) {
            if (strtolower($key) == ';extends') {
                if (isset($iniArray[$value])) {
                    $this->_assertValidExtend($section, $value);

                    $config = $this->_processSection(
                        $iniArray, $value, $config
                    );
                } else {
                    throw new Thrae_Config_Exception(
                        "Parent section '{$section}' cannot be found"
                    );
                }
            } else {
                $config = $this->_processKey($config, $key, $value);
            }
        }
        return $config;
    }
    
    /**
     * Assign the key's value to the property list.
     * <p>Handles the nest separator for sub-properties.</p>
     *
     * @param  array  $config
     * @param  string $key
     * @param  string $value
     * @throws Thrae_Config_Exception
     * @return array
     */
    protected function _processKey($config, $key, $value)
    {
        if (strpos($key, self::NEST_SEPARATOR) !== false) {
            $pieces = explode(self::NEST_SEPARATOR, $key, 2);
            if (strlen($pieces[0]) && strlen($pieces[1])) {
                if (!isset($config[$pieces[0]])) {
                    if ($pieces[0] === '0' && !empty($config)) {
                        // convert the current values in $config into an array
                        $config = array($pieces[0] => $config);
                    } else {
                        $config[$pieces[0]] = array();
                    }
                } elseif (!is_array($config[$pieces[0]])) {
                    throw new Thrae_Config_Exception(
                        'Cannot create sub-key for \'' .
                        $pieces[0] .
                        '\' as key already exists'
                    );
                }
                $config[$pieces[0]] = $this->_processKey(
                    $config[$pieces[0]], $pieces[1], $value
                );
            } else {
                throw new Thrae_Config_Exception("Invalid key '{$key}'");
            }
        } else {
            $config[$key] = $value;
        }
        return $config;
    }
    
    /**
     * Throws an exception if <kbd>$extendingSection</kbd> may not extend 
     * <kbd>$extendedSection</kbd>, and tracks the section extension if it is
     * valid.
     *
     * @param  string $extendingSection
     * @param  string $extendedSection
     * @throws Thrae_Config_Exception
     * @return void
     */
    protected function _assertValidExtend($extendingSection, $extendedSection)
    {
        // detect circular section inheritance
        $extendedSectionCurrent = $extendedSection;
        while (array_key_exists($extendedSectionCurrent, $this->_extends)) {
            if ($this->_extends[$extendedSectionCurrent] == $extendingSection) {
                throw new Thrae_Config_Exception(
                    'Illegal circular inheritance detected'
                );
            }
            $extendedSectionCurrent = $this->_extends[$extendedSectionCurrent];
        }
        // remember that this section extends another section
        $this->_extends[$extendingSection] = $extendedSection;
    }
    
    /**
     * Merge two arrays recursively, overwriting keys of the same name in
     * <kbd>$firstArray</kbd> with the value in <kbd>$secondArray</kbd>.
     *
     * @param  mixed $firstArray  First array
     * @param  mixed $secondArray Second array to merge into first array
     * @return array
     */
    protected function _arrayMergeRecursive($firstArray, $secondArray)
    {
        if (is_array($firstArray) && is_array($secondArray)) {
            foreach ($secondArray as $key => $value) {
                if (isset($firstArray[$key])) {
                    $firstArray[$key] = 
                        $this->_arrayMergeRecursive($firstArray[$key], $value);
                } else {
                    if ($key === 0) {
                        $firstArray = array(
                            0=>$this->_arrayMergeRecursive($firstArray, $value)
                        );
                    } else {
                        $firstArray[$key] = $value;
                    }
                }
            }
        } else {
            $firstArray = $secondArray;
        }

        return $firstArray;
    }
}
