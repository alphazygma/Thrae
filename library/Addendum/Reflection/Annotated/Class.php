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
class Addendum_Reflection_Annotated_Class extends ReflectionClass
{
    private $_annotations;

    public function __construct($class)
    {
        parent::__construct($class);
        $this->_annotations = $this->createAnnotationBuilder()->build($this);
    }

    public function hasAnnotation($class)
    {
        return $this->_annotations->hasAnnotation($class);
    }

    public function getAnnotation($annotation)
    {
        return $this->_annotations->getAnnotation($annotation);
    }

    public function getAnnotations()
    {
        return $this->_annotations->getAnnotations();
    }

    public function getAllAnnotations($restriction = false)
    {
        return $this->_annotations->getAllAnnotations($restriction);
    }

    public function getConstructor()
    {
        $constructor = parent::getConstructor();
        if (isset($constructor)) {
            return $this->createReflectionAnnotatedMethod($constructor);
        } else {
            return $constructor;
        }
        
    }

    public function getMethod($name)
    {
        return $this->createReflectionAnnotatedMethod(parent::getMethod($name));
    }

    public function getMethods($filter = -1)
    {
        $result = array();
        foreach (parent::getMethods($filter) as $method) {
            $result[] = $this->createReflectionAnnotatedMethod($method);
        }
        return $result;
    }

    public function getProperty($name)
    {
        return $this->createReflectionAnnotatedProperty(
            parent::getProperty($name)
        );
    }

    public function getProperties($filter = -1)
    {
        $result = array();
        foreach (parent::getProperties($filter) as $property) {
            $result[] = $this->createReflectionAnnotatedProperty($property);
        }
        return $result;
    }

    public function getInterfaces()
    {
        $result = array();
        foreach (parent::getInterfaces() as $interface) {
            $result[] = $this->createReflectionAnnotatedClass($interface);
        }
        return $result;
    }

    public function getParentClass()
    {
        $class = parent::getParentClass();
        return $this->createReflectionAnnotatedClass($class);
    }

    protected function createAnnotationBuilder()
    {
        return new Addendum_Annotation_Builder();
    }

    private function createReflectionAnnotatedClass($class)
    {
        return ($class !== false) ? 
            new Addendum_Reflection_Annotated_Class($class->getName()) :
            false;
    }

    private function createReflectionAnnotatedMethod($method)
    {
        $name = $this->getName();
        $methodName = $method->getName();
        return ($method !== null) ? 
            new Addendum_Reflection_Annotated_Method($name, $methodName) :
            null;
    }

    private function createReflectionAnnotatedProperty($property)
    {
        $name = $this->getName();
        $propertyName = $method->getName();
        return ($property !== null) ? 
            new Addendum_Reflection_Annotated_Property($name, $propertyName) :
            null;
    }
}
