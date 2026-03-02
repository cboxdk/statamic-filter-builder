<?php

namespace Cbox\FilterBuilder\Tests;

use Cbox\FilterBuilder\ServiceProvider;
use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
    }
}
