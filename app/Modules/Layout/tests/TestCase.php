<?php

namespace App\Modules\Layout\tests;




use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

abstract class TestCase extends \Tests\TestCase
{
    use InteractsWithViews;

    protected function setUp():void
    {
     //   $this->loadViewsFrom(__DIR__ . '/../Views', 'layout');
        parent::setUp();

    }
}
