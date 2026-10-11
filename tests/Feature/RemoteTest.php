<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Modules\Remote\Expose\RemoteExpose;
use Zofe\Rapyd\Modules\Remote\Support\Remote;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

/** The test app is provider and consumer at once: it exposes companies and reads them back as source "self". */
class RemoteTest extends TestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Company $company;

    protected function defineEnvironment($app)
    {
        $app['config']->set('rapyd.remote.enabled', true);
        $app['config']->set('rapyd.remote.callers', ['consumer' => 'secret']);
        $app['config']->set('rapyd.remote.sources', ['self' => ['url' => 'http://self.test', 'token' => 'secret']]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        RemoteExpose::resource('companies', Company::class)
            ->searchable(['business_name'])
            ->fields(['business_name', 'status'])
            ->label(fn ($c) => $c->business_name)
            ->scope(fn ($query, $user) => $user ? $query : $query->whereRaw('1 = 0'));   // anonymous callers see nothing

        $this->user = User::create(['name' => 'Op', 'email' => 'op@example.com', 'password' => 'x']);
        $this->company = Company::create(['business_name' => 'Acme', 'status' => 'active']);
    }

    /** Route the consumer's HTTP calls to this same app. */
    protected function fakeSourceIsThisApp(): void
    {
        Http::fake(function (Request $request) {
            $url = parse_url($request->url());
            $response = $this->withHeaders([
                'Authorization' => $request->header('Authorization')[0] ?? '',
                'X-Remote-User' => $request->header('X-Remote-User')[0] ?? '',
            ])->getJson($url['path'] . '?' . ($url['query'] ?? ''));

            return Http::response($response->json(), $response->status());
        });
    }

    public function test_the_endpoint_needs_a_valid_token()
    {
        $this->getJson('/_remote/companies')->assertUnauthorized();
        $this->withToken('wrong')->getJson('/_remote/companies')->assertUnauthorized();
        $this->withToken('secret')->getJson('/_remote/invoices')->assertNotFound();
    }

    public function test_the_scope_gets_the_user_the_caller_acts_for()
    {
        $this->withToken('secret')->getJson('/_remote/companies?q=acm')->assertJsonCount(0, 'data');

        $this->withToken('secret')->withHeader('X-Remote-User', $this->user->id)
            ->getJson('/_remote/companies?q=acm')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Acme')
            ->assertJsonPath('data.0.attributes.status', 'active');
    }

    public function test_find_fetches_once_then_reads_the_cache_and_refetches_after_the_ttl()
    {
        $this->actingAs($this->user);
        $this->fakeSourceIsThisApp();

        $this->assertSame('Acme', Remote::find('self.companies', $this->company->id)?->label);
        $this->assertSame('Acme', Remote::find('self.companies', $this->company->id)?->label);
        Http::assertSentCount(1);

        $this->company->update(['business_name' => 'Acme Renamed']);
        $this->travel(config('rapyd.remote.ttl') + 1)->seconds();

        $this->assertSame('Acme Renamed', Remote::find('self.companies', $this->company->id)?->label);
        Http::assertSentCount(2);
    }

    public function test_when_the_source_is_down_the_last_copy_is_used()
    {
        $this->actingAs($this->user);
        $this->fakeSourceIsThisApp();
        Remote::find('self.companies', $this->company->id);

        $this->travel(config('rapyd.remote.ttl') + 1)->seconds();
        Http::fake(fn () => Http::response(null, 503));

        $this->assertSame('Acme', Remote::find('self.companies', $this->company->id)?->label);
    }

    public function test_the_lookup_endpoint_and_the_label_component()
    {
        $this->actingAs($this->user);
        Http::fake(['self.test/*' => Http::response(['data' => [
            ['id' => '7', 'label' => 'Acme', 'attributes' => []],
        ]])]);

        $this->getJson(route('remote.lookup', ['self', 'companies']) . '?q=ac')
            ->assertOk()
            ->assertExactJson([['id' => '7', 'title' => 'Acme']]);

        $this->blade('<x-remote::label resource="self.companies" :id="7" />')->assertSee('Acme');
    }
}
