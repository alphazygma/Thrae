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
 * The <kbd>Sample_SimpleService</kbd> class just works as a health check
 * service, response is the same for every request method.
 * 
 * @author Alejandro B. Salazar <alphazygma@gmail.com>
 * @version Sample
 * @version 0.1
 * @since 0.1
 */
class Sample_SimpleService
{
    /**
     * @Get
     * @Post
     * @Put
     * @Delete
     */
    public function healthCheck()
    {
        return 'OK';
    }
}
