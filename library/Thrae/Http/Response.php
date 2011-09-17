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
 * The <kbd>Thrae_Http_Response</kbd> class wraps the HTTP response stream of
 * a client.
 * <p>By default it sets the following response headers if not specified by
 * the client:
 * <ul>
 *   <li>CONTENT-TYPE : text/xml; charset=utf-8</li>
 *   <li>STATUS : 200</li>
 * </ul>
 * <p>This class is called <var>Thrae_Http_Response</var> for to avoid namespace
 * collisions, there is a PHP extension that provides a
 * <kbd>Thrae_Http_Request</kbd> and a <kbd>Thrae_Http_Response</kbd> classes, 
 * so there is a possibility that in the future a class named 
 * <kbd>Thrae_Http_Status</kbd> is created which will make it troublesome for
 * the user if they are using such extension and this framework.</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae/Http
 * @version 0.1
 * @since 0.1
 */
class Thrae_Http_Response
{
    /**
     * Defines the Response status.
     * @var int Default value: HTTP status OK [200]
     */
    private $_statusCode = Thrae_Http_Status::STATUS_OK;

    /**
     * Defines the Response content type
     * @var string Default value: text/xml; charset=utf-8
     */
    private $_contentType = 'text/xml; charset=utf-8';

    /**
     * Associative array that stores the headers added through the use of the
     * setHeader() method.
     * @var array
     */
    private $_headersMap = array();

    /**
     * Return the status code set for this response.
     * <p>If the status code is not set explicitly, it defaults to 200 (HTTP
     * status OK).</p>
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * Sets the status code for this response.
     * <p>If the status code is not one of the HTTP status codes declared by
     * the W3C, then the value
     * is ignored and not set.</p>
     * 
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        if (Thrae_Http_Status::getCodeMessage($statusCode) != NULL) {
            $this->_statusCode = (int)$statusCode;
        }
    }

    /**
     * Return the content-type header for this response.
     * <p>If the content type is not explicitly set, it defaults to 
     * 'text/xml; charset=utf-8'.</p>
     * 
     * @return string
     */
    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * Sets the content type for this response.
     * 
     * @todo Alejandro Salazar: add validation for know content types.
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->_contentType = $contentType;
    }

    /**
     * Adds an HTTP header to be set as part of the Response, if the name exists
     * already, the value is replaced with this new one.
     * 
     * @param string $name
     * @param mixed  $value
     */
    public function addHeader($name, $value)
    {
        // Avoiding setting the Content-Type through the headers.
        if (strpos('content-type', strtolower($name)) === FALSE) {
            $this->_headersMap[$name] = $value;
        }
    }

    /**
     * Returns a bool indicating if the named Response header has been set.
     * 
     * @param string $name
     * @return bool
     */
    public function containsHeader($name)
    {
        return array_key_exists($name, $this->_headersMap);
    }

    /**
     * Writes output to client with the base HTTP status header and content-type
     * headers.
     * <p>If the body is set to be <kbd>NULL</kbd>, an empty string is sent.</p>
     * 
     * @param string $body
     * @param int    $statusCode [optional]<br/> ignores the status header
     *      variable and uses this parameter instead)
     * @param string $contentType [optional]<br/> ignores the content-type
     *      header variable and uses this parameter instead)
     */
    public function write($body=NULL, $statusCode=NULL, $contentType=NULL)
    {
        if (!empty($body) && !is_string($body)) {
            throw new Thrae_Exception(
                'The body of the message was not a String'
            );
        }

        // Sanitizing the status code and content type to make sure not wrong
        // values are sent.
        if (isset($statusCode)) {
            $this->setStatusCode($statusCode);
        }
        if (isset($contentType)) {
            $this->setContentType($contentType);
        }

        $statusHeader = 'HTTP/1.1 ' . $this->_statusCode . ' '
                . Thrae_Http_Status::getCodeMessage($this->_statusCode);
        
        header($statusHeader);
        header('Content-Type: ' . $this->_contentType);

        foreach ($this->_headersMap as $name => $value) {
            if (strtolower($name) != 'content-length') {
                header($name . ': ' . $value);
            }
            unset($name, $value);
        } unset($name, $value);

        if (empty($body)) {
            header('Content-Length: 0');
            echo '';
        } else {
            header('Content-Length: ' . strlen($body));
            echo $body;
        }
    }

    /**
     * Simple function that returns an error to the client, a message can be
     * passed as body of the response error.
     * 
     * @param int    $code
     * @param string $message
     */
    public function sendError($code, $message=NULL)
    {
        $this->write($message, $code);
    }
}
