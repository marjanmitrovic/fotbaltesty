<?php
declare(strict_types=1);

namespace App\Router;

use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    public static function createRouter(): RouteList
    {
        $router = new RouteList();
        $router->addRoute('login', 'Sign:in');
        $router->addRoute('logout', 'Sign:out');
        $router->addRoute('dashboard', 'Dashboard:default');
        $router->addRoute('test', 'Test:default');
        $router->addRoute('test/<id>', 'Test:run');
        $router->addRoute('test/<id>/vysledek', 'Test:result');
        $router->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');
        return $router;
    }
}
