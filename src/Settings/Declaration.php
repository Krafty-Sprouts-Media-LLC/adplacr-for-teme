<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Settings;

/**
 * The plugin's settings as they are declared in teme-plugin.json: four global ones, then
 * the same set of fields for each ad slot. `build/manifest.php` writes this list into the
 * manifest, and a test keeps the two equal.
 */
final class Declaration
{
    /** How many ad slots the settings screen offers (DECISIONS D-05). */
    public const int SLOTS = 5;

    /** The secret setting the statistics address names as its verification secret (FINDINGS F-15). */
    public const string TRACKING_KEY = 'tracking_key';

    /** Position choices, by the value stored; the first is the default. */
    public const array POSITIONS = [
        'after_paragraph' => 'After paragraph number',
        'before_paragraph' => 'Before paragraph number',
        'before_content' => 'Before the article',
        'after_content' => 'After the article',
        'every_paragraphs' => 'After every few paragraphs',
        'before_heading' => 'Before a heading',
        'after_heading' => 'After a heading',
        'near_end' => 'Near the end (before the last paragraph)',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function settings(): array
    {
        $settings = [
            self::setting('mobile_breakpoint', 'number', 'Mobile breakpoint', 'Screens narrower than this many pixels get the mobile ad code. WordPress Ad Placr used 782.', [
                'default' => 782, 'min' => 200, 'max' => 1024, 'unit' => 'px',
            ]),
            self::setting('tablet_breakpoint', 'number', 'Tablet breakpoint', 'Screens wider than the mobile breakpoint and narrower than this are tablets. Keep it above the mobile value.', [
                'default' => 1024, 'min' => 201, 'max' => 1600, 'unit' => 'px',
            ]),
            self::setting('analytics_enabled', 'boolean', 'Count impressions and clicks', 'Off by default. When on, the plugin counts how often each ad is seen and clicked, per day. No personal information is kept.', [
                'default' => false,
            ]),
            self::setting('retention_days', 'select', 'Keep statistics for', 'Daily counts older than this are removed automatically.', [
                'default' => '90',
                'options' => ['30' => '30 days', '60' => '60 days', '90' => '90 days', '180' => '180 days', '365' => '365 days'],
            ]),
            self::setting(self::TRACKING_KEY, 'secret', 'Statistics key', 'Teme requires a key behind every public address that receives data. Ad Placr does not use its value; you can leave it empty.', []),
        ];

        for ($n = 1; $n <= self::SLOTS; $n++) {
            array_push($settings, ...self::slot($n));
        }

        return $settings;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function slot(int $n): array
    {
        $p = "ad{$n}_";
        $of = "Ad {$n}: ";

        return [
            self::setting($p.'name', 'text', $of.'name', 'A name only you see, so you can tell your ads apart.', ['default' => '', 'maxLength' => 80]),
            self::setting($p.'status', 'select', $of.'status', 'An ad is shown only while it is Active and has ad code.', [
                'default' => 'paused', 'options' => ['paused' => 'Paused', 'active' => 'Active'],
            ]),
            self::setting($p.'position', 'select', $of.'where it appears', 'Where in the article the ad is placed. Only paragraphs and headings at the top level of the article count, never ones inside a list, quote or table.', [
                'default' => 'after_paragraph', 'options' => self::POSITIONS,
            ]),
            self::setting($p.'paragraph', 'number', $of.'paragraph number', 'Used by "After paragraph number", "Before paragraph number" and "After every few paragraphs" (then it is the step).', [
                'default' => 1, 'min' => 1, 'max' => 100,
            ]),
            self::setting($p.'repeat_max', 'number', $of.'most repeats', 'Used by "After every few paragraphs": the most times the ad is repeated in one article. Teme never repeats more than 10 times.', [
                'default' => 3, 'min' => 1, 'max' => 10,
            ]),
            self::setting($p.'heading_level', 'number', $of.'heading level', 'Used by the heading locations: 2 means an h2 heading.', [
                'default' => 2, 'min' => 1, 'max' => 6,
            ]),
            self::setting($p.'heading_nth', 'number', $of.'which heading', 'Used by the heading locations: 1 means the first heading of that level.', [
                'default' => 1, 'min' => 1, 'max' => 50,
            ]),
            self::setting($p.'code', 'textarea', $of.'ad code', 'The code from your ad network. Scripts that load from a web address and iframes work; scripts written inline in the code are removed by Teme.', [
                'default' => '',
            ]),
            self::setting($p.'mobile_code', 'textarea', $of.'mobile ad code', 'Optional. Shown instead of the ad code on screens under the mobile breakpoint.', ['default' => '']),
            self::setting($p.'alignment', 'select', $of.'alignment', 'Where the ad sits in the column. "None" leaves the ad code as it is.', [
                'default' => 'none', 'options' => ['none' => 'None', 'left' => 'Left', 'center' => 'Center', 'right' => 'Right'],
            ]),
            self::setting($p.'devices', 'multiselect', $of.'show on', 'The screen sizes the ad is shown on. Choose none to show on all.', [
                'default' => ['desktop', 'tablet', 'mobile'],
                'options' => ['desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'],
            ]),
            self::setting($p.'types', 'text', $of.'content types', 'Show only in these content types, separated by commas, such as post, page. Leave empty to show in all.', [
                'default' => '', 'example' => 'post, page',
            ]),
            self::setting($p.'terms', 'text', $of.'categories and tags', 'Show only in articles that have one of these terms, as taxonomy:id separated by commas. Leave empty to show in all.', [
                'default' => '', 'example' => 'category:01H8XYZ, tag:01H8ABC',
            ]),
            self::setting($p.'languages', 'text', $of.'languages', 'Show only in articles in these languages, such as en, fr. Leave empty to show in all.', [
                'default' => '', 'example' => 'en, fr',
            ]),
            self::setting($p.'start', 'text', $of.'start', 'Do not show before this date and time, in the site\'s time zone. Leave empty to start at once.', [
                'default' => '', 'example' => '2026-12-01 09:00',
            ]),
            self::setting($p.'end', 'text', $of.'end', 'Do not show after this date and time, in the site\'s time zone. Leave empty to never end.', [
                'default' => '', 'example' => '2026-12-31 23:59',
            ]),
            self::setting($p.'code_b', 'textarea', $of.'version B ad code', 'Optional. A second version of the ad. Each time the page is drawn, one version is chosen by its weight.', ['default' => '']),
            self::setting($p.'mobile_code_b', 'textarea', $of.'version B mobile ad code', 'Optional mobile code for version B.', ['default' => '']),
            self::setting($p.'weight_a', 'number', $of.'version A weight', 'How often version A is chosen. 3 and 1 means 75% and 25%.', [
                'default' => 1, 'min' => 1, 'max' => 100,
            ]),
            self::setting($p.'weight_b', 'number', $of.'version B weight', 'How often version B is chosen.', [
                'default' => 1, 'min' => 1, 'max' => 100,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $more
     *
     * @return array<string, mixed>
     */
    private static function setting(string $key, string $type, string $label, string $description, array $more = []): array
    {
        return ['key' => $key, 'type' => $type, 'label' => $label, 'description' => $description] + $more;
    }
}
