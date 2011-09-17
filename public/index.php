<?php
// Copyright (C) 2010-2011 Alejandro Salazar <alphazygma@gmail.com>

/*
 * This library is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Lesser General Public License as published by the Free
 * Software Foundation; either version 2.1 of the License, or (at your option)
 * any later version.
 *
 * This library is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 * See the GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library; if not, write to the Free Software Foundation
 *   Free Software Foundation, Inc.
 *   51 Franklin Street, Fifth Floor
 *   Boston, MA  02110-1301  USA
 */
/*
 * For more info about this PHP RESTful framework look at the Thrae class.
 * For License details, refer to the appended license file.
 */
/*
 * This file is the entry point of the Thrae PHP RESTful Webservice server
 */

/*
 * The Thrae PHP RESTful Webservice framework is self contained and is meant to
 * be very lightweight, it doesn't support any MVC for that is left up to the
 * developer.
 * This framework basically provides with a way to parse RESTful webservice
 * calls either in JSON or XML and respond in the same way, and simplifying
 * the logic that users have to deal with.
 * So if your webservices require the use of your own libraries or external
 * libraries to deal for example with database, just add them to the run-time
 * path (update application/config/application.ini).
 */

// Define path to application directory
$dirName = dirname(__FILE__);
defined('APPLICATION_PATH') 
    || define('APPLICATION_PATH', realpath($dirName.'/../application'));

defined('THRAE_LIBRARY_PATH') 
    || define('THRAE_LIBRARY_PATH', realpath($dirName.'/../library'));


// Define application environment
// Obtaining environment from the global environment variable which can be
// defined in the shell or the Apache Host (SetEnv directive)
$env = getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production';
defined('APPLICATION_ENV') || define('APPLICATION_ENV', $env);
unset($env);


// Adding the Application directory and the THRAE libraries to the path
set_include_path(
    implode(
        PATH_SEPARATOR, 
        array(
            THRAE_LIBRARY_PATH,
            APPLICATION_PATH,
            get_include_path(),
        )
    )
);

/** Required Libraries */
require_once 'Thrae/Application.php';
$thrae = new Thrae_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/config/application.ini'
);
$thrae->dispatch();
