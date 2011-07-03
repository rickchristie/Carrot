<?php

/**
 * This file is part of the Carrot framework.
 *
 * Copyright (c) 2011 Ricky Christie <seven.rchristie@gmail.com>
 *
 * Licensed under the MIT License.
 *
 */

/**
 * Default provider class for Request
 * 
 * Injects the request class with superglobals.
 *
 * >> Notice <<
 * 
 * This is a default provider class for a \Carrot\Core class. DO
 * NOT EDIT THIS FILE DIRECTLY. If you need to replace this
 * provider class with your own, follow this steps:
 *
 * <ol>
 *  <li>Copy and paste this provider class to a new class under
 *  your own namespace.</li>
 *  <li>Modify your custom provider class as needed.</li>
 *  <li>Bind your custom provider class in 'providers.php', this
 *  will make the DIC prioritizes your class and use your
 *  provider class instead.</li>
 * </ol>
 * 
 * @author      Ricky Christie <seven.rchristie@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 */

namespace Carrot\Core;

class RequestProvider extends Provider
{
    /**
     * @var array List of singleton configurations (case-sensitive).
     */
    protected $singletons = array('Main');
    
    /**
     * Returns the instance for 'Main' configuration.
     *
     * @return Request
     *
     */
    public function getMain()
    {
        return new Request($_SERVER, $_GET, $_POST, $_FILES, $_COOKIE, $_REQUEST, $_ENV);
    }
}