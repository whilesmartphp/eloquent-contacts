<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Whilesmart\Contacts\Http\Controllers\ContactController;

class CustomContactController extends ContactController {}

class RouteCustomizationTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('contacts.controller', CustomContactController::class);
        $app['config']->set('contacts.route_actions', ['index', 'store']);
        $app['config']->set('contacts.route_write_middleware', ['contact-write']);
    }

    #[Test]
    public function it_configures_the_controller_actions_and_write_middleware(): void
    {
        $routes = $this->app['router']->getRoutes();
        $index = $routes->getByName('contacts.index');
        $store = $routes->getByName('contacts.store');

        $this->assertInstanceOf(Route::class, $index);
        $this->assertInstanceOf(Route::class, $store);
        $this->assertSame(CustomContactController::class.'@index', $index->getActionName());
        $this->assertContains('contact-write', $store->gatherMiddleware());
        $this->assertNull($routes->getByName('contacts.show'));
    }
}
