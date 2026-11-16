<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

use DateTimeImmutable;
use DateTimeZone;
use KraftySprouts\AdPlacr\Settings\Declaration;
use Throwable;

/**
 * Reads the plugin's settings group and returns the ads that can be shown. Anything that cannot
 * be shown (Paused, no code, unknown position) is left out, so a half-filled slot draws nothing.
 */
final class Ads
{
    private const array DEVICES = ['desktop', 'tablet', 'mobile'];

    private const array ALIGNMENTS = ['none', 'left', 'center', 'right'];

    /**
     * @param  array<string, mixed>  $values  the group's values, as SettingsReader::group() answers
     *
     * @return list<Ad> in slot order
     */
    public static function fromSettings(array $values, DateTimeZone $siteZone): array
    {
        $ads = [];

        for ($n = 1; $n <= Declaration::SLOTS; $n++) {
            $ad = self::slot($n, $values, $siteZone);

            if ($ad !== null) {
                $ads[] = $ad;
            }
        }

        return $ads;
    }

    /**
     * @param  array<string, mixed>  $v
     */
    private static function slot(int $n, array $v, DateTimeZone $zone): ?Ad
    {
        $get = static fn (string $field): mixed => $v["ad{$n}_{$field}"] ?? null;

        $position = self::text($get('position'));
        $versions = self::versions($n, $get);

        if (self::text($get('status')) !== 'active' || ! array_key_exists($position, Declaration::POSITIONS) || $versions === []) {
            return null;
        }

        $alignment = self::text($get('alignment'));
        $devices = array_values(array_intersect(self::DEVICES, self::list($get('devices'))));

        return new Ad(
            slot: $n,
            name: self::text($get('name')),
            position: $position,
            paragraph: self::number($get('paragraph'), 1, 1, 100),
            repeatMax: self::number($get('repeat_max'), 3, 1, 10),
            headingLevel: self::number($get('heading_level'), 2, 1, 6),
            headingNth: self::number($get('heading_nth'), 1, 1, 50),
            versions: $versions,
            alignment: in_array($alignment, self::ALIGNMENTS, true) ? $alignment : 'none',
            devices: $devices === [] ? self::DEVICES : $devices,
            types: self::csv($get('types')),
            terms: self::terms($get('terms')),
            languages: self::csv($get('languages')),
            start: self::time($get('start'), $zone),
            end: self::time($get('end'), $zone),
        );
    }

    /**
     * @param  callable(string): mixed  $get
     *
     * @return list<Version>
     */
    private static function versions(int $n, callable $get): array
    {
        $versions = [];

        foreach (['a' => ['code', 'mobile_code', 'weight_a'], 'b' => ['code_b', 'mobile_code_b', 'weight_b']] as $letter => [$code, $mobile, $weight]) {
            $code = self::text($get($code));
            $mobile = self::text($get($mobile));

            if (trim($code) === '' && trim($mobile) === '') {
                continue;
            }

            $versions[] = new Version($n.$letter, $code, $mobile, self::number($get($weight), 1, 1, 100));
        }

        return $versions;
    }

    private static function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    /**
     * @return list<string>
     */
    private static function list(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, is_string(...))) : [];
    }

    private static function number(mixed $value, int $default, int $min, int $max): int
    {
        $number = is_int($value) || (is_string($value) && is_numeric($value)) ? (int) $value : $default;

        return max($min, min($max, $number));
    }

    /**
     * @return list<string> lower-cased, trimmed, without empties or repeats
     */
    private static function csv(mixed $value): array
    {
        $items = array_map(static fn (string $item): string => strtolower(trim($item)), explode(',', self::text($value)));

        return array_values(array_unique(array_filter($items, static fn (string $item): bool => $item !== '')));
    }

    /**
     * @return array<string, list<string>> term ids by taxonomy, from `taxonomy:id` pairs
     */
    private static function terms(mixed $value): array
    {
        $terms = [];

        foreach (explode(',', self::text($value)) as $pair) {
            if (! str_contains($pair, ':')) {
                continue;
            }

            [$taxonomy, $id] = array_map(trim(...), explode(':', $pair, 2));
            $taxonomy = strtolower($taxonomy);

            if ($taxonomy === '' || $id === '' || in_array($id, $terms[$taxonomy] ?? [], true)) {
                continue;
            }

            $terms[$taxonomy][] = $id;
        }

        return $terms;
    }

    private static function time(mixed $value, DateTimeZone $zone): ?int
    {
        $text = trim(self::text($value));

        if ($text === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($text, $zone))->getTimestamp();
        } catch (Throwable) {
            return null;
        }
    }
}
