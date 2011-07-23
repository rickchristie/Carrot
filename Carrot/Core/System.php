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
 * System
 * 
 * This class is the bells and whistles of Carrot's framework.
 * It's refactored index.php file, so this class is sort of the
 * actor that initializes and calls everything.
 * 
 * @author      Ricky Christie <seven.rchristie@gmail.com>
 * @license     http://www.opensource.org/licenses/mit-license.php MIT License
 *
 */

namespace Carrot\Core;

use ErrorException;

class System
{
    /**
     * @var string Absolute file path to DIC configuration file.
     */
    protected $configFilePath;
    
    /**
     * @var string Absolute file path to router configuration file.
     */
    protected $routesFilePath;
    
    /**
     * @var string Absolute file path to autoloader configuration file.
     */
    protected $autoloadFilePath;
    
    /**
     * @var string Absolute file path to Autoloader class file.
     */
    protected $autoloaderClassFilePath;
    
    /**
     * @var Autoloader The Autoloader instance instantiated by initializeAutoloader().
     */
    protected $autoloader;
    
    /**
     * @var \Closure The anonymous function used to load files without leaking scope.
     */
    protected $loadFileFunction;
    
    /**
     * @var \Closure The anonymous function used to convert errors into ErrorException.
     */
    protected $errorHandlerFunction;
    
    /**
     * @var DependencyInjectionContainer Object used to wire dependencies.
     */
    protected $dic;
    
    /**
     * @var ExceptionHandler Object used to handle exceptions.
     */
    protected $exceptionHandler;
    
    /**
     * @var Router Object used to route requests to a specific Destination.
     */
    protected $router;
    
    /**
     * Constructs the Carrot Sytem object.
     *
     * @param string $configFilePath Absolute file path to DIC configuration file.
     * @param string $routesFilePath Absolute file path to router configuration file.
     * @param string $autoloadFilePath Absolute file path to autoloader configuration file.
     * @param string $autoloaderClassFilePath Absolute file path to Autoloader class file.
     *
     */
    public function __construct($configFilePath, $routesFilePath, $autoloadFilePath, $autoloaderClassFilePath)
    {
        $this->loadFileFunction = function($filePath, array $params)
        {
            extract($params);
            require $filePath;
        };
        
        $this->errorHandlerFunction = function($errno, $errstr, $errfile, $errline)
        {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        };
        
        $this->configFilePath = $configFilePath;
        $this->routesFilePath = $routesFilePath;
        $this->autoloadFilePath = $autoloadFilePath;
        $this->autoloaderClassFilePath = $autoloaderClassFilePath;
    }
    
    /**
     * Sets error_reporting to E_ALL | E_STRICT.
     *
     * Carrot advocates strict coding, thus this framework will report
     * all errors, even the tiniest mistakes. To control how errors
     * are going to be displayed, configure exception handling in 
     * Carrot.
     *
     */
    public function reportAllErrors()
    {
        error_reporting(E_ALL | E_STRICT);
    }
    
    /**
     * Checks PHP requirements, exits processing if not fulfilled.
     * 
     * Makes sure magic_quotes_gpc is turned off, register_globals is
     * turned off and makes sure the PHP_VERSION is more than 5.3.0.
     * Will immediately call exit() if any requirement is not
     * fulfilled.
     * 
     */
    public function checkPHPRequirements()
    {
        if (get_magic_quotes_gpc())
        {
            exit('Magic quotes are on. Please turn off magic quotes.');
        }
        
        if (ini_get('register_globals'))
        {
            exit('Register globals are on. Please turn off register globals.');
        }
        
        if (version_compare(PHP_VERSION, '5.3.0') < 0)
        {
            exit('Carrot requires at least PHP 5.3, please upgrade.');
        }
    }
    
    /**
     * Makes sure all the required files exists.
     *
     * Checks to see if DIC configuration file, router configuration
     * file, autoloader configuration file and autoloader class file
     * really exists or not. Exits processing immediately if one of
     * them does not exists.
     *
     */
    public function checkRequiredFileExistence()
    {
        if (!file_exists($this->configFilePath))
        {
            exit("DIC configuration file does not exist ($configFilePath).");
        }
        
        if (!file_exists($this->routesFilePath))
        {
            exit("Router configuration file does not exist ($routesFilePath).");
        }
        
        if (!file_exists($this->autoloadFilePath))
        {
            exit("Autoloader configuration file does not exist ($autoloadFilePath).");
        }
        
        if (!file_exists($this->autoloaderClassFilePath))
        {
            exit("Autoloader class file does not exist ($autoloaderClassFilePath).");
        }
    }
    
    /**
     * Set up the autoloader.
     *
     * Instantiates Autoloader, loads the autoloader configuration
     * file and register the autoloader. The autoloader configuration
     * file is loaded from anonymous function so that it doesn't have
     * any access to this class.
     *
     */
    public function initializeAutoloader()
    {
        require $this->autoloaderClassFilePath;
        $this->autoloader = new Autoloader;
        $loadFile = $this->loadFileFunction;
        $loadFile($this->autoloadFilePath, array('autoloader' => $this->autoloader));
        $this->autoloader->register();
    }
    
    /**
     * Set up the dependency injection container.
     *
     * Instantiates DependencyInjectionContainer (DIC), registers
     * default bindings for Carrot\Core and Carrot\Docs, and loads
     * the user DIC configuration file.
     *
     * You can override default bindings in your configuration file.
     *
     */
    public function initializeDependencyInjectionContainer()
    {
        $this->dic = new DependencyInjectionContainer;
        $this->registerDefaultDICBindings();
        $loadFile = $this->loadFileFunction;
        $loadFile($this->configFilePath, array('dic' => $this->dic));
    }
    
    /**
     * Set up the error handler.
     * 
     * Uses set_error_handler to register an anonymous function that
     * converts regular PHP errors to ErrorException instances.
     * 
     */
    public function initializeErrorHandler()
    {
        set_error_handler($this->errorHandlerFunction);
    }
    
    /**
     * Set up the exception handler.
     * 
     * Gets Carrot\Core\ExceptionHandler{Main:Transient} instance from
     * the DIC and sets the exception handler. 
     *
     */
    public function initializeExceptionHandler()
    {
        $this->exceptionHandlerManager = $this->dic->getInstance(
            new ObjectReference('Carrot\Core\ExceptionHandlerManager{Main:Transient}')
        );
        
        $this->exceptionHandlerManager->setDIC($this->dic);
        $this->exceptionHandlerManager->setDefaultServerProtocol($_SERVER['SERVER_PROTOCOL']);
        $this->exceptionHandlerManager->set();
    }
    
    /**
     * Set up the router.
     * 
     * Gets Carrot\Core\Router{Main:Singleton} instance from the DIC,
     * loads user's router configuration file, and initializes the
     * router object.
     *
     */
    public function initializeRouter()
    {
        $this->router = $this->dic->getInstance(
            new ObjectReference('Carrot\Core\Router{Main:Singleton}')
        );
        
        $loadFile = $this->loadFileFunction;
        $loadFile($this->routesFilePath, array('router' => $this->router));
        $this->router->instantiateRouteObjects($this->dic);
    }
    
    /**
     * Dispatches the request and send the response to client.
     *
     * Instantiates the front controller, gets the destination from
     * router, tells the front controller to get the response, and
     * then sends the response to the client.
     *
     */
    public function run()
    {
        $frontController = $this->dic->getInstance(
            new ObjectReference('Carrot\Core\FrontController{Main:Transient}')
        );
        
        $destination = $this->router->getDestination();
        $response = $frontController->dispatch($this->dic, $destination);
        $response->send();
    }
    
    /**
     * Registers default DIC bindings for core classes.
     *
     * List of registered default DIC bindings for core classes:
     *
     * <code>
     * Carrot\Core\AppRequestURI{Main:Transient}
     * Carrot\Core\ExceptionHandler{Main:Transient}
     * Carrot\Core\Request{Main:Transient}
     * Carrot\Core\FrontController{Main:Transient}
     * </code>
     *
     * Besides that there are also default bindings for Carrot\Docs:
     *
     * <code>
     * Carrot\Docs\Route{Main:Transient}
     * Carrot\Docs\Controller{Main:Transient}
     * Carrot\Docs\View{Main:Transient}
     * </code>
     *
     * You should be able to override the above default bindings in
     * your DIC configuration file (defaults to config.php).
     *
     */
    protected function registerDefaultDICBindings()
    {
        $this->dic->bind('Carrot\Core\AppRequestURI{Main:Transient}', array(
            $_SERVER['SCRIPT_NAME'],
            $_SERVER['REQUEST_URI']
        ));
        
        $this->dic->bind('Carrot\Core\ExceptionHandlerManager{Main:Transient}', array(
            array('Exception' => new ObjectReference('Carrot\Core\ExceptionHandler{Main:Transient}'))
        ));
        
        $this->dic->bind('Carrot\Core\Request{Main:Transient}', array(
            $_SERVER,
            $_GET,
            $_POST,
            $_FILES,
            $_COOKIE,
            $_REQUEST,
            $_ENV
        ));
        
        $this->dic->bind('Carrot\Core\FrontController{Main:Transient}', array(
            $_SERVER['SERVER_PROTOCOL']
        ));
        
        $this->dic->bind('Carrot\Docs\Route{Main:Transient}', array(
            new ObjectReference('Carrot\Core\AppRequestURI{Main:Transient}')
        ));
        
        $this->dic->bind('Carrot\Docs\Controller{Main:Transient}', array(
            new ObjectReference('Carrot\Docs\View{Main:Transient}'),
            new ObjectReference('Carrot\Docs\Model{Main:Singleton}')
        ));
        
        $this->dic->bind('Carrot\Docs\View{Main:Transient}', array(
            new ObjectReference('Carrot\Docs\Model{Main:Singleton}'),
            new ObjectReference('Carrot\Core\Router{Main:Singleton}'),
            'CarrotDocs'
        ));
    }
}