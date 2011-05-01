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
 * Router
 * 
 * Carrot's default Router. Uses chain of responsibility to store anonymous functions
 * that try to determine the destination. You can think of each anonymous function as
 * a Closure object representing a route.
 *
 * @author      Ricky Christie <seven.rchristie@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 */

namespace Carrot\Core;

class Router implements \Carrot\Core\Interfaces\RouterInterface
{
    /**
     * @var array List of anonymous functions as chain of responsibility.
     */
    protected $chains = array();
    
    /**
     * @var int The function index currently active, set initially as -1, means no index is active.
     */
    protected $active_index = -1;
    
    /**
     * @var Destination Default Destination object to return if there's no matching route. 
     */
    protected $no_matching_route_destination;
    
    /**
     * @var StdClass PHP standard class containing 
     */
    protected $params;
    
    /**
     * Constructs a Router object.
     *
     * @param array $params Routing parameters to be passed to the anonymous functions.
     * @param Destination $no_matching_route_destination Default destination to return when there is no matching route.
     *
     */
    public function __construct(array $params, \Carrot\Core\Destination $no_matching_route_destination)
    {
        $this->params = (object)$params;
        $this->no_matching_route_destination = $no_matching_route_destination;
    }
    
    /**
     * Add a new function to the chain of responsibility.
     *
     * Your anonymous function should accept two parameters: $params and $router.
     * Routing parameters are set during object construction. If you need to add
     * another parameter (be it an object or a simple string), you should do so
     * at the dependency registration file.
     *
     * Your anonymous function must either return a Destination instance or pass
     * the parameters to the next function in the chain of responsibility.
     *
     * <code>
     * $router->add(function($params, $router)
     * {
     *     // Returns a destination for '/'
     *     if (empty($params->request->getAppRequestURISegments()))
     *     {
     *         return new Destination
     *         (
     *             '\Vendor\Namespace\HomeController@main',
     *             'index',
     *             array('Key Lime Pie', 'Cupcake', 'Orange Juice')
     *         );
     *     }
     *
     *     // Otherwise, not my responsibility
     *     return $router->next($request, $session);
     * });
     * </code>
     *
     * Once you returned a destination, the chain stops, so if there are two functions
     * handling the same route, the earliest function always wins.
     *
     * >> WARNING <<
     *
     * Don't call router methods other than Router::next(), Router::rewind()
     * and Router::getDestinationForNoMatchingRoute() inside the anonymous function
     * unless you wanted an unpredicted behavior (and possibly infinite loop).
     *
     * @param Closure $chain
     *
     */
    public function add(\Closure $chain)
    {
        $this->chains[] = $chain;
    }
    
    /**
     * Starts the chain of responsibility to get the Destination object.
     *
     * If the returned value is not an instance of Destination this method
     * will throw a RuntimeException. If you have no route defined, it will
     * also throw a RuntimeException.
     *
     * @return Destination
     *
     */
    public function getDestination()
    {
        if (empty($this->chains))
        {
            throw new \RuntimeException('Router error in getting Destination, it doesn\'t have any route defined.');
        }
        
        $this->active_index = -1;
        $destination = $this->next($this->params, $this);
        
        if (!is_a($destination, '\Carrot\Core\Destination'))
        {
            throw new \RuntimeException("Router error in getting Destination, route #{$this->active_index} does not return an instance of \Carrot\Core\Destination.");
        }
        
        return $destination;
    }
    
    /**
     * Calls the next function in the chain of responsibility.
     * 
     * Passes $request and $session to the next function. If the chain is exhausted
     * it will return the default Router::no_matching_route_destination instead.
     *
     * @param StdClass $params Standard PHP class containing the routing parameters, set at constructor.
     * @param Router $router An instance of this Router class.
     *
     */
    public function next($params, $router)
    {
        ++$this->active_index;
        
        if (!isset($this->chains[$this->active_index]) or !is_callable($this->chains[$this->active_index]))
        {
            return $this->no_matching_route_destination;
        }
        
        return $this->chains[$this->active_index]($params, $router);
    }
    
    /**
     * Restarts the chain of responsibility with new parameters.
     *
     * It's usually a bad practice to redefine the user's request/session
     * object and restart the chain of responsibility, it is better to use
     * response redirection. If you need it so badly, here it is. It resets
     * Router::active_index to -1 and in doing so restarts the chain.
     *
     * @param StdClass $params Standard PHP class containing the routing parameters, set at constructor.
     * @param Router $router An instance of this Router class.
     *
     */
    public function rewind($params, $router)
    {
        $this->active_index = -1;
        return $this->next($params, $router);
    }
    
    /**
     * Sets a default destination to return to if there is no matching route.
     *
     * @param Destination
     *
     */
    public function setDestinationForNoMatchingRoute(\Carrot\Core\Destination $destination)
    {
        $this->no_matching_route_destination = $destination;
    }
    
    /**
     * Gets the default destination to go if there's no matching route.
     *
     * @return Destination
     *
     */
    public function getDestinationForNoMatchingRoute()
    {   
        return $this->no_matching_route_destination;
    }
    
    /**
     * Loads a file that defines routes.
     *
     * @param string $path Absolute path to the file.
     *
     */
    public function loadRoutesFile($path)
    {
        if (file_exists($path))
        {
            $router = $this;
            require($path);
        }
    }
}