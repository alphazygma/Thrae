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
 * The <kbd>Thrae_Http_Exception</kbd> extends the Core Exception to support
 * setting the message a String or an Associative Array.
 * <p>Because the core <kbd>getMessage()</kbd> function cannot be overriden, the
 * <kbd>getExceptionMessage()</kbd> is provided to retrieve the custom message
 * set.</p>
 * <p>If the message set is a string, it is set on the parent constructor as
 * well and thus retrievable through either the <kbd>getMessage()</kbd> or 
 * <kbd>getExceptionMessage()</kbd> functions, if the message is an Associative
 * Array, then the parent constructor message is filled with the name of the
 * class that generated this Exception.</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae/Http
 * @version 0.1
 * @since 0.1
 */
class Thrae_Http_Exception extends Exception
{
    /** @var mixed String or Associative Array */
    protected $_exceptionMessage;
    
    /**
     * Constructor where the message can be a String or an Associative Array
     * 
     * @param mixed     $message  Array or String<br/>[optional]
     * @param int       $code     [optional]
     * @param Exception $previous [optional]
     */
    public function __construct($message=null, $code=null, $previous=null)
    {
        // If the message is a String, then pass this as is to the parent
        // constructor if an Array, then set the name of the class as the parent
        // Message
        $parentMessage = (isset($message) && is_string($message)) ? 
            $message :
            get_class($this);

        // Set the default error code if not supplied, if supplied but not an
        // integer, throw a (critical) exception
        if (is_null($code)) {
            $code = -1;
        } else if (!is_int($code)) {
            throw new Thrae_Exception('The error code was not an integer', -1);
        }
        
        parent::__construct($parentMessage, $code, $previous);

        // Verifying that only String or Array is passed as value of the Message
        if (isset($message) && !(is_string($message) || is_array($message))) {
            throw new Thrae_Exception(
                get_class($this) . ' expects String or Array message', -1
            );
        }

        $this->_exceptionMessage = $message;
    }

    /**
     * Returns the message set for this exception
     * @return mixed Array or String
     */
    public function getExceptionMessage()
    {
        return $this->_exceptionMessage;
    }
}
