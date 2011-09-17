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
class Addendum_Annotation_Collection
{
    private $_annotations;

    public function __construct($annotations)
    {
        $this->_annotations = $annotations;
    }

    public function hasAnnotation($class)
    {
        $class = Addendum_Annotation_Mapper::getRealName($class);
        
        $class = Addendum_Main::resolveClassName($class);
        return isset($this->_annotations[$class]);
    }
    
    public function getAnnotation($class)
    {
        $class = Addendum_Annotation_Mapper::getRealName($class);
        
        $class = Addendum_Main::resolveClassName($class);
        return isset($this->_annotations[$class]) ?
                end($this->_annotations[$class]) : false;
    }

    public function getAnnotations()
    {
        $result = array();
        foreach ($this->_annotations as $instances) {
            $result[] = end($instances);
        }
        return $result;
    }

    public function getAllAnnotations($restriction = false)
    {
        $restriction = Addendum_Main::resolveClassName($restriction);
        $result = array();
        foreach ($this->_annotations as $class => $instances) {
            if (!$restriction || $restriction == $class) {
                $result = array_merge($result, $instances);
            }
        }
        return $result;
    }
}
