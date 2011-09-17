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
 * The <kbd>Thrae_Http_Request</kbd> class wraps the HTTP request stream of
 * a client.
 * <p>This class is called <var>ThraeHttpRequest</var> for to avoid namespace
 * collisions, there is a PHP extension that provides a <kbd>HttpRequest</kbd>
 * and a <kbd>HttpResponse</kbd> classes, so there is a possibility that in the
 * future a class named <kbd>Thrae_Http_Status</kbd> is created which will make
 * it troublesome for the user if they are using such extension and this 
 * framework.</p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @package Thrae/Http
 * @version 0.1
 * @since 0.1
 */
class Thrae_Http_Request
{
    /**
     * Associative array that will hold the headers set for this request.
     * @var array
     */
    private $_headersMap = array();

    /**
     * Associative array that holds a copy of the <kbd>$_SERVER</kbd> magic
     * variable when this object
     * is constructed.
     * @var array
     */
    private $_serverAttributes;

    /**
     * An associative array with the values of the query string sent in the
     * request or NULL if there
     * was no query string.
     * @var array
     */
    private $_uriParameters;

    /**
     * Creates a new <kbd>HttpRequest</kbd> object with the parsed values 
     * obtained from the <kbd>$_SERVER</kbd> PHP magic variable.
     * <p>Some variables read include:
     * <ul>
     *   <li>HTTP method</li>
     *   <li>service URI</li>
     *   <li>query data</li>
     *   <li>protocol</li>
     *   <li>localServer</li>
     *   <li>remoteClient</li>
     * </ul></p>
     */
    public function __construct()
    {
        $this->_serverAttributes = $_SERVER;

        // Parsing headers -----------------------------------------------------
        // Additional HTTP headers not prefixed with HTTP_ in $_SERVER
        $extraHeaders = array('CONTENT_TYPE', 'CONTENT_LENGTH');
        $prefixToRemove = array('HTTP_' , '_');

        foreach ($_SERVER as $key => $val) {
            if (strpos($key, 'HTTP_') === 0 || in_array($key, $extraHeaders)) {
                $name = str_replace($prefixToRemove, '', $key);
                $this->_headersMap[$name] = $val;
            }
        }unset($key, $val);

        unset($extraHeaders);
        unset($prefixToRemove);

        if (!empty($_REQUEST)) {
            $this->_uriParameters = $_REQUEST;
        }
    }

    /**
     * Returns a copy of the <kbd>$_SERVER</kbd> magic variable
     * @return array
     */
    public function getServerAttributes()
    {
        return $this->_serverAttributes;
    }

    /**
     * Returns the Accept header of the request, or <kbd>null</kbd> if the
     * header was not sent.
     * @return string
     */
    public function getAccept()
    {
        return $this->_getServerAttribute('HTTP_ACCEPT');
    }

    /**
     * Returns the MIME type of the body of the request, or <kbd>null</kbd> if
     * the type is not known.
     * @return string
     */
    public function getContentType()
    {
        return $this->_getServerAttribute('CONTENT_TYPE');
    }
    
    /**
     * Returns the String lenght of the body of the request, or <kbd>null</kbd>
     * if there was no body.
     * @return int
     */
    public function getContentLenght()
    {
        return $this->_getServerAttribute('CONTENT_LENGTH');
    }

    /**
     * Returns the value of the specified request header as a string.
     * @param string $name
     * @return string The value for the header if it exists, <kbd>null</kbd>
     *      otherwise.
     */
    public function getHeader($name)
    {
        return array_key_exists($name, $this->_headersMap)?
                $this->_headersMap[$name] :
                NULL;
    }

    /**
     * Returns a bool indicating if the response containse the header.
     * @param string $name
     * @return bool 
     */
    public function containsHeader($name)
    {
        return array_key_exists($name, $this->_headersMap);
    }

    /**
     * Returns all the headers sent by the request as an associative array.
     * @return array
     */
    public function getHeadersMap()
    {
        return $this->_headersMap;
    }

    /**
     * Returns an array of all the header names this request contains.
     * @return array
     */
    public function getHeaderNames()
    {
        return array_keys($this->_headersMap);
    }

    /**
     * Returns the name of the HTTP method with which this request was made.
     * <p>For example, GET, POST, PUT or DELETE.
     * @return string
     */
    public function getMethod()
    {
        return strtoupper($this->_getServerAttribute('REQUEST_METHOD'));
    }

    /**
     * Returns the part of this request's URL from the protocol name up to the
     * query string in the first line of the HTTP request.
     * @return string
     */
    public function getUri()
    {
        return trim($this->_getServerAttribute('PATH_INFO'));
    }

    /**
     * If the request had a Query String, then the values are returned here as
     * an Associative Array.
     * @return array Associative Array if Query String exists, NULL otherwise.
     */
    public function getUriParameters()
    {
        return $this->_uriParameters;
    }

    /**
     * Returns the Internet Protocol (IP) address of the client or last proxy
     * that sent the request.
     * @return string
     */
    public function getRemoteAddr()
    {
        return $this->_getServerAttribute('REMOTE_ADDR');
    }

    /**
     * Returns the Internet Protocol (IP) source port of the client or last
     * proxy that sent the request.
     * @return int
     */
    public function getRemotePort()
    {
        return (int)$this->_getServerAttribute('REMOTE_PORT');
    }

    /**
     * Validates whether the attribute exists and returns it if so.
     * @param string $attributeName
     * @return mixed 
     */
    private function _getServerAttribute($attributeName)
    {
        return isset($this->_serverAttributes[$attributeName])?
                $this->_serverAttributes[$attributeName] :
                NULL;
    }
}
