<?php
session_start();
$_SESSION['csrf'] = isset($_SESSION['csrf']) ? $_SESSION['csrf'] : sha1(microtime());

require $_SERVER['DOCUMENT_ROOT'] . '/configs.php';
require $_SERVER['DOCUMENT_ROOT'] . '/routes.php';


// Set up default controller.
$controller_name = $routes['default'];
$request_pieces = '';

// Break URI into an array.
$uri_pieces = explode("/", trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), "/"));
// Run through URI fragments looking for a match
while ($uri_pieces) {
    $compare_key = strtolower(implode("/", $uri_pieces));
    // We're done if $compare_key is zero length
    if (strlen($compare_key) == 0) break;
    
    if (array_key_exists($compare_key, $routes)) {
        // If URI segment is found in $routes array (routes.php), set controller and stop looking 
        $controller_name = $routes[$compare_key];
        break;
    } else {
        // If URI segment is not found reduce the URI by one segment
        $request_pieces[] = array_pop($uri_pieces);
    }
}

// Turn over control to the controller...
$controller = new $controller_name($dependencies);
$controller->processRequest(@array_reverse($request_pieces));
