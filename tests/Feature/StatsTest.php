<?php

declare(strict_types=1);

use App\Domain\Plugins\PluginLoader;
use App\Domain\Plugins\PluginRegistry;
use App\Domain\Settings\Operations\Input\UpdateSettingsInput;
use App\Domain\Settings\Operations\UpdateSettings;
use App\Domain\Support\Security\Elevation;
use App\Domain\Support\Security\OperatorElevation;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use KraftySprouts\AdPlacr\Ads\Placer;
use KraftySprouts\AdPlacr\Stats\PruneHandler;
use Teme\Contracts\Jobs\JobContext;
use Teme\Contracts\Settings\SettingsReader;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    config(['teme.version' => '0.8.0-alpha.1']);
    app()->instance(Elevation::class, new OperatorElevation);
    config(['teme.plugin_paths' => [realpath(__DIR__.'/../..')]]);
    app(PluginRegistry::class)->flush();

    $status = Artisan::call('teme:plugins:enable', ['name' => 'krafty-sprouts/adplacr', '--agree' => true]);
    expect($status)->toBe(0, Artisan::output());
    app(PluginLoader::class)->load();

    $this->admin = User::factory()->create()->assignRole('administrator');
});

/**
 * @param  array<string, mixed>  $values
 */
function saveStatsSettings(User $admin, array $values): void
{
    $version = (int) DB::table('settings')->where('group_key', 'plugin.adplacr')->value('version');
    app(UpdateSettings::class)->execute(new UpdateSettingsInput('plugin.adplacr', $values, [], [], $version, (string) $admin->id));
}

/**
 * A browser's beacon, as the plugin's script sends it.
 *
 * @param  array<string, string>  $headers
 */
function beacon(string $body, array $headers = ['X-Adplacr' => '1']): TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json', 'REMOTE_ADDR' => '203.0.113.9'];

    foreach ($headers as $name => $value) {
        $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
    }

    $request = Request::create('/adplacr/track', 'POST', [], [], [], $server, $body);

    return TestResponse::fromBaseResponse(app(HttpKernel::class)->handle($request), $request);
}

/**
 * @return array{impressions: int, clicks: int} summed over every day for this slot and version
 */
function counted(int $slot, string $version): array
{
    $row = DB::table('adplacr_daily')->where('slot', $slot)->where('version', $version)
        ->selectRaw('coalesce(sum(impressions), 0) as impressions, coalesce(sum(clicks), 0) as clicks')->first();

    return ['impressions' => (int) $row->impressions, 'clicks' => (int) $row->clicks];
}

/**
 * @param  array<string, mixed>  $more
 */
function adOn(User $admin, array $more = []): void
{
    saveStatsSettings($admin, $more + ['analytics_enabled' => true, 'ad1_status' => 'active', 'ad1_code' => '<ins>x</ins>']);
}

it('counts an impression and a click for an ad that exists', function (): void {
    adOn($this->admin);

    beacon('{"event":"impression","ad":1,"version":"1a"}')->assertNoContent();
    beacon('{"event":"impression","ad":1,"version":"1a"}')->assertNoContent();
    beacon('{"event":"click","ad":1,"version":"1a"}')->assertNoContent();

    expect(counted(1, '1a'))->toBe(['impressions' => 2, 'clicks' => 1])
        ->and(DB::table('adplacr_daily')->count())->toBe(1);
});

it('keeps a separate count for each version of an ad', function (): void {
    adOn($this->admin, ['ad1_code_b' => '<ins>b</ins>']);

    beacon('{"event":"impression","ad":1,"version":"1a"}');
    beacon('{"event":"impression","ad":1,"version":"1b"}');
    beacon('{"event":"impression","ad":1,"version":"1b"}');

    expect(counted(1, '1a')['impressions'])->toBe(1)->and(counted(1, '1b')['impressions'])->toBe(2);
});

it('keeps its list of countable ad versions in step with the saved settings', function (): void {
    $live = fn (): array => DB::table('adplacr_live')->orderBy('slot')->orderBy('version')->pluck('version')->all();

    expect($live())->toBe([]);

    adOn($this->admin, ['ad1_code_b' => '<ins>b</ins>', 'ad2_status' => 'active', 'ad2_code' => '<ins>2</ins>']);
    expect($live())->toBe(['1a', '1b', '2a']);

    saveStatsSettings($this->admin, ['ad1_status' => 'paused']);
    expect($live())->toBe(['2a']);

    saveStatsSettings($this->admin, ['analytics_enabled' => false]);
    expect($live())->toBe([]);
});

it('counts nothing while statistics are off, which is the default', function (): void {
    saveStatsSettings($this->admin, ['ad1_status' => 'active', 'ad1_code' => '<ins>x</ins>']);

    beacon('{"event":"impression","ad":1,"version":"1a"}')->assertNoContent();

    expect(DB::table('adplacr_daily')->count())->toBe(0);
});

it('counts nothing for an ad or version that is not set up, so a stranger cannot make up rows', function (): void {
    adOn($this->admin);

    beacon('{"event":"impression","ad":2,"version":"2a"}')->assertNoContent();
    beacon('{"event":"impression","ad":1,"version":"1b"}')->assertNoContent();

    expect(DB::table('adplacr_daily')->count())->toBe(0);
});

it('refuses a body that is not a beacon, and a request without the plugin header', function (): void {
    adOn($this->admin);

    beacon('{"event":"view","ad":1,"version":"1a"}')->assertStatus(400);
    beacon('{"event":"impression","ad":1,"version":"1a"}', headers: [])->assertStatus(401);

    expect(DB::table('adplacr_daily')->count())->toBe(0);
});

it('serves the tracking script from the plugin\'s own address', function (): void {
    $request = Request::create('/adplacr/tracking.js', 'GET');
    $response = TestResponse::fromBaseResponse(app(HttpKernel::class)->handle($request), $request);

    $response->assertOk();

    expect((string) $response->headers->get('Content-Type'))->toContain('javascript')
        ->and($response->getContent())->toContain('adplacr')->toContain('IntersectionObserver')->toContain('/adplacr/track');
});

it('adds the script to the page once, in the first ad, only while statistics are on', function (): void {
    $values = ['ad2_status' => 'active', 'ad2_code' => '<ins>two</ins>'] + slot1();
    $script = '<script src="/adplacr/tracking.js" defer></script>';

    $on = Placer::insertionsFor(['analytics_enabled' => true] + $values, new DateTimeZone('UTC'), article(), 1_800_000_000);
    $off = Placer::insertionsFor(['analytics_enabled' => false] + $values, new DateTimeZone('UTC'), article(), 1_800_000_000);

    expect($on[0]->html)->toContain($script)
        ->and($on[1]->html)->not->toContain('tracking.js')
        ->and($off[0]->html.$off[1]->html)->not->toContain('tracking.js');
});

it('prunes counts older than the retention window and keeps the rest', function (): void {
    adOn($this->admin, ['retention_days' => '30']);
    $today = now()->utc()->startOfDay();

    DB::table('adplacr_daily')->insert([
        ['day' => $today->copy()->subDays(45)->toDateString(), 'slot' => 1, 'version' => '1a', 'impressions' => 9, 'clicks' => 1],
        ['day' => $today->copy()->subDays(31)->toDateString(), 'slot' => 1, 'version' => '1a', 'impressions' => 8, 'clicks' => 1],
        ['day' => $today->copy()->subDays(29)->toDateString(), 'slot' => 1, 'version' => '1a', 'impressions' => 7, 'clicks' => 1],
        ['day' => $today->toDateString(), 'slot' => 1, 'version' => '1a', 'impressions' => 6, 'clicks' => 1],
    ]);

    (new PruneHandler(app(SettingsReader::class)))->handle(new class implements JobContext
    {
        public function payload(): array
        {
            return [];
        }

        public function attempt(): int
        {
            return 1;
        }

        public function progress(int $done, int $total, string $message = ''): void {}
    });

    expect(DB::table('adplacr_daily')->orderBy('day')->pluck('impressions')->map(fn ($n): int => (int) $n)->all())->toBe([7, 6]);
});
