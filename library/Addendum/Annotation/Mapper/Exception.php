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

/*
 * This class was added to the Addendum framework, due to the nature of name
 * spaces, annotation tags that match a class can sometimes be inconvenient,
 * so, the <kbd>Addendum_Annotation_Mapper</kbd> adds a static function
 * that allows to add mappings to simplify Annotation tags.
 * <p>However, there are a few tags that cannot be mapped which are part of the 
 * Addendum framework, if such tags are tried to be mapped, this exception is
 * risen.</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @since 0.4.1
 * @version 0.4.1
 */
class Addendum_Annotation_Mapper_Exception extends Exception
{
    public function __construct($message = null, $code = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
