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
 * The <kbd>Sample_ComplexServiceB</kbd> shows how to serve the four HTTP
 * request methods, and how to read the body and variables parsed from the URI.
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @version Sample
 * @version 0.1
 * @since 0.1
 */
class Sample_ComplexServiceB
{
    /**
     * @Post
     */
    public function doPost()
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
                    'name' => 'ComplexService',
                    'type' => 'B'
                )
            )
        );
        
        return $response;
    }

    /**
     * @Get
     */
    public function doGet()
    {
        throw new Exception('Unexpected exception');
    }
    
    /**
     * @Put
     */
    public function doPut()
    {
        return 'Called the PUT method';
    }
    
    /**
     * @Delete
     */
    public function doDelete()
    {
        return array('response'=>'called the DELETE method');
    }
}