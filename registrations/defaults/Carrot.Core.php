<?php

/**
 * Dependency registration file for {\Carrot\Core}
 *
 * Registers the dependencies of Carrot framework's core classes.
 * This file is defines default dependencies between core classes
 * used by Carrot.
 * 
 * >> READ BEFORE EDITING THIS FILE <<
 * 
 * Do not edit this file directly, if you wish to change something,
 * duplicate this file and have your /registrations.php point to the
 * duplicated file. Edit the duplicated file instead. That way you
 * can keep this file as reference when editing.
 *
 */

/**
 * \Carrot\Core\FrontController@main
 * 
 * 
 * 
 */

$dic->register('\Carrot\Core\FrontController@main', function($dic)
{
    return new \Carrot\Core\FrontController
    (
        $dic->getInstance('\Carrot\Core\Router@shared'),
        $dic->getInstance('\Carrot\Core\ErrorHandler@shared'),
        $dic,
        dirname(__DIR__) . DIRECTORY_SEPARATOR . 'routes.php'
    ); 
});

/**
 * \Carrot\Core\Router@main
 * 
 * Carrot's default Router class. It doesn't use the Request and
 * Session class per se, it just passes them to the anonymous
 * function. You can replace the Request and Session object with
 * your own custom Request and Session class.
 *
 * @param array $params Routing parameters to be passed to the anonymous functions.
 * @param Destination $no_matching_route_destination Default destination to return when there is no matching route.
 * 
 */

$dic->register('\Carrot\Core\Router@shared', function($dic)
{   
    // Destination to go to when there is no matching route
    $no_matching_route_destination = new \Carrot\Core\Destination('\Carrot\Core\Controllers\NoMatchingRouteController@main', 'index');
    
    // Parameters to pass to the anonymous functions, get any object you want with DIC
    $params = array
    (
        'request' => $dic->getInstance('\Carrot\Core\Request@shared'),
        'session' => $dic->getInstance('\Carrot\Core\Session@shared')
    );
    
    return new \Carrot\Core\Router($params, $no_matching_route_destination);
});

/**
 * \Carrot\Core\ErrorHandler@shared
 * 
 * Carrot's default ErrorHandler class. You can change the templates
 * used by it by filling the appropriate parameter with an absolute
 * file path.
 *
 * Since there is no point of having more than one error handler class,
 * we save an instance of it as shared.
 *
 * @param string $server_protocol Either 'HTTPS 1.0' or 'HTTP 1.1', used to set the status code to 500.
 * @param bool $development_mode Optional. When set to TRUE, will use development error/exception templates, otherwise will use production error/exception templates.
 * @param string $error_template Optional. Absolute path to the production error template. Used when ErrorHandler::development_mode is FALSE.
 * @param string $exception_template Optional. Absolute path to the production uncaught exception template. Used when ErrorHandler::development_mode is FALSE. 
 * @param string $error_template_div Optional. Absolute path to the development error template. Used when ErrorHandler::development_mode is TRUE.
 * @param string $exception_template_div Optional. Absolute path to the development uncaught exception template. Used when ErrorHandler::development_mode is TRUE.
 *
 */

$dic->register('\Carrot\Core\ErrorHandler@shared', function($dic)
{   
    $error_handler = new \Carrot\Core\ErrorHandler
    (
        $dic->getInstance('\Carrot\Core\Request@shared')->getServer('SERVER_PROTOCOL'),
        TRUE
    );
    
    $dic->saveShared('\Carrot\Core\ErrorHandler@shared', $error_handler);
    return $error_handler;
});

/**
 * \Carrot\Core\Request@shared
 *
 * Carrot's default Request class, to be shared across the framework. Default
 * configuration doesn't include $base_path parameter, it is guessed automatically
 * by the framework. However for security reasons, it is recommended that you 
 * hard code the base path.
 *
 * Base path is the relative path from server root to the folder where the front
 * controller is located. If the front controller is in the server root, it simply
 * returns '/'.
 *
 * We save its instance as shared since there is no point in creating two
 * different instances.
 *
 * @param array $server $_SERVER variable.
 * @param array $get $_GET variable.
 * @param array $post $_POST variable.
 * @param array $files $_FILES variable.
 * @param array $cookie $_COOKIE variable.
 * @param array $request $_REQUEST variable.
 * @param array $env $_ENV variable.
 * @param string $base_path Optional. Base path, with starting and trailing slash.
 *
 */
 
$dic->register('\Carrot\Core\Request@shared', function($dic)
{
    $object = new \Carrot\Core\Request
    (
        $_SERVER,
        $_GET,
        $_POST,
        $_FILES,
        $_COOKIE,
        $_REQUEST,
        $_ENV
    );
    
    $dic->saveShared('\Carrot\Core\Request@shared', $object);
    
    return $object;
});

/**
 * \Carrot\Core\Session@shared
 * 
 * Carrot's default Session wrapper. By using this class instead of
 * accessing $_SESSION directly, your classes will be easier to
 * test.
 * 
 * We save session as shared since every change to a session must
 * be reflected on the entire thread request.
 *
 */

$dic->register('\Carrot\Core\Session@shared', function($dic)
{
    $session = new \Carrot\Core\Session();
    $dic->saveShared('\Carrot\Core\Session@shared', $session);
    return $session;
});

/**
 * \Carrot\Core\Response@main
 * 
 * Carrot's default Response class. It implements ResponseInterface so
 * you can use it as a return value to the front controller. The controller
 * has the responsibility to build the Response, so it is not shared
 * as it is only needed by the controller.
 *
 * @param string $server_protocol Used when setting the response code, either 'HTTP/1.0' or 'HTTP/1.1'.
 *
 */

$dic->register('\Carrot\Core\Response@main', function($dic)
{
    return new \Carrot\Core\Response
    (
        $dic->getInstance('\Carrot\Core\Request@shared')->getServer('SERVER_PROTOCOL')
    );
});

/**
 * \Carrot\Core\Controllers\NoMatchingRouteController@main
 *
 * Carrot's default 'Http 404 Page Not Found' page. Injected by default to the
 * Router as the default destination to go to when there is no matching route.
 * 
 * @param Response $response Instance of \Carrot\Core\Response
 *
 */

$dic->register('\Carrot\Core\Controllers\NoMatchingRouteController@main', function($dic)
{
    return new \Carrot\Core\Controllers\NoMatchingRouteController
    (
        $dic->getInstance('\Carrot\Core\Response@main')
    );
});

/**
 * \Carrot\Core\Controllers\WelcomeController@main
 *
 * Carrot's controller that displays the welcome page.
 * 
 * @param Request $request Instance of \Carrot\Core\Request.
 * @param Response $response Instance of \Carrot\Core\Response.
 * @param string $vendors_directory Path to the DIC root directory (default is /vendors), without trailing slash.
 *
 */

$dic->register('\Carrot\Core\Controllers\WelcomeController@main', function($dic)
{
    return new \Carrot\Core\Controllers\WelcomeController
    (
        $dic->getInstance('\Carrot\Core\Request@shared'),
        $dic->getInstance('\Carrot\Core\Response@main'),
        dirname((__DIR__)) . DIRECTORY_SEPARATOR . 'vendors'
    );
});