<?php

declare(strict_types=1);

use App\Domain\Contents\Operations\CreateDraft;
use App\Domain\Contents\Operations\Input\CreateDraftInput;
use App\Domain\Contents\Operations\Input\PublishRevisionInput;
use App\Domain\Contents\Operations\Input\SaveDraftInput;
use App\Domain\Contents\Operations\PublishRevision;
use App\Domain\Contents\Operations\SaveDraft;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** The built-in "No inserted content" field. Saves take a field's ID, not its handle `insertions_off` (FINDINGS F-14). */
const INSERTIONS_OFF = '01K5Z0F1ED00000000000000XF';

/** The plugin's folder, which holds teme-plugin.json. */
const PLUGIN = __DIR__.'/../..';

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    config(['teme.version' => '0.8.0-alpha.1']);
    app()->instance(Elevation::class, new OperatorElevation);

    config(['teme.plugin_paths' => [realpath(PLUGIN)]]);
    app(PluginRegistry::class)->flush();

    $status = Artisan::call('teme:plugins:enable', ['name' => 'krafty-sprouts/adplacr', '--agree' => true]);
    expect($status)->toBe(0, Artisan::output());
    app(PluginLoader::class)->load();

    $this->editor = User::factory()->create()->assignRole('editor');
    $this->admin = User::factory()->create()->assignRole('administrator');
});

/**
 * Saves the plugin's settings the way the admin does, through Teme's own Operation.
 *
 * @param  array<string, mixed>  $values
 */
function saveAdSettings(User $admin, array $values): void
{
    $version = (int) DB::table('settings')->where('group_key', 'plugin.adplacr')->value('version');

    app(UpdateSettings::class)->execute(new UpdateSettingsInput('plugin.adplacr', $values, [], [], $version, (string) $admin->id));
}

/**
 * A published article of six paragraphs, through Teme's real Operations.
 *
 * @param  array<string, mixed>  $fields
 */
function publishedArticle(User $author, string $title, array $fields = []): string
{
    $nodes = [];

    foreach (range(1, 6) as $i) {
        $nodes[] = ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => "Paragraph number {$i}."]]];
    }

    $content = app(CreateDraft::class)->execute(new CreateDraftInput('post', $title, ['type' => 'doc', 'content' => []], [], (string) $author->id));
    app(SaveDraft::class)->execute(new SaveDraftInput($content->id, $title, ['type' => 'doc', 'content' => $nodes], $fields, $content->working_revision_id, (string) $author->id, []));
    $content->refresh();
    app(PublishRevision::class)->execute(new PublishRevisionInput($content->id, $content->working_revision_id, (string) $author->id));

    return '/'.str($title)->slug().'/';
}

/** A GET of this exact path (the test client's own get() trims the trailing slash). */
function openPage(string $path): TestResponse
{
    $request = Request::create($path, 'GET');

    return TestResponse::fromBaseResponse(app(HttpKernel::class)->handle($request), $request);
}

it('is accepted by Teme: found, valid, enabled, with the settings it declares', function (): void {
    $found = app(PluginRegistry::class)->find('krafty-sprouts/adplacr');

    expect($found)->not->toBeNull()
        ->and($found->usable())->toBeTrue()
        ->and($found->problems)->toBe([]);

    // Teme checks every value against the declaration, so a save that works proves the group is registered.
    saveAdSettings($this->admin, ['ad1_status' => 'active', 'ad1_code' => '<ins>x</ins>']);

    expect(DB::table('settings')->where('group_key', 'plugin.adplacr')->exists())->toBeTrue();
});

it('draws an Active ad after the paragraph it was set to, inside the real article page', function (): void {
    saveAdSettings($this->admin, ['ad1_status' => 'active', 'ad1_position' => 'after_paragraph', 'ad1_paragraph' => 2, 'ad1_code' => '<ins class="adsbygoogle">AD-ONE</ins>']);
    $path = publishedArticle($this->editor, 'Ad after two');

    $html = openPage($path)->assertOk()->getContent();

    expect($html)->toContain('Paragraph number 2.</p><div id="adplacr-ad-1"')
        ->and($html)->toContain('<ins class="adsbygoogle">AD-ONE</ins>')
        ->and($html)->toContain('data-ad-id="1"');
});

it('draws nothing while the ad is Paused', function (): void {
    saveAdSettings($this->admin, ['ad1_status' => 'paused', 'ad1_code' => '<ins>HIDDEN</ins>']);
    $path = publishedArticle($this->editor, 'No ad here');

    expect(openPage($path)->assertOk()->getContent())->not->toContain('HIDDEN')->not->toContain('adplacr');
});

it('respects the built-in "No inserted content" switch on an article', function (): void {
    saveAdSettings($this->admin, ['ad1_status' => 'active', 'ad1_code' => '<ins>SWITCHED-OFF</ins>']);
    $path = publishedArticle($this->editor, 'Switched off', [INSERTIONS_OFF => true]);

    expect(openPage($path)->assertOk()->getContent())->not->toContain('SWITCHED-OFF');
});

it('keeps ad code that loads from an address and removes inline scripts, as Teme cleans insertions', function (): void {
    saveAdSettings($this->admin, ['ad1_status' => 'active', 'ad1_code' => '<script async src="https://ads.example.net/a.js"></script><script>window.bad=1</script><ins>K</ins>']);
    $path = publishedArticle($this->editor, 'Cleaned');

    $response = openPage($path)->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('src="https://ads.example.net/a.js"')
        ->and($html)->not->toContain('window.bad=1')
        ->and((string) $response->headers->get('Content-Security-Policy-Report-Only').$response->headers->get('Content-Security-Policy'))->toContain('https://ads.example.net');
});

it('puts several ads in their places in one article', function (): void {
    saveAdSettings($this->admin, [
        'ad1_status' => 'active', 'ad1_position' => 'before_content', 'ad1_code' => '<ins>TOP</ins>',
        'ad2_status' => 'active', 'ad2_position' => 'after_content', 'ad2_code' => '<ins>BOTTOM</ins>',
        'ad3_status' => 'active', 'ad3_position' => 'every_paragraphs', 'ad3_paragraph' => 3, 'ad3_repeat_max' => 5, 'ad3_code' => '<ins>EVERY</ins>',
    ]);
    $path = publishedArticle($this->editor, 'Many ads');

    $html = openPage($path)->assertOk()->getContent();

    expect(substr_count($html, '<ins>TOP</ins>'))->toBe(1)
        ->and(substr_count($html, '<ins>BOTTOM</ins>'))->toBe(1)
        ->and(substr_count($html, '<ins>EVERY</ins>'))->toBe(2)
        ->and(strpos($html, '<ins>TOP</ins>'))->toBeLessThan(strpos($html, 'Paragraph number 1.'))
        ->and(strpos($html, '<ins>BOTTOM</ins>'))->toBeGreaterThan(strpos($html, 'Paragraph number 6.'));
});
