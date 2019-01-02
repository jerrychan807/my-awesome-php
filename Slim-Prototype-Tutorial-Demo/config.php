<?php
/**
 * Created by PhpStorm.
 * User: chanjerry
 * Date: 2019/1/2
 * Time: 11:21 PM
 */

define('SLIM_ROOT', __DIR__);

return array(
    'debug' => true,
    'mode' => 'develoment',
    'view' => new View\Twig(),
    'templates.path' => __DIR__. '/templates'
);