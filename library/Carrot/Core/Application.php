<?php

/**
 * This file is part of the Carrot framework.
 *
 * Copyright (c) 2011 Ricky Christie <seven.rchristie@gmail.com>.
 *
 * Licensed under the MIT License.
 *
 */

/**
 * Application.
 *
 * Represents a web application. The reponsibility of this class
 * is to instantiate and orchestrate Carrot core classes in order
 * to generate a response from the request.
 *
 * @author  Ricky Christie <seven.rchristie@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 *
 */

namespace Carrot\Core;

use Exception,
    RuntimeException,
    ErrorException,
    InvalidArgumentException,
    ReflectionMethod,
    Carrot\Autoloader\AutoloaderInterface,
    Carrot\DependencyInjection\Container,
    Carrot\DependencyInjection\Reference,
    Carrot\DependencyInjection\Config\ConfigInterface,
    Carrot\DependencyInjection\Injector\PointerInjector,
    Carrot\DependencyInjection\Injector\ConstructorInjector,
    Carrot\Logbook\LogbookInterface,
    Carrot\ExceptionHandler\HandlerInterface,
    Carrot\Event\DispatcherInterface,
    Carrot\Routing\Destination,
    Carrot\Routing\Router,
    Carrot\Routing\Config\ConfigInterface as RoutingConfigInterface,
    Carrot\Response\ResponseInterface,
    Carrot\Response\HTTPResponse,
    Carrot\Response\CLIResponse;

class Application
{
    /**
     * @var array Main configuration array from main configuration
     *      file.
     */
    protected $config;
    
    /**
     * @var callback The anonymous function used to load files
     *      without leaking scope.
     */
    protected $loadFileFunction;
    
    /**
     * @var AutoloaderInterface The class file autoloader.
     */
    protected $autoloader;
    
    /**
     * @var Logbook Carrot core classes' log container, useful for
     *      debugging.
     */
    protected $logbook;
    
    /**
     * @var Container The dependency injection container.
     */
    protected $container;
    
    /**
     * @var ConfigInterface The configuration object for the
     *      dependency injection container.
     */
    protected $containerConfig;
    
    /**
     * @var DispatcherInterface The event dispatcher.
     */
    protected $dispatcher;
    
    /**
     * @var HandlerInterface The exception handler.
     */
    protected $exceptionHandler;
    
    /**
     * Constructor.
     * 
     * The configuration array consists of $files, $defaults, and
     * $base sub-configuration.
     *
     * <code>
     * $config = array(
     *     'files' => $files,
     *     'defaults' => $defaults,
     *     'base' => $base
     * );
     * </code>
     * 
     * The $files configuration array contains absolute paths to
     * other configuration files. Its structure is as follows:
     *
     * <code>
     * $files = array(
     *     'autoloader' => $autoloaderFilePath,
     *     'injectionConfig' => $injectionConfigFilePath,
     *     'eventConfig' => $eventConfigFilePath,
     *     'routingConfig' => $routingConfigFilePath
     * );
     * </code>
     * 
     * The $defaults configuration array configures which
     * implementations of request, logbook, event dispatcher, routing
     * configuration, and HTTP URI. The structure is as follows:
     *
     * <code>
     * $defaults = array(
     *     'request' => $requestArray,
     *     'logbook' => $logbookArray,
     *     'exceptionHandler' => $handlerArray,
     *     'eventDispatcher' => $dispatcherArray,
     *     'routingConfig' => $routingArray,
     *     'HTTPURI' => $className
     * );
     * </code>
     *
     * Request, logbook, exception handler, event dispatcher, and
     * routing config items contains an array that holds information
     * on the class and instance name of the implementation to be
     * used, as in:
     *
     * <code>
     * $requestArray = array(
     *     'class' => 'Carrot\Routing\DefaultRequest',
     *     'name' => ''
     * );
     * </code>
     *
     * The $base configuration array configures the base HTTP URI of
     * the entier application, as in:
     * 
     * <code>
     * $base = array(
     *     'scheme' => 'http',
     *     'authority' => 'example.com',
     *     'path' => '/'
     * );
     * </code>
     *
     * For more information please see the documentation.
     * 
     * @param array $config Configuration array from main configuration file.
     *
     */
    public function __construct(array $config)
    {
        if ($this->isConfigValid($config) == FALSE)
        {
            throw new InvalidArgumentException('Application error in instantiation. The provided configuration array is not valid.');
        }
        
        $this->loadFileFunction = function($filePath, array $params = array())
        {
            extract($params);
            return require $filePath;
        };
        
        $this->config = $config;
    }
    
    /**
     * Initializes the environment and Carrot's core classes.
     * 
     * @see checkPHPRequirements()
     * @see reportAllErrors()
     * @see errorHandler()
     * @see initializeAutoloader()
     * @see initializeDependencyInjectionContainer();
     *
     */
    public function initialize()
    {
        $this->checkPHPRequirements();
        $this->reportAllErrors();
        $this->setErrorHandler();
        $this->setTemporaryExceptionHandler();
        $this->initializeAutoloader();
        $this->initializeDependencyInjectionContainer();
        $this->initializeLogbook();
        $this->initializeExceptionHandler();
        $this->initializeEventDispatcher();
        $this->initializeRouter();
    }
    
    /**
     * Route the request, instantiate the destination object, and
     * return the response.
     * 
     * The {@see Destination} instance contains information on which
     * object to instantiate, which method to call, and what
     * arguments to pass when calling the method. This method will
     * 'run' the destination method. If it returns an instance of
     * ResponseInterface, it will be returned. If it returns another
     * instance of Destination, internal redirection is triggered
     * and the method will try to run the returned Destination
     * instance instead.
     *
     * If the destination method returns something other than
     * Destination or ResponseInterface instance, this method will
     * try to convert it to string and use default response objects.
     * 
     * @throws RuntimeException If the destination reached doesn't
     *         return an instance of ResponseInterface, or if this
     *         method fails to convert the returned value to string.
     * @return ResponseInterface
     *
     */
    public function run()
    {
        $response = $destination = $this->router->routeRequest();
        
        while ($response instanceof Destination)
        {
            $destination = $response;
            $object = $this->container->get($destination->getReference());
            $response = $this->runDestinationMethod(
                $object,
                $destination->getMethodName(),
                $destination->getArgs()
            );
            
            if ($response instanceof ResponseInterface)
            {
                return $response;
            }
            
            if ($response instanceof Destination)
            {
                continue;
            }
            
            $response = $this->convertToResponseObject($response);
            return $response;
        }
    }
    
    /**
     * Carrot's default error handler.
     *
     * Converts PHP errors into ErrorException.
     *
     * @see initialize()
     *
     */
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
    
    /**
     * Carrot's temporary exception handler.
     *
     * This exception handler is removed when the main exception
     * handler class gets initialized,
     * {@see initializeMainExceptionHandler()}. This method will
     * print out generic 500 Internal Server Error if display_errors
     * is turned off, otherwise will print out the exception message,
     * in a slightly better format than PHP default.
     *
     * @see initialize()
     *
     */
    public function temporaryExceptionHandler(Exception $exception)
    {
        if (!ini_get('display_errors'))
        {
            $body = '<p>We are sorry for the inconvenience, but an internal
            server error has occurred. If you are this site\'s administrator,
            you can turn on <code>display_errors</code> to see more details
            about this error.</p>';
        }
        else 
        {
            $class = get_class($exception);
            $line = $exception->getLine();
            $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES);
            $file = $exception->getFile();
            $body = 
                "<p>
                    An uncaught exception occured before the main exception
                    handler is set. This can be either a problem in the
                    configuration files or a bug in Carrot's core classes.
                </p>
                
                <p class=\"message\">
                    {$message} on line {$line} in file <code>{$file}</code>
                </p>";
        }
        
        echo
            "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">
            <html>
            <head>
            	<title>500 Internal Server Error</title>
            	<style type=\"text/css\">
            	   body { width: 500px; margin: 150px; font-family: 'Arial', 'Helvetica', sans-serif; font-size: 13px; line-height: 1.5; }
            	   h1 { font-weight: normal; font-size: 25px }
            	   code { font-family: 'Monaco', 'Consolas', 'Courier', 'Courier New', monospace; font-size: 90%; }
            	   p { margin: 15px 0; }
            	   p.message { background: #f1f7fd; border: 1px solid #aebece; -moz-border-radius: 5px; border-radius: 5px; padding: 15px; overflow-x: auto; }
            	   ul { margin: 15px 0; padding: 0 0 0 0; list-style-type: none; }
            	   ul li { margin: 8px 0; padding: 0 0 0 40px; background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAaRJREFUeNqc0l8oQ1EcB/DvuXfW7v4gWY3JrNGWPKiJlELhDSnlAU8elPLiyYNXj548+fO49qgm7/IkL1LTZCzNljQMubYx9x6/W1eNdGd+dfqdfuf26Xd+5zLzqh9amCxV+C1el6IwCkHPzfhnfAH2j0JxGxyLtA9WApj0HLOo5lCxoGwxizABhonwcaKG6s/6Mu6gVrF7q7hpn3Heai4ogyvxm5hk5kk6SkZmrMNlgSdRvhIhBGxcwkL6DqPVucaRPqDbp2hdRAgJlpsBZNvb+VQm09Mvy+iQFCoADU0iOj2qpCMuQ4CGiKHHh2ies9vUJRXShDyp8LgFBFzcrSPST4Cuzb8V6KNeSnvt7x91bfV05qI524HTayBxzzbHQ7l5Q0BHtP9ix5dXgh02le4iAg6GoxTD7SvGCdk1BHREa3fDIyuzLSYVtU4BGUnAYYZlqd5GSNYQKIHmKK0FZKXGb+U4s4qIF1iYgOk/ATqivcC6K6dOermKC5uIe7ABQg7+BJRAYxrke1GbEw7hhPZdFQEls1mitUxrvmKgBHJSyn8KMAD+06jQNTPITwAAAABJRU5ErkJggg==); background-repeat: no-repeat; background-position: 18px 1px; }
            	</style>
            </head>
            <body>
                <h1>500 Internal Server Error</h1>
            	{$body}
            </body>
            </html>";
    }
    
    /**
     * Check if the configuration array is valid.
     *
     * The configuration array is considered valid if it contains all
     * needed values. See documentation for a list of required
     * values.
     * 
     * @see __construct()
     * @param array $config The configuration array to test.
     * @return bool TRUE if valid, FALSE otherwise.
     *
     */
    protected function isConfigValid(array $config)
    {
        return (
            isset(
                $config['files']['autoloader'],
                $config['files']['injectionConfig'],
                $config['files']['eventConfig'],
                $config['files']['routingConfig'],
                $config['defaults']['request']['class'],
                $config['defaults']['request']['name'],
                $config['defaults']['logbook']['class'],
                $config['defaults']['logbook']['name'],
                $config['defaults']['exceptionHandler']['class'],
                $config['defaults']['exceptionHandler']['name'],
                $config['defaults']['eventDispatcher']['class'],
                $config['defaults']['eventDispatcher']['name'],
                $config['defaults']['routingConfig']['class'],
                $config['defaults']['routingConfig']['name'],
                $config['defaults']['HTTPURI'],
                $config['base']['scheme'],
                $config['base']['authority'],
                $config['base']['path']
            )
        );
    }
    
    /**
     * Checks PHP requirements, exits processing if not fulfilled.
     * 
     * Makes sure magic_quotes_gpc is turned off, register_globals is
     * turned off and makes sure the PHP_VERSION is more than 5.3.0.
     *
     * @throws RuntimeException If one of the PHP requirements is not
     *         fulfilled.
     * @see initialize()
     * 
     */
    protected function checkPHPRequirements()
    {
        if (get_magic_quotes_gpc())
        {
            throw new RuntimeException('Magic quotes are on. Please turn off magic quotes.');
        }
        
        if (ini_get('register_globals'))
        {
            throw new RuntimeException('Register globals are on. Please turn off register globals.');
        }
        
        if (version_compare(PHP_VERSION, '5.3.0') < 0)
        {
            throw new RuntimeException('Carrot requires at least PHP 5.3, please upgrade.');
        }
    }
    
    /**
     * Sets error_reporting to E_ALL | E_STRICT.
     *
     * Carrot advocates strict coding, thus this framework will report
     * all errors, even the tiniest mistakes. To control how errors
     * are going to be displayed, configure exception handling in 
     * Carrot.
     *
     * @see initialize()
     *
     */
    protected function reportAllErrors()
    {
        error_reporting(E_ALL | E_STRICT);
    }
    
    /**
     * Set Carrot's main error handler with set_error_handler().
     * 
     * @see initialize()
     *
     */
    protected function setErrorHandler()
    {
        set_error_handler(array($this, 'errorHandler'));
    }
    
    /**
     * Set Carrot's temporary exception handler with
     * set_exception_handler().
     *
     * The temporary exception handler is for uncaught exceptions
     * that happens before the main exception handler is properly
     * initialized.
     *
     * @see initialize()
     * @see removeTemporaryExceptionHandler()
     *
     */
    protected function setTemporaryExceptionHandler()
    {
        set_exception_handler(array($this, 'temporaryExceptionHandler'));
    }
    
    /**
     * Remove Carrot's temporary exception handler with
     * restore_exception_handler().
     *
     * Remove temporary exception handler so that the main exception
     * handler class can take its place.
     * 
     * @see initialize()
     * @see setTemporaryExceptionHandler()
     *
     */
    protected function removeTemporaryExceptionHandler()
    {
        restore_exception_handler();
    }
    
    /**
     * Loads the autoloader file and register the returned autoloader.
     * 
     * @see initialize()
     * @throws RuntimeException If file does not exist, or if the
     *         returned value from the loaded file is not an instance
     *         of AutoloaderInterface.
     *
     */
    protected function initializeAutoloader()
    {
        $filePath = $this->config['files']['autoloader'];
        
        if (!file_exists($filePath))
        {
            throw new RuntimeException("Carrot autoloader file ({$filePath}) does not exist.");
        }
        
        $loadFile = $this->loadFileFunction;
        $autoloader = $loadFile($filePath);
        
        if (($autoloader instanceof AutoloaderInterface) == FALSE)
        {
            $unexpectedType = is_object($autoloader) ? get_class($autoloader) : gettype($autoloader);
            throw new RuntimeException("Expected instance of Carrot\Autoloader\AutoloaderInterface from the autoloader file ({$filePath}), {$unexpectedType} given.");
        }
        
        $autoloader->register();
        $this->autoloader = $autoloader;
    }
    
    /**
     * Load the injection configuration object and instantiates the
     * dependency injection container.
     * 
     * @see initialize()
     * @throws RuntimeException If the file doesn't exist, or if the
     *         loaded file doesn't return an instance of
     *         ConfigInterface.
     *
     */
    protected function initializeDependencyInjectionContainer()
    {
        $filePath = $this->config['files']['injectionConfig'];
        
        if (!file_exists($filePath))
        {
            throw new RuntimeException("Carrot injection configuration file ({$filePath}) does not exist.");
        }
        
        $loadFile = $this->loadFileFunction;
        $config = $loadFile($filePath);
        
        if ($config instanceof ConfigInterface == FALSE)
        {
            $unexpectedType = is_object($config) ? get_class($config) : gettype($config);
            throw new RuntimeException("Expected instance of Carrot\DependencyInjection\ConfigInterface from the injection configuration file ({$filePath}), {$unexpectedType} given.");
        }
        
        $this->addReservedInjectors($config);
        $this->containerConfig = $config;
        $this->container = new Container($config);
    }
    
    /**
     * Adds injectors that are necessary for Carrot to run correctly.
     *
     * Adds PointerInjectors that resolve references to core
     * interfaces to its concrete implementations based on user
     * configuration.
     * 
     * @param ConfigInterface $config Dependency injection
     *        configuration.
     *
     */
    protected function addReservedInjectors(ConfigInterface $config)
    {
        $config->addInjector(new PointerInjector(
            new Reference('Carrot\Request\RequestInterface'),
            new Reference(
                $this->config['defaults']['request']['class'],
                'Singleton',
                $this->config['defaults']['request']['name']
            )
        ));
        
        $config->addInjector(new PointerInjector(
            new Reference('Carrot\Logbook\LogbookInterface'),
            new Reference(
                $this->config['defaults']['logbook']['class'],
                'Singleton',
                $this->config['defaults']['logbook']['name']
            )
        ));
        
        $config->addInjector(new PointerInjector(
            new Reference('Carrot\ExceptionHandler\HandlerInterface'),
            new Reference(
                $this->config['defaults']['exceptionHandler']['class'],
                'Singleton',
                $this->config['defaults']['exceptionHandler']['name']
            )
        ));
        
        $config->addInjector(new PointerInjector(
            new Reference('Carrot\Event\DispatcherInterface'),
            new Reference(
                $this->config['defaults']['eventDispatcher']['class'],
                'Singleton',
                $this->config['defaults']['eventDispatcher']['name']
            )
        ));
        
        $config->addInjector(new PointerInjector(
            new Reference('Carrot\Routing\Config\ConfigInterface'),
            new Reference(
                $this->config['defaults']['routingConfig']['class'],
                'Singleton',
                $this->config['defaults']['routingConfig']['name']
            )
        ));
        
        $config->addInjector(new PointerInjector(
            new Reference('Carrot\Routing\RouterInterface'),
            new Reference('Carrot\Routing\Router')
        ));
    }
    
    /**
     * Initialize an implementation of LogbookInterface and set it to
     * the dependency injection container.
     *
     * TODO: Finish Logbook package, add it to container.
     * TODO: Update DebugHandler to display Logbook info.
     * 
     * @see initialize()
     *
     */
    protected function initializeLogbook()
    {   
        $this->logbook = $this->container->get(new Reference(
            'Carrot\Logbook\LogbookInterface'
        ));
    }
    
    /**
     * Get the exception handler implementation from the container,
     * removes temporary exception handler and registers the
     * new exception handler.
     *
     * @see initialize()
     *
     */
    protected function initializeExceptionHandler()
    {
        $this->exceptionHandler = $this->container->get(new Reference(
            'Carrot\ExceptionHandler\HandlerInterface'
        ));
        
        $this->removeTemporaryExceptionHandler();
        set_exception_handler(array($this->exceptionHandler, 'handle'));
    }
    
    /**
     * Load the event dispatcher file and set its container using
     * {@see DispatcherInterface::setContainer()}.
     *
     * Notifies the event 'Carrot.Core.EventDispatcherReady' after
     * the event dispatcher has been properly set up.
     * 
     * @see initialize()
     * @throws RuntimeException If the file doesn't exist, or if the
     *         file doesn't return an instance of
     *         DispatcherInterface.
     *
     */
    protected function initializeEventDispatcher()
    {
        $dispatcher = $this->container->get(new Reference(
            'Carrot\Event\DispatcherInterface'
        ));
        
        $filePath = $this->config['files']['eventConfig'];
        
        if (!file_exists($filePath))
        {
            throw new RuntimeException("Carrot event dispatcher configuration file ({$filePath}) does not exist.");
        }
        
        $loadFile = $this->loadFileFunction;
        $loadFile($filePath, array('dispatcher' => $dispatcher));
        $dispatcher->setContainer($this->container);
        $this->dispatcher = $dispatcher;
        $this->dispatcher->notifyListeners('Carrot.Core.EventDispatcherReady');
    }
    
    /**
     * Load the routing configuration object and instantiate Carrot's
     * Router object using the container.
     *
     * The Router object is instantiated using the container so that
     * users will be able to access it by configuring dependency
     * injection appropriately.
     *
     * @see initialize()
     *
     */
    protected function initializeRouter()
    {
        $routingConfig = $this->container->get(new Reference(
            'Carrot\Routing\Config\ConfigInterface'
        ));
        
        $filePath = $this->config['files']['routingConfig'];
        
        if (!file_exists($filePath))
        {
            throw new RuntimeException("Carrot routing configuration file ({$filePath}) does not exist.");
        }
        
        $loadFile = $this->loadFileFunction;
        $loadFile($filePath, array('config' => $routingConfig));
        $this->containerConfig->addInjector(new ConstructorInjector(
            new Reference('Carrot\Routing\Router'),
            array(
                $routingConfig,
                new Reference('Carrot\Request\DefaultRequest'),
                $this->container,
                $this->config['base'],
                $this->config['defaults']['HTTPURI']
            )
        ));
        
        $this->router = $this->container->get(new Reference(
            'Carrot\Routing\RouterInterface'
        ));
    }
    
    /**
     * Run the destination method, we use ugly switch statements in
     * order to save some performance.
     * 
     * @see run()
     * @param mixed $object The object that contains the destination
     *        method to be run.
     * @param string $methodName The name of the method to be run.
     * @param array $args The arguments to be used when running the
     *        method.
     *
     */
    protected function runDestinationMethod($object, $methodName, array $args)
    {
        $count = count($args);
        
        switch ($count)
        {
            case 0:
                return $object->$methodName();
            break;
            case 1:
                return $object->$methodName($args[0]);
            break;
            case 2:
                return $object->$methodName($args[0], $args[1]);
            break;
            case 3:
                return $object->$methodName($args[0], $args[1], $args[2]);
            break;
            case 4:
                return $object->$methodName($args[0], $args[1], $args[2], $args[3]);
            break;
            case 5:
                return $object->$methodName($args[0], $args[1], $args[2], $args[3], $args[4]);
            break;
            default:
                $reflectionMethod = new ReflectionMethod($object, $methodName);
                return $reflectionMethod->invokeArgs($object, $args);
            break;
        }
    }
    
    /**
     * Converts a variable into a default ResponseInterface
     * implementation, depending on the nature of the request.
     * 
     * @see run()
     * @param mixed $var The variable to be converted.
     *
     */
    protected function convertToResponseObject($var)
    {
        try
        {
            $var = (string) $var;
        }
        catch (Exception $exception)
        {
            $type = is_object($var) ? get_class($var) : gettype($var);
            throw new RuntimeException("Application error in running destination method. The returned value is not an instance of Carrot\Response\ResponseInterface and conversion effort failed.");
        }
        
        $request = $this->container->get(new Reference(
            'Carrot\Request\RequestInterface'
        ));
        
        if ($request->isCLI())
        {
            return new CLIResponse($var);
        }
        
        return new HTTPResponse($var);
    }
}