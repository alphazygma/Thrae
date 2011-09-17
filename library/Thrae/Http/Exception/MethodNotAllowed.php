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
 * The <kbd>Thrae_Http_Exception_MethodNotAllowed</kbd> class allows to define a
 * message through a String or an Associative Array.
 * <p>This exception represents an HTTP status error code of 405.</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae/Http/Exception
 * @version 0.1
 * @since 0.1
 */
class Thrae_Http_Exception_MethodNotAllowed extends Thrae_Http_Exception_Status
{
    /**
     * Constructs an Exception to represent an HTTP status error of 405.
     * 
     * @param mixed     $message  Array or String<br/>[optional]
     * @param Exception $previous [optional]
     */
    public function __construct($message = null, $previous = null)
    {
        parent::__construct(
            $message, Thrae_Http_Status::STATUS_METHOD_NOT_ALLOWED, $previous
        );
    }
}
