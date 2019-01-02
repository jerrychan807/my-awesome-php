<?php
/**
 * Created by PhpStorm.
 * User: chanjerry
 * Date: 2019/1/2
 * Time: 10:38 PM
 */

require 'vendor/autoload.php';

$config = require 'config.php';

$app = new \Slim\Slim($config);

// Main Intro Closure
$execute = function($path = array()) use($app)
{
    $baseUri = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));

    $template = implode('/', $path);

    $template = $template ?: 'index';

    $data = array(
        'path' => $path,

        'uri' => array('base' => $baseUri),
        // Add helper to view
        'helper' => new DI\Helper()
    );

    $app->render($template . '.twig', $data);
};

// For Home
$app->get('/', $execute);

// For inner pages
$app->get('/:path+', $execute);

$app->run();