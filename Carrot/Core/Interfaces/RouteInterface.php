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
 * Route Interface
 *
 * This interface defines a contract between your route class and
 * the Router. You will have to provide two methods for two-way
 * routing: one for translating requests to an instance of
 * Callback, the other for translating arguments from view into
 * a URL.
 *
 * Please note that a Route class is defined as a class that
 * contains both the data and logic required to represent one
 * specific two-way routing. This means if your route class needs
 * dependencies, you will have to inject it yourself by
 * configuring the DIC.
 * 
 * @author      Ricky Christie <seven.rchristie@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 */

namespace Carrot\Core\Interfaces;

interface RouteInterface
{   
    /**
     * Routes the current request into a Callback instance.
     * 
     * The Router will loop through registered route objects and call
     * this method when routing. If your route object cannot route
     * the current request, simply return nothing and the Router will
     * move on to the next route object.
     * 
     * @see \Carrot\Core\Callback
     * @return mixed Either an instance of \Carrot\Core\Callback or null.
     * 
     */
    public function route();
    
    /**
     * Translates arguments from view into a valid string URL.
     * 
     * When your view or template calls
     * \Carrot\Core\Router::getURL(), the Router will relay the
     * arguments to this method. Make sure you return a valid URL,
     * as a matter of convention, do NOT place the URL string in any
     * formatting function, like urlencode() or htmlspecialchars(),
     * simply return the URL string as it is.
     * 
     * @return string Valid URL.
     *
     */
    public function getURL(array $args);
}