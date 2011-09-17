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
 * The <kbd>Thrae_Http_Exception_Status</kbd> class allows to define a message
 * through a String or an Associative Array and specify the HTTP status error.
 * <p>The HTTP status error codes range between 400 and 417 or 500 to 505, if
 * the status code is not set in the constructor or is outside the range, it
 * will be set to the Default 500 (INTERNAL SERVER ERROR)</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae/Http/Exception
 * @version 0.1
 * @since 0.1
 */
class Thrae_Http_Exception_Status extends Thrae_Http_Exception
{
    /**
     * Constructor of the <kbd>HttpStatusException</kbd> class.
     * <p>If the status error code is not defined, 500 (INTERNAL SERVER ERROR)
     * will be used.</p>
     * 
     * @param mixed     $message  Array or String<br/>[optional]
     * @param int       $code     [optional]
     * @param Exception $previous [optional]
     */
    public function __construct($message=null, $code=null, $previous=null)
    {
        // If status is not set or not in range, Default it to 500
        // (INTERNAL SERVER ERROR)
        if (empty($code) 
                || ( !($code >= 400 && $code <= 417) &&
                     !($code >= 500 && $code <= 505) )) {
            $code = Thrae_Http_Status::STATUS_INTERNAL_SERVER_ERROR;
        }
        
        parent::__construct($message, $code, $previous);
    }
}
