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
 * The <kbd>Thrae_Autoloader</kbd> is based on the Zend Autloader but at much
 * simpler level.
 * <p>It implments the Framework Interop Group reference:<br/>
 * http://groups.google.com/group/php-standards/web/psr-0-final-proposal<br/>
 * And adds Zends security check for illegal characters in the class name.
 * </p>
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae
 * @version 0.1
 * @since 0.1
 */
class Thrae_Autoloader
{
    /**
     * Loas a class by parsing the classname into a path to the file.
     * <p>This autoloader is based on the Zend Autoloader classes, but at a much
     * simpler version.
     * </p>
     * <p>The file must be in the "$class.php" format.
     * </p>
     * <p>The class name will be split at underscores to generate the path
     * hierarchy.<br/>
     * e.g. "My_Sample_Class" will map to "My/Sample/Class.php"
     * </p>
     * @param string $className
     * @throws Thrae_Exception
     */
    public static function classLoad($className)
    {
        if (class_exists($className, false) 
                || interface_exists($className, false)) {
            return;
        }
        
        // Autodiscover the path from the class name
        // Implementation is PHP namespace-aware, and based on
        // Framework Interop Group reference implementation:
        // http://groups.google.com/group/php-standards/web/psr-0-final-proposal
        $className = ltrim($className, '\\');
        $fileName  = '';
        $namespace = '';
        $lastNsPos = strripos($className, '\\');
        if ($lastNsPos) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace)
                    . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        self::_illegalCharacterCheck($fileName);
        
        // Using `include_once' instead of require_once to be able to throw 
        // Thrae's own exception instead of automatically dying out of the
        // default behavior of `require_once'
        @include_once $fileName;
        
        if (!class_exists($className, false) 
                && !interface_exists($className, false)) {
            throw new Thrae_Autoloader_Exception(
                "File ({$fileName}) does not exist or " .
                "class ({$className}) was not found in the file"
            );
        }
    }
    
    /**
     * Checking that the filename does not contain illegal characters that could
     * lead to exploits.
     * @param string $fileName 
     * @throws Thrae_Exception
     */
    protected static function _illegalCharacterCheck($fileName)
    {
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $fileName)) {
            throw new Thrae_Autoloader_Exception(
                "Illegal character(s) in filename '{$fileName}'"
            );
        }
    }
}
