<?php

declare(strict_types=1);

use KraftySprouts\AdPlacr\AdPlacr;
use Teme\Contracts\Insertions\InsertionContributor;
use Teme\Contracts\Plugins\Plugin;
use Teme\Contracts\Settings\SettingsReader;

/**
 * A settings reader that answers from arrays, as Teme's does from the saved values and declared defaults.
 *
 * @param  array<string, array<string, mixed>>  $groups
 */
function readerWith(array $groups, bool $throws = false): SettingsReader
{
    return new class($groups, $throws) implements SettingsReader
    {
        /** @param array<string, array<string, mixed>> $groups */
        public function __construct(private array $groups, private bool $throws) {}

        public function group(string $group): array
        {
            if ($this->throws) {
                throw new RuntimeException('settings are unavailable');
            }

            return $this->groups[$group] ?? [];
        }

        public function get(string $group, string $key): mixed
        {
            return $this->group($group)[$key] ?? null;
        }

        public function string(string $group, string $key): string
        {
            return (string) $this->get($group, $key);
        }

        public function int(string $group, string $key): int
        {
            return (int) $this->get($group, $key);
        }

        public function bool(string $group, string $key): bool
        {
            return (bool) $this->get($group, $key);
        }

        public function stringList(string $group, string $key): array
        {
            return (array) $this->get($group, $key);
        }
    };
}

it('is a Teme plugin and an insertion contributor that speaks for the plugin', function (): void {
    $plugin = new AdPlacr(readerWith([]));

    expect($plugin)->toBeInstanceOf(Plugin::class)
        ->and($plugin)->toBeInstanceOf(InsertionContributor::class)
        ->and($plugin->precedence())->toBe('plugin');
});

it('draws the ads saved in its settings group', function (): void {
    $plugin = new AdPlacr(readerWith(['plugin.adplacr' => slot1(['code' => '<ins>hello</ins>'])]));
    $insertions = $plugin->insertions(article());

    expect($insertions)->toHaveCount(1)->and($insertions[0]->html)->toContain('<ins>hello</ins>');
});

it('draws nothing before anything is set up', function (): void {
    expect((new AdPlacr(readerWith(['plugin.adplacr' => savedDefaults()])))->insertions(article()))->toBe([]);
});

it('reads a schedule in the time zone of the site', function (): void {
    $future = (new DateTimeImmutable('+2 days', new DateTimeZone('UTC')))->format('Y-m-d H:i');
    $groups = ['plugin.adplacr' => slot1(['start' => $future]), 'general' => ['timezone' => 'Asia/Tokyo']];

    expect((new AdPlacr(readerWith($groups)))->insertions(article()))->toBe([]);

    $past = (new DateTimeImmutable('-2 days', new DateTimeZone('UTC')))->format('Y-m-d H:i');
    $groups['plugin.adplacr'] = slot1(['start' => $past]);

    expect((new AdPlacr(readerWith($groups)))->insertions(article()))->toHaveCount(1);
});

it('treats a time zone it does not know as UTC instead of failing', function (): void {
    $groups = ['plugin.adplacr' => slot1(), 'general' => ['timezone' => 'Mars/Olympus']];

    expect((new AdPlacr(readerWith($groups)))->insertions(article()))->toHaveCount(1);
});

it('never breaks the page: if the settings cannot be read it draws nothing', function (): void {
    expect((new AdPlacr(readerWith([], throws: true)))->insertions(article()))->toBe([]);
});
