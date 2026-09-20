<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Zofe\Rapyd\Localization\Locales;
use Zofe\Rapyd\Localization\SetLocale;
use Zofe\Rapyd\Tests\TestCase;

/**
 * Languages: the enabled locales, the URL prefix, the helpers, the JSON catalogues of
 * the package, the SetLocale middleware (URL, session, switcher, Livewire requests).
 */
class LocalizationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.locale', 'en');
        $app['config']->set('rapyd.locale', 'en');
        $app['config']->set('rapyd.locales', ['en', 'it', 'fr']);
    }

    protected function defineRoutes($router): void
    {
        parent::defineRoutes($router);
        // a page in every language: the prefix of the current request, like the module routes
        foreach (['', 'it', 'fr'] as $prefix) {
            $router->middleware('web')->prefix($prefix)->get('/hello', fn () => __('Back') . '|' . app()->getLocale() . '|' . route_lang('hello'))->name($prefix ? "hello.{$prefix}" : 'hello');
        }
        $router->middleware('web')->post('/livewire/update', fn () => app()->getLocale())->name('livewire.update');
    }

    public function test_the_enabled_locales_and_their_prefixes()
    {
        $locales = app(Locales::class);

        $this->assertSame(['en', 'it', 'fr'], $locales->all());
        $this->assertSame('en', $locales->default());
        $this->assertTrue($locales->enabled());
        $this->assertSame('', $locales->prefix('en'));
        $this->assertSame('it', $locales->prefix('it'));
        $this->assertSame('it', $locales->fromRequest(Request::create('/it/companies')));
        $this->assertNull($locales->fromRequest(Request::create('/companies')));
        $this->assertNull($locales->fromRequest(Request::create('/de/companies')), 'not enabled');
        $this->assertSame('Italiano', $locales->name('it'));
        $this->assertSame('fr', $locales->fromBrowser(Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'fr-FR,fr;q=0.9,de;q=0.5'])));
        $this->assertNull($locales->fromBrowser(Request::create('/', 'GET', [], [], [], ['HTTP_ACCEPT_LANGUAGE' => 'de-DE'])));
    }

    public function test_one_locale_means_nothing_is_enabled()
    {
        config(['rapyd.locales' => ['en']]);
        $this->assertFalse(app(Locales::class)->enabled());
        $this->assertSame(['en'], app(Locales::class)->all());
    }

    public function test_route_lang_and_url_lang_build_the_url_of_the_current_language()
    {
        $this->assertSame(url('/hello'), route_lang('hello'));
        app()->setLocale('it');
        $this->assertSame(url('/it/hello'), route_lang('hello'));
        $this->assertSame(url('/fr/hello'), route_lang('hello', null, true, 'fr'));

        $this->get('/it/hello');
        $this->assertSame('/hello', url_lang('en'));
        $this->assertSame('/fr/hello', url_lang('fr'));
        $this->assertSame('/fr/hello?clang=1', url_lang('fr', true));
    }

    public function test_the_package_catalogues_translate_the_phrases()
    {
        app()->setLocale('it');
        $this->assertSame('Indietro', __('Back'));
        $this->assertSame('Ragione sociale', __('Business Name'));
        app()->setLocale('fr');
        $this->assertSame('Retour', __('Back'));
        app()->setLocale('en');
        $this->assertSame('Back', __('Back'));
    }

    public function test_the_url_prefix_sets_the_language_and_is_remembered()
    {
        $this->get('/it/hello')->assertOk()->assertSee('Indietro|it|' . url('/it/hello'));
        $this->assertSame('it', session(Locales::SESSION_KEY));

        // the Livewire update endpoint has no prefix: it takes the language of the page
        $this->post('/livewire/update')->assertOk()->assertSee('it');

        // a default-language page asked while the choice is italian: sent to the italian one
        $this->get('/hello')->assertRedirect('/it/hello');

        // the switcher back to english: remembered, no more redirects
        $this->get('/hello?clang=1')->assertOk()->assertSee('Back|en|');
        $this->assertSame('en', session(Locales::SESSION_KEY));
        $this->get('/hello')->assertOk();
    }

    public function test_the_browser_language_is_used_once_and_a_single_locale_never_redirects()
    {
        $this->withHeaders(['Accept-Language' => 'fr-FR,fr;q=0.9'])->get('/hello')->assertRedirect('/fr/hello');

        config(['rapyd.locales' => ['en']]);
        $this->withHeaders(['Accept-Language' => 'fr-FR'])->get('/hello')->assertOk()->assertSee('Back|en|');
    }

    public function test_the_middleware_is_in_the_web_group_and_the_switcher_is_in_the_layouts()
    {
        $web = app(\Illuminate\Contracts\Http\Kernel::class)->getMiddlewareGroups()['web'] ?? [];
        $this->assertContains(SetLocale::class, $web);

        app()->setLocale('it');
        $html = view('layout::includes.locale_switcher')->render();
        $this->assertStringContainsString('Italiano', $html);
        $this->assertStringContainsString('hreflang="fr"', $html);
        $this->assertStringContainsString('clang=1', $html);
    }
}
