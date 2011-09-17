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
 * The <kbd>Sample_Service</kbd> class shows how to use exceptions to manipulate
 * the HTTP response code and how to send a detailed message.
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @version Sample
 * @version 0.1
 * @since 0.1
 */
class Sample_Service
{
    /**
     * @Get
     */
    public function showException()
    {
        throw new Thrae_Http_Exception_NotFound(
            array(
                'errors' => array(
                    'error' => array(
                        array(
                            'element' => $this->uriVar,
                            'message' => 'element doesn\'t exist'
                        ),
                        array(
                            'variation' => $this->x,
                            'message'   => 'cannot be calculated'
                        )
                    )
                )
            )
        );
    }
    
    /**
     * @Post
     */
    public function makeAPost()
    {
        $uriParams = empty($this->uriParams)? 'empty' : $this->uriParams;
        $body      = empty($this->body)? 'empty' : $this->body;
        $myVar     = empty($this->myVar)? 'empty' : $this->myVar;
        $y         = empty($this->y)? 'empty' : $this->y;
        
        $response = array(
            'complex' => array(
                'variables' => array(
                    'myVar' => $myVar,
                    'y'     => $y
                ),
                'parameters' => $uriParams,
                'body'       => $body,
                'response'   => array(
                    'name' => 'Service'
                )
            )
        );
        
        return $response;
    }
}
