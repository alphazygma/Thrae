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
 * The <kbd>Thrae_Exception</kbd> class represents the problems with the
 * application that cannot let the application run appropriately 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae
 * @version 0.1
 * @since 0.1
 */
class Thrae_Exception extends Exception
{
    /**
     * Constructor of the most generic Thrae exception.
     * 
     * @param string    $message  [optional]
     * @param int       $code     [optional]
     * @param Exception $previous [optional]
     */
    public function __construct($message = null, $code = null, $previous = null)
    {
        if (is_null($code)) {
            $code = -1;
        } else if (!is_int($code)) {
            throw new self(
                'The error code was not an integer', -1, new self($message)
            );
        }
        parent::__construct($message, $code, $previous);
    }
}
