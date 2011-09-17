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
class Addendum_Annotation_Builder
{
    private static $_cache = array();
    
    public function build($targetReflection)
    {
        
        $data = $this->parse($targetReflection);
        $annotations = array();
        foreach ($data as $class => $parameters) {
            $class = Addendum_Annotation_Mapper::getRealName($class);
            
            foreach ($parameters as $params) {
                $annotation = $this->instantiateAnnotation(
                    $class, $params, $targetReflection
                );
                if ($annotation !== false) {
                    $annotations[get_class($annotation)][] = $annotation;
                }
            }
        }
        return new Addendum_Annotation_Collection($annotations);
    }

    public function instantiateAnnotation(
            $class, $parameters, $targetReflection = false)
    {
        $class = Addendum_Main::resolveClassName($class);
        if (is_subclass_of($class, 'Addendum_Annotation') 
                && !Addendum_Main::ignores($class) 
                || $class == 'Addendum_Annotation') {
            $annotationReflection = new ReflectionClass($class);
            return $annotationReflection->newInstance(
                $parameters, $targetReflection
            );
        }
        return false;
    }

    private function parse($reflection)
    {
        $key = $this->createName($reflection);
        if (!isset(self::$_cache[$key])) {
            $parser = new Addendum_AnnotationsMatcher();
            $parser->matches($this->getDocComment($reflection), $data);
            self::$_cache[$key] = $data;
        }
        return self::$_cache[$key];
    }

    private function createName($target)
    {
        if ($target instanceof ReflectionMethod) {
            return $target->getDeclaringClass()->getName() 
                    . '::' . $target->getName();
        } elseif ($target instanceof ReflectionProperty) {
            return $target->getDeclaringClass()->getName() 
                    . '::$' . $target->getName();
        } else {
            return $target->getName();
        }
    }

    protected function getDocComment($reflection)
    {
        return Addendum_Main::getDocComment($reflection);
    }

    public static function clearCache()
    {
        self::$_cache = array();
    }

}
