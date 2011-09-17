<?php
// Copyright (C) 2010-2011 Alejandro Salazar <alphazygma@gmail.com>

/*
 * This class was taken from the Addendum PHP Reflection Annotations
 * http://code.google.com/p/addendum/
 * Copyright (C) 2006-2009 Jan "Johno Suchal <johno@jsmf.net>
 * The Class was modified to have the class naming convention proposed by Zend 
 * and refactored to be in a file of its own withing a more package oriented
 * structure, all this enables easy Autoloading.
 */

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
 * @author Johno Suchal <johno@jsmf.net> (original author)
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @since 0.4.1
 * @version 0.4.1
 */
class Addendum_Main
{
    private static $_rawMode;
    private static $_ignore;
    private static $_classnames = array();
    private static $_annotations = false;

    public static function getDocComment($reflection)
    {
        if (self::checkRawDocCommentParsingNeeded()) {
            $docComment = new Addendum_DocComment();
            return $docComment->get($reflection);
        } else {
            return $reflection->getDocComment();
        }
    }

    /** Raw mode test */
    private static function checkRawDocCommentParsingNeeded()
    {
        if (self::$_rawMode === null) {
            $reflection = new ReflectionClass('Addendum_Main');
            $method = $reflection->getMethod('checkRawDocCommentParsingNeeded');
            self::setRawMode($method->getDocComment() === false);
        }
        return self::$_rawMode;
    }

    public static function setRawMode($enabled = true)
    {
        self::$_rawMode = $enabled;
    }

    public static function resetIgnoredAnnotations()
    {
        self::$_ignore = array();
    }

    public static function ignores($class)
    {
        return isset(self::$_ignore[$class]);
    }

    public static function ignore()
    {
        foreach (func_get_args() as $class) {
            self::$_ignore[$class] = true;
        }
    }

    public static function resolveClassName($class)
    {
        if (isset(self::$_classnames[$class])) {
            return self::$_classnames[$class];
        }
        $matching = array();
        foreach (self::getDeclaredAnnotations() as $declared) {
            if ($declared == $class) {
                $matching[] = $declared;
            } else {
                $pos = strrpos($declared, "_$class");
                if ($pos !== false 
                        && ($pos + strlen($class) == strlen($declared) - 1)) {
                    $matching[] = $declared;
                }
            }
        }
        $result = null;
        switch (count($matching)) {
            case 0:
                $result = $class;
                break;
            case 1:
                $result = $matching[0];
                break;
            default:
                $errMsg = 'Cannot resolve class name for \'' . $class
                        . '\'. Possible matches: ' . join(', ', $matching);
                trigger_error($errMsg, E_USER_ERROR);
                break;
        }
        self::$_classnames[$class] = $result;
        return $result;
    }

    private static function getDeclaredAnnotations()
    {
        if (!self::$_annotations) {
            self::$_annotations = array();
            foreach (get_declared_classes() as $class) {
                if (is_subclass_of($class, 'Addendum_Annotation') 
                        || $class == 'Addendum_Annotation') {
                    self::$_annotations[] = $class;
                }
            }
        }
        return self::$_annotations;
    }

}
