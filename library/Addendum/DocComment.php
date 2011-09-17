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
class Addendum_DocComment
{
    private static $_classes = array();
    private static $_methods = array();
    private static $_fields = array();
    private static $_parsedFiles = array();

    public static function clearCache()
    {
        self::$_classes = array();
        self::$_methods = array();
        self::$_fields = array();
        self::$_parsedFiles = array();
    }

    public function get($reflection)
    {
        if ($reflection instanceof ReflectionClass) {
            return $this->forClass($reflection);
        } elseif ($reflection instanceof ReflectionMethod) {
            return $this->forMethod($reflection);
        } elseif ($reflection instanceof ReflectionProperty) {
            return $this->forProperty($reflection);
        }
    }

    public function forClass($reflection)
    {
        $this->process($reflection->getFileName());
        $name = $reflection->getName();
        return isset(self::$_classes[$name]) ? self::$_classes[$name] : false;
    }

    public function forMethod($reflection)
    {
        $this->process($reflection->getDeclaringClass()->getFileName());
        $class = $reflection->getDeclaringClass()->getName();
        $method = $reflection->getName();
        return isset(self::$_methods[$class][$method]) ? 
                self::$_methods[$class][$method] : 
                false;
    }

    public function forProperty($reflection)
    {
        $this->process($reflection->getDeclaringClass()->getFileName());
        $class = $reflection->getDeclaringClass()->getName();
        $field = $reflection->getName();
        return isset(self::$_fields[$class][$field]) ? 
                self::$_fields[$class][$field] : 
                false;
    }

    private function process($file)
    {
        if (!isset(self::$_parsedFiles[$file])) {
            $this->parse($file);
            self::$_parsedFiles[$file] = true;
        }
    }

    protected function parse($file)
    {
        $tokens = $this->getTokens($file);
        $currentClass = false;
        $currentBlock = false;
        $max = count($tokens);
        $i = 0;
        while ($i < $max) {
            $token = $tokens[$i];
            if (is_array($token)) {
                list($code, $value) = $token;
                switch ($code) {
                    case T_DOC_COMMENT:
                        $comment = $value;
                        break;
                    case T_CLASS:
                        $class = $this->getString($tokens, $i, $max);
                        if ($comment !== false) {
                            self::$_classes[$class] = $comment;
                            $comment = false;
                        }
                        break;
                    case T_VARIABLE:
                        if ($comment !== false) {
                            $field = substr($token[1], 1);
                            self::$_fields[$class][$field] = $comment;
                            $comment = false;
                        }
                        break;
                    case T_FUNCTION:
                        if ($comment !== false) {
                            $function = $this->getString($tokens, $i, $max);
                            self::$_methods[$class][$function] = $comment;
                            $comment = false;
                        }
                        break;
                    // ignore
                    case T_WHITESPACE:
                    case T_PUBLIC:
                    case T_PROTECTED:
                    case T_PRIVATE:
                    case T_ABSTRACT:
                    case T_FINAL:
                    case T_VAR:
                        break;
                    default:
                        $comment = false;
                        break;
                }
            } else {
                $comment = false;
            }
            $i++;
        }
    }

    private function getString($tokens, &$i, $max)
    {
        do {
            $token = $tokens[$i];
            $i++;
            if (is_array($token)) {
                if ($token[0] == T_STRING) {
                    return $token[1];
                }
            }
        } while ($i <= $max);
        return false;
    }

    private function getTokens($file)
    {
        return token_get_all(file_get_contents($file));
    }
}
