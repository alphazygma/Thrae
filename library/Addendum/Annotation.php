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
class Addendum_Annotation
{
    public $value;
    private static $_creationStack = array();

    public final function __construct($data = array(), $target = false)
    {
        $reflection = new ReflectionClass($this);
        $class = $reflection->getName();
        if (isset(self::$_creationStack[$class])) {
            trigger_error(
                "Circular annotation reference on '$class'", E_USER_ERROR
            );
            return;
        }
        self::$_creationStack[$class] = true;
        foreach ($data as $key => $value) {
            if ($reflection->hasProperty($key)) {
                $this->$key = $value;
            } else {
                trigger_error(
                    "Property '$key' not defined for annotation '$class'"
                );
            }
        }
        $this->checkTargetConstraints($target);
        $this->checkConstraints($target);
        unset(self::$_creationStack[$class]);
    }

    private function checkTargetConstraints($target)
    {
        $reflection = new Addendum_Reflection_Annotated_Class($this);
        if ($reflection->hasAnnotation('Target')) {
            $value = $reflection->getAnnotation('Target')->value;
            $values = is_array($value) ? $value : array($value);
            foreach ($values as $value) {
                if ($value == 'class' && $target instanceof ReflectionClass)
                    return;
                if ($value == 'method' && $target instanceof ReflectionMethod)
                    return;
                if ($value == 'property' 
                        && $target instanceof ReflectionProperty)
                    return;
                if ($value == 'nested' && $target === false)
                    return;
            }
            if ($target === false) {
                $errMsg = "Annotation '" . get_class($this) 
                        . "' nesting not allowed";
                trigger_error($errMsg, E_USER_ERROR);
                unset($errMsg);
            } else {
                $errMsg = "Annotation '" . get_class($this) 
                        . "' not allowed on " . $this->createName($target);
                trigger_error($errMsg, E_USER_ERROR);
                unset($errMsg);
            }
        }
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

    protected function checkConstraints($target)
    {
    }

}
