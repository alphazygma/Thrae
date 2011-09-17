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
 * The <kbd>Thrae_Application</kbd> class is the one responsible to handle
 * Webservices requests.
 * <p>Thrae is the Draconic word for Air.</p>
 * <p>This class takes care of the following actions:
 * <ul>
 * <li>Validating the request
 *    <ul>
 *    <li>ACCEPT header if present, is either json or xml</li>
 *    <li>Checking that the service URI requested exist</li>
 *    <li>Checking that the Request method is supported for the service</li>
 *    </ul>
 * </li>
 * <li>Parsing the values from the service URI to create an instance of the new
 *     service and pass them as parameters of the constructor.
 * </li>
 * <li>Search for the public method that implements the Request Method and
 *     invoke it
 *    <ul>
 *    <li>If the Request Method is PUT or POST, then the Request body is passed
 *        as parameter.
 *    <li>
 *    <li>If the Request Method is other than PUT or POST, then the function is
 *        invoked with no parameter
 *    </li>
 *    </ul>
 * </li>
 * <li>Obtain the response from the serving class function, which can be among a
 *     <kbd>null</kbd> value, a String or an Associative array and then return
 *     the response in either XML or JSON format as requested by the specified 
 *     <kbd>HTTP ACCEPT</kbd> header, if this header is not sent XML is assumed.
 * </li>
 * </ul>
 * </p>
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @version 0.1
 * @package Thrae
 * @since 0.1
 * @link http://code.google.com/p/addendum/
 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
 * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
 */
class Thrae_Application
{
    const CONTENT_TYPE_JSON = 'json';
    const CONTENT_TYPE_XML  = 'xml';
    
    private $_xmlContentTypes = array(
        'application/xml',
        'text/xml'
    );
    private $_jsonContentTypes = array(
        'text/x-json',
        'text/json',
        'application/json'
    );
    
    /** @var Thrae_Http_Request */
    private $_request;
    /** @var Thrae_Http_Response */
    private $_response;
    /** @var boolean */
    private $_isLogging = false;
    /** @var string If $_isLogging is true, an id for this session logs is
     *      stored here */
    private $_sessLogId;
    /** @var boolean */
    private $_displayErrors = false;
    /** @var boolean */
    private $_xmlPrettyFormat = false;
    /** @var string */
    private $_requestMethod = null;
    /** @var string */
    private $_requestContentType = null;
    /** @var string */
    private $_responseContentType = null;
    
    /** @var array Will contain the mappings for URI to service class and 
     *      parsing of variables in URI */
    private $_serviceDefinitions;
    
    
    /**
     * Initialize application. Potentially initializes include_paths, PHP
     * settings, and bootstrap class.
     *
     * @param  string $environment
     * @param  string $configPath String path to configuration file
     * @throws Thrae_Exception If $configPath is not set or is not a string
     * @return void
     */
    public function __construct($environment, $configPath)
    {
        try {
            require_once 'Thrae/Autoloader.php';
            // Adding the static function of the Thrae_Autloader class to the
            // native autoloading stack
            spl_autoload_register('Thrae_Autoloader::classLoad');

            if (is_null($environment || !is_string($environment))) {
                throw new Thrae_Exception("Environment is not set");
            }

            if (is_null($configPath) || !is_string($configPath)) {
                throw new Thrae_Exception("Configuration path is not set");
            }

            $config = new Thrae_Config($configPath, $environment);
            $config = $config->getDataArray();
            $this->_setOptions($config);

            $this->_setAnnotationMappings();
            
            $this->_loadServices($config, $environment);

            $this->_log('Parsing HTTP Request and Response objects');
            $this->_request = new Thrae_Http_Request();
            $this->_response = new Thrae_Http_Response();
        } catch (Exception $e) {
            $this->_handleDisplayError($e);
            throw $e;
        }
    }
    
    /**
     * Delegates dispatch and abstracts error handling to make cleaner the
     * actual dispatch code.
     */
    public function dispatch()
    {
        try {
            $this->_dispatch();
        } catch (Exception $e) {
            $this->_handleError($e);
        }
    }
    
    /**
     * Set application options and add include paths and/or set php settings.
     *
     * @param array $config
     */
    private function _setOptions($config)
    {
        $config = array_change_key_case($config, CASE_LOWER);
        
        if (!empty($config['phpsettings'])) {
            $this->_setPhpSettings($config['phpsettings']);
        }
        
        if (!empty($config['includepaths'])) {
            $this->_setIncludePaths($config['includepaths']);
        }
        
        if (!empty($config['settings'])) { 
            if (isset($config['settings']['logging'])) {
                $this->_isLogging = (true == $config['settings']['logging']);
                if ($this->_isLogging) {
                    $this->_sessLogId = 
                        '[' . substr(md5(microtime()), 0, 7) . '] ';
                }
            }
            if (isset($config['settings']['displayErrors'])) {
                $this->_displayErrors =
                    true == $config['settings']['displayErrors'];
            }
            if (isset($config['settings']['xml']['prettyFormat'])) {
                $this->_xmlPrettyFormat =
                    true == $config['settings']['xml']['prettyFormat'];
            }
        }
    }
    
    /**
     * Sets shortcut for annotation names, so services only have to Annotate
     * the HTTP method they serve instead of the full class name that maps to
     * such HTTP method
     */
    private function _setAnnotationMappings()
    {
        Addendum_Annotation_Mapper::addMapping(
            'Get', 'Thrae_Http_Request_Annotation_Get'
        );
        Addendum_Annotation_Mapper::addMapping(
            'Post', 'Thrae_Http_Request_Annotation_Post'
        );
        Addendum_Annotation_Mapper::addMapping(
            'Put', 'Thrae_Http_Request_Annotation_Put'
        );
        Addendum_Annotation_Mapper::addMapping(
            'Delete', 'Thrae_Http_Request_Annotation_Delete'
        );
        Addendum_Annotation_Mapper::addMapping(
            'Options', 'Thrae_Http_Request_Annotation_Options'
        );
    }
    
    /**
     * Sets the path to allow the REST service classes to be found when
     * autoloaded, and parses the file that contains the URI mappings.
     * 
     * @param string $config 
     * @param string $environment
     */
    private function _loadServices($config, $environment)
    {
        $this->_log('Adding the services directory to the path');
        if (!empty($config['settings']['services']['path'])) {
            $path = $config['settings']['services']['path'];
            set_include_path(get_include_path() . PATH_SEPARATOR . $path);
        } else {
            throw new Thrae_Exception(
                'Services path option not defined, make sure ' .
                '"settings.services.path" is defined in the configuarion file.'
            );
        }
        
        $this->_log('Parsing the services mappings');
        if (!empty($config['settings']['services']['mappings'])) {
            // Parsing all the services defined in the configuration mappings
            $servicesConfig = new Thrae_Config_Services(
                $config['settings']['services']['mappings'], $environment
            );
            
            $services = $servicesConfig->getDataArray();
            $this->_serviceDefinitions = $services['service'];
        } else {
            throw new Thrae_Exception(
                'Services configuration option not defined, make sure ' .
                '"settings.services.mappings" is defined in the configuarion' .
                'file'
            );
        }
    }
    
    /**
     * Set PHP configuration settings
     *
     * @param array $settings
     */
    private function _setPhpSettings($settings, $prefix = '')
    {
        foreach ($settings as $key => $value) {
            $key = empty($prefix) ? $key : $prefix . $key;
            
            if (is_scalar($value)) {
                ini_set($key, $value);
            } else if (is_array($value)) {
                $this->_setPhpSettings($value, $key . '.');
            }
        }
    }
    
    /**
     * Set include path
     *
     * @param  array $paths
     * @return Thrae_Application
     */
    private function _setIncludePaths($paths)
    {
        $path = implode(PATH_SEPARATOR, $paths);
        set_include_path(get_include_path() . PATH_SEPARATOR . $path);
    }
    
    /**
     * Based on the URI, it looks for a matching service and delegates the
     * processing to serve the request.
     */
    private function _dispatch()
    {
        $requestUri = $this->_request->getUri();
        $this->_log("Getting URI: {$requestUri}");
        
        if (empty($requestUri) || $requestUri == "/") {
            throw new Thrae_Http_Exception_BadRequest(
                "No service requested, uri({$requestUri})"
            );
        } else {
            $this->_log('Looking for service matches');
            $serviceDef = $this->_matchForService($requestUri);
            
            if (isset($serviceDef)) {
                $this->_log('Match found, serving request');
                $this->_serve($serviceDef, $requestUri);
            } else {
                $err = "No service to match the requested uri ({$requestUri})";
                $this->_log($err);
                throw new Thrae_Http_Exception_BadRequest($err);
            }
        }
    }
    
    /**
     * Looks for a service definition that matches the requested URI.
     * <p>If there is a match, the Service definition array is returned,
     * otherwise <kbd>null</kbd> is returned.</p>
     * 
     * @param  string $requestUri
     * @return array 
     * @throws Thrae_Http_Exception_InternalServerError if the URI is unparsable
     */
    private function _matchForService($requestUri)
    {
        $matchedServiceDefinition = null;
        
        foreach ($this->_serviceDefinitions as $serviceDef) {
            $this->_log("Matching against: {$serviceDef['uriParser']}");
            $matchNum = preg_match(
                '#^' . $serviceDef['uriParser'] . '$#', $requestUri
            );

            if ($matchNum === false) {
                throw new Thrae_Http_Exception_InternalServerError(
                    'The uri is unparsable, may contain unsupported characters'
                );
            } else if ($matchNum == 1) {
                $matchedServiceDefinition = $serviceDef;
                break;
            }
        }
        return $matchedServiceDefinition;
    }
    
    /**
     * The central part of the application, where the request is served.
     * <p>All request headers are obtained, and the request is served
     * accordingly.</p>
     * 
     * @param array $serviceDef Array with information on how to parse the URI
     * @param string $requestUri String with the URI to parse
     */
    private function _serve($serviceDef, $requestUri)
    {
        $this->_requestMethod = $this->_getSanitizedRequestMethod(
            $this->_request->getMethod()
        );
        $this->_requestContentType = $this->_getSanitizedContentType(
            $this->_request->getContentType()
        );
        
        // If the accept header is not sent by the client, this sanitize method
        // uses the content type of the request to respond in the same format.
        $this->_responseContentType = $this->_getSanitizedAcceptHeader(
            $this->_request->getAccept()
        );

        $matches = null;
        $this->_log('Parsing for variables in URI');
        // Parsing URI to obtain possible variables
        preg_match(
            '#^' . $serviceDef['varParser'] . '$#',
            $requestUri,
            $matches
        );
        
        // Building an array that contains the variable names and their values
        $variables = array();
        // Starting from 1, since match 0 is the match for the whole pattern.
        for ($i = 1; $i < count($matches); $i++) {
            $variables[] = array(
                'varName'  => $serviceDef['varNames'][$i-1],
                'varValue' => $matches[$i]
            );
        }
        unset($matches);

        $this->_log('Reflecting on Class that serves the request');
        $clazz = new Addendum_Reflection_Annotated_Class(
            $serviceDef['className']
        );
        
        $clazzMethodList = $clazz->getMethods();
        
        // If it happens to be the OPTIONS http request method, then collect the
        // methods supported by the class and send response with options to the
        // client, then "return" to finish the request process
        if ($this->_requestMethod == 'Options') {
            $this->_log('Creating OPTIONS message');
            $this->_returnHttpMethodOptions($clazzMethodList);
            return;
        }
        
        // Checking the public functions declared in the service for one Tagged
        // with the HTTP request method
        $methodToInvoke = null;
        
        // Looking for a class method that contains a phpDoc comment annotation
        // matching the request method
        $this->_log("Looking for method for '{$this->_requestMethod}' request");
        foreach ($clazzMethodList as &$clazzMethod) {
            if ($clazzMethod->hasAnnotation($this->_requestMethod)) {
                $methodToInvoke = $clazzMethod;
                break;
            }
        }unset($clazzMethod);
        
        if (is_null($methodToInvoke)) {
            throw new Thrae_Http_Exception_MethodNotAllowed(
                'The requested method is not supported'
            );
        }

        // Now that we know there is a method in the class to serve the request,
        // we create an instance of the class
        $this->_log("Creating new instance of '{$clazz->getName()}'");
        $clazzInstance = $clazz->newInstanceArgs();
        
        // We inject the public variables into the class' object for the URI
        // parameters, and we set the $body variable to empty so regardless of
        // the request method, the variable will always be available.
        $this->_log('Injecting URI parameters and body');
        $clazzInstance->uriParams = $this->_request->getUriParameters();
        $clazzInstance->body      = null;

        // If there were any variables parsed, inject them into the instance.
        $this->_log('Injecting parsed variables');
        foreach ($variables as $varPair) {
            $this->_log("  {$varPair['varName']} = {$varPair['varValue']}");
            
            // Very important, notice the "$" after the "$clazzInstance->", this
            // is what allows us to inject a variable with the name that was
            // provided by the developer in the services configuration file.
            $clazzInstance->$varPair['varName'] = $varPair['varValue'];
        }
        
        // Only Post and Put HTTP methods have a body, so if any was the
        // requested method, set up the body variable
        if (in_array($this->_requestMethod, array('Post', 'Put'))) {
            $this->_log("Injecting body for '{$this->_requestMethod}' request");
            $clazzInstance->body = $this->_getRequestBody();
        }
        
        // Executing the method on the 
        $this->_log("Executing method '{$methodToInvoke->getName()}'");
        $serviceResult = $methodToInvoke->invoke($clazzInstance);
        
        $this->_log('Parsing method response');
        $formattedResponse = $this->_parseResponse($serviceResult);
        
        // _logResponse is pretty much equivalent to _log, just added to avoid
        // doing a double parsing of the result with forcing NO pretty printing
        // if Logging is not required, most useful for a PROD environment.
        $this->_logResponse($serviceResult);
        
        // Sending response to the client
        $this->_response->write(
            $formattedResponse, 
            Thrae_Http_Status::STATUS_OK, 
            $this->_getWriteResponseContentType()
        );
    }
    
    /**
     * Based on a Reflected Class method list, it checks which HTTP methods can
     * be served and responds to the client with such.
     * 
     * @param array $clazzMethodList 
     */
    private function _returnHttpMethodOptions($clazzMethodList)
    {
        $httpMethodList = array('Get', 'Post', 'Put', 'Delete');
        $options = array();

        foreach ($clazzMethodList as $clazzMethod) {
            foreach ($httpMethodList as $httpMethod){
                $annotation = $clazzMethod->getAnnotation($httpMethod);
                if ($annotation !== false) {
                    $options[$httpMethod] = 1;
                }
            }
        }
        
        if (count($options) == 0) {
            $options = array('optionList'=> array());
        } else {
            $options = array_keys($options);
            // Lowercase all the http methods found
            for ($i = 0; $i < count($options); $i++) {
                $options[$i] = strtolower($options[$i]);
            }

            $options = array(
                'optionList'=> array(
                    'option' => $options
                )
            );
        }
        
        $formattedResponse = $this->_parseResponse($options);
        $this->_logResponse($options);
        
        $this->_response->write(
            $formattedResponse, 
            Thrae_Http_Status::STATUS_OK, 
            $this->_getWriteResponseContentType()
        );
    }
    
    /**
     * Parses the Request input based on the Content-Type sent and returns an
     * array of it's value.
     * <p>If the body is empty, an empty array is returned.</p>
     * 
     * @return array
     * @throws Thrae_Http_Exception_BadRequest If the body sent could not be 
     *      parsed on the specified Content-Type
     */
    private function _getRequestBody()
    {
        $body = '';
        $contentLength = $this->_request->getContentLenght();
        if (isset($contentLength) && $contentLength > 0) {
            $httpContent = fopen('php://input', 'r');
            while ($data = fread($httpContent, $contentLength)) {
                $body .= $data;
            }
            fclose($httpContent);
        }

        if (empty($body)) {
            return array();
        }

        if (self::CONTENT_TYPE_XML == $this->_requestContentType) {
            if (Thrae_Parser_Xml::validateString($body)) {
                return @Thrae_Parser_Xml::toArray($body);
            } else {
                error_log('XML sent could not be parsed - ' . $body);
                throw new Thrae_Http_Exception_BadRequest(
                    'XML sent could not be parsed'
                );
            }
        } else { // ( self::CONTENT_TYPE_JSON == $this->_requestContentType )
            if (Thrae_Parser_Json::validateString($body)) {
                return Thrae_Parser_Json::toArray($body);
            } else {
                error_log('JSON sent could not be parsed - ' . $body);
                throw new Thrae_Http_Exception_BadRequest(
                    'JSON sent could not be parsed'
                );
            }
        }
    }
    
    /**
     * Parses the Web Service response in the format requested by the client.
     * <p>The response data returned from our service can be either a String or
     * an Associative Array.
     * </p>
     * <p>The Media-Type is defined by the client through the HTTP Accept header
     * and is represented by this class constants when it was parsed:
     * <ul>
     *   <li>CONTENT_TYPE_JSON</li>
     *   <li>CONTENT_TYPE_XML</li>
     * </ul>
     * </p>
     * 
     * @param mixed   $responseData String or Associative Array
     * @param boolean $ignorePrettyFormat Used mostly for logging purposes
     * @return string
     * @see ::CONTENT_TYPE_JSON
     * @see ::CONTENT_TYPE_XML
     */
    private function _parseResponse($responseData, $ignorePrettyFormat=false)
    {
        if (empty($responseData)) {
            return '';
        }

        $responseMsg = NULL;
        if (self::CONTENT_TYPE_JSON == $this->_responseContentType) {
            $responseMsg = Thrae_Parser_Json::toJson($responseData);
        } else { // if (self::CONTENT_TYPE_XML == $this->_responseContentType)
            $responseMsg = Thrae_Parser_Xml::toXml(
                $responseData, 
                null, 
                $ignorePrettyFormat ? false: $this->_xmlPrettyFormat
            );
        }

        return $responseMsg;
    }
    
    /**
     * Returns the response content type based on what the client requested.
     * <p>If the client didn't send the Accept header, then the response content
     * type is set to be accordingly to how the reponsed was parsed.</p>
     * 
     * @return String 
     */
    private function _getWriteResponseContentType()
    {
        $clientAcceptHeader = $this->_request->getAccept();
        if (empty($clientAcceptHeader)) {
            switch($this->_responseContentType) {
                case self::CONTENT_TYPE_XML:
                    return 'text/xml';
                case self::CONTENT_TYPE_JSON:
                    return 'text/json';
            }
        } else {
            return $clientAcceptHeader;
        }
    }
    
    /**
     * Depending on the Exception family type, it parses the exception or let
     * it fall through.
     * <p>If the exception is from the <kbd>Thrae_Http_Exception_Status</kbd>
     * family, it parses the error message into a JSON or XML and sets the HTTP
     * code to the one defined by the exception.</p>
     * <p>If the exception is from the <kbd>Thrae_Exception</kbd> family, it is
     * considered critical and it is let to fall through</p>
     * <p>Any other exception is treated as an Internal Server Error exception
     * and handled similarily to the <kbd>Thrae_Http_Exception_Status</kbd>
     * exceptions</p>
     * 
     * @param Exception $exception 
     */
    private function _handleError(Exception $exception)
    {
        // If it is an status exception, format it to XML or JSON response with
        // the appropriate HTTP status header
        if ($exception instanceof Thrae_Http_Exception_Status) {
            $code   = $exception->getCode();
            $errMsg = $exception->getExceptionMessage();
            
            if (isset($this->_responseContentType) && 
                    self::CONTENT_TYPE_JSON == $this->_responseContentType) {
                $errMsg = Thrae_Parser_Json::toJson($errMsg);
            } else {
                if (!empty($errMsg) && is_string($errMsg)) {
                    $errMsg = Thrae_Parser_Xml::toXml(
                        array('error' => $errMsg),
                        null,
                        $this->_xmlPrettyFormat
                    );
                } else {
                    $errMsg = Thrae_Parser_Xml::toXml(
                        $errMsg,
                        null,
                        $this->_xmlPrettyFormat
                    );
                }
            }
            
            $this->_response->sendError($code, $errMsg);
        } else if ($exception instanceof Thrae_Exception) {
            // If it is a Thrae core exception, it means it was a critical type
            // that basically prevents correct functioning of the framework so
            // it should be displayed as a stack trace
            $this->_handleDisplayError($exception);
        } else {
            // Any other exception would be wrapped
            $responseMsg = null;
            
            // If the response content type is set and is set to JSON, then
            // parse as JSON, otherwise parse as XML
            if (isset($this->_responseContentType) && 
                    self::CONTENT_TYPE_JSON == $this->_responseContentType) {
                $responseMsg = Thrae_Parser_Json::toJson(
                    array('error' => $exception->getMessage())
                );
            } else {
                $responseMsg = Thrae_Parser_Xml::toXml(
                    array('error' => $exception->getMessage())
                );
            }
            
            $this->_response->sendError(
                Thrae_Http_Status::STATUS_INTERNAL_SERVER_ERROR,
                $responseMsg
            );
        }
    }
    
    /**
     * Method to help print the stack trace a bit more detailed than just the
     * native printStackTraceAsString method of the Exception class.
     * 
     * @param Exception $exception 
     */
    private function _handleDisplayError(Exception $exception)
    {
        if ($this->_displayErrors) {
            echo get_class($exception), ' with message: ',
                $exception->getMessage(), "\n";
            echo '#0 ', $exception->getFile(), '(', $exception->getLine(),
                ")\n";
            $exceptionIdx = 1;
            
            foreach ($exception->getTrace() as $trace) {
                echo '#', $exceptionIdx , ' ';
                if (isset($trace['file'])) {
                    echo $trace['file'], '(', $trace['line'], ') : ',
                         $trace['class'], $trace['type'], $trace['function'];
                } else {
                    echo '[internal function]: ';
                    if (isset($trace['class'])) {
                        echo $trace['class'], $trace['type'];
                    }
                    echo $trace['function'];
                }
                
                if (empty($trace['args'])) {
                    echo "()\n";
                } else {
                    // Capturing detailed arguments
                    ob_start();
                    var_dump($trace['args']);
                    $args = ob_get_contents();
                    ob_end_clean();
                    
                    // Removing the initial string "array(##)"
                    $args = preg_replace('#^.*{#', '{', $args);
                    // Removing the last "\n" added by the var_dump
                    $args = preg_replace('#}\s$#', '}', $args);
                    // Putting the values of an array inline
                    $args = preg_replace('#=>\s*#', '=> ', $args);
                    echo '(', $args, ")\n";
                }
                $exceptionIdx++;
            }
        }
    }
    
    /**
     * Returns the HTTP method string as new string with all letters lowercased
     * except for the first which is uppercased.
     * <p>Example: POST is returned as Post</p>
     * 
     * @param  string $httpMethod
     * @return string
     */
    private function _getSanitizedRequestMethod($httpMethod)
    {
        return ucfirst(strtolower($httpMethod));
    }

    /**
     * Returns a String constant with the supported content-type value, an 
     * exception is thrown if the type is not supported.
     * <p>The String constants returned can be:
     * <ul>
     *   <li>CONTENT_TYPE_JSON - If the content-type is a JSON compatible.</li>
     *   <li>CONTENT_TYPE_XML - If the content-type is a XML compatible.</li>
     * </ul>
     * </p>
     * 
     * @param  string $contentType
     * @return string
     * @see ::CONTENT_TYPE_JSON
     * @see ::CONTENT_TYPE_XML
     * @throws Thrae_Http_Exception_UnsupportedMediaType if the Content-Type is
     *      not supported
     */
    private function _getSanitizedContentType($contentType)
    {
        $sanitizedType = $this->_getSanitizedType($contentType);

        if (isset($sanitizedType) && $sanitizedType !==false) {
            return $sanitizedType;
        } else if (is_null($sanitizedType)) { 
            return self::CONTENT_TYPE_XML;
        } else {
            throw new Thrae_Http_Exception_UnsupportedMediaType(
                'Content-Type "' . $contentType . '" is not supported'
            );
        }
    }
    
    /**
     * Returns a String constant with the supported HTTP Accept header value, an
     * exception is thrown if the `accept` type is not supported.
     * The String constants returned can be:
     * <ul>
     *   <li>CONTENT_TYPE_JSON - If the accept header is a JSON compatible.</li>
     *   <li>CONTENT_TYPE_XML - If the accept header is a XML compatible.</li>
     * </ul>
     * @param  string $contentType
     * @return string
     * @see ::CONTENT_TYPE_JSON
     * @see ::CONTENT_TYPE_XML
     * @throws Thrae_Http_Exception_UnsupportedMediaType if the HTTP Accept 
     *      header value is not supported
     */
    private function _getSanitizedAcceptHeader($accept)
    {
        $sanitizedType = $this->_getSanitizedType($accept);

        if (isset($sanitizedType)) {
            return $sanitizedType;
        } else if (isset($this->_requestContentType)) {
            return $this->_requestContentType;
        } else {
            throw new Thrae_Http_Exception_UnsupportedMediaType(
                'Accept header "' . $accept . '" is not supported'
            );
        }
    }
    
    /**
     * Returns a String constant representing the type sent as parameter, if the
     * type is not supported FALSE is returned.
     * <p>The String constants returned can be:
     * <ul>
     *   <li>CONTENT_TYPE_JSON - If the type is a JSON compatible content-type
     *       or accept header.
     *   </li>
     *   <li>CONTENT_TYPE_XML - If the type is a XML compatible content-type or
     *       accept header.
     *   </li>
     * </ul>
     * </p>
     * 
     * @param  string $type
     * @return mixed String if the type could be sanitized, <kbd>null</kbd> 
     *      if the parameter was <kbd>null</kbd>, <kbd>false</kbd> otherwise.
     * @see ::CONTENT_TYPE_JSON
     * @see ::CONTENT_TYPE_XML
     */
    private function _getSanitizedType($type)
    {
        if (!isset($type) || empty($type)) {
            return null;
        }

        // Lower case string of the type for comparison
        $type = strtolower($type);
        $typeSection = strstr($type, ';', true);
        if ($typeSection !== false) {
            $type = $typeSection;
        }
        
        if (in_array($type, $this->_xmlContentTypes)) {
            return self::CONTENT_TYPE_XML;
        } else if (in_array($type, $this->_jsonContentTypes)) {
            return self::CONTENT_TYPE_JSON;
        }

        // If  type didn't match any XML or JSON pattern just return false.
        return false;
    }
    
    /**
     * Logs a message to the apache error log if the <kbd>settings.logging</kbd>
     * cofiguration option was set to true.
     * 
     * @param String $message 
     */
    private function _log($message)
    {
        if ($this->_isLogging) {
            error_log($this->_sessLogId . $message);
        }
    }
    
    /**
     * Convenience method to avoid double parsing of the response if logging is
     * not required.
     * <p>Useful to save processing time on a Production environment.</p>
     * 
     * @param array $message 
     */
    private function _logResponse($message){
        if ($this->_isLogging) {
            $message = $this->_parseResponse($message, true);
            $this->_log($message);
        }
    }
}
