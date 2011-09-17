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
 * The <kbd>Mapper</kbd> ...
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @version Thrae/...
 * @version 0.1
 * @since 0.1
 */
class Addendum_Annotation_Mapper
{
    private static $_annotationMap = array(
        'Target' => 'Addendum_Annotation_Target'
    );
    
    /**
     *
     * @param string $key
     * @param string $className 
     */
    public static function addMapping($key, $className)
    {
        if (!is_string($key) || !is_string($className)) {
            throw new Addendum_Annotation_Mapper_Exception(
                'Cannot add mapping if the parameters are not strings'
            );
        }
        if ('Target' == $key) {
            throw new Addendum_Annotation_Mapper_Exception(
                'Cannot override the Target system meta-annotation'
            );
        }
        self::$_annotationMap[$key] = $className;
    }

    public static function getMapping($key)
    {
        return isset(self::$_annotationMap[$key]) ?
            self::$_annotationMap[$key] : null;
    }
    
    /**
     * Function to support the Annotation mappings
     * @param string  $class
     * @return string
     */
    public static function getRealName($class)
    {
        return array_key_exists($class, self::$_annotationMap)?
                self::$_annotationMap[$class] : $class;
        
    }
}
