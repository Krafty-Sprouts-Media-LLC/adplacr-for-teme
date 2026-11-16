<?php

declare(strict_types=1);

namespace KraftySprouts\AdPlacr\Ads;

/**
 * Builds the markup of one ad: a wrapper that names the slot and version, one slot of ad code (or two when
 * there is mobile code), and a small style block that switches between them by screen width. Nothing here
 * looks at the visitor's browser, so a cached page is right for everyone.
 */
final class Renderer
{
    /** How a visible slot is laid out: centred, full width. */
    private const string SLOT_VISIBLE = 'display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;width:100%!important;box-sizing:border-box!important;';

    private const string ALIGN_CSS = '.adplacr-align{display:flex;width:100%}.adplacr-align--left{justify-content:flex-start}'
        .'.adplacr-align--center{justify-content:center}.adplacr-align--right{justify-content:flex-end}'
        .'.adplacr-align>.adplacr__slot{max-width:100%}';

    private readonly int $mobileMax;

    private readonly int $tabletMax;

    /**
     * @param  int  $mobileMax  widest screen, in pixels, that counts as mobile (kept within 200 to 1024)
     * @param  int  $tabletMax  widest screen that counts as tablet (kept above the mobile width, up to 1600)
     */
    public function __construct(int $mobileMax, int $tabletMax)
    {
        $this->mobileMax = max(200, min(1024, $mobileMax));
        $this->tabletMax = max($this->mobileMax + 1, min(1600, $tabletMax));
    }

    /**
     * The ad code is returned inside the markup exactly as the administrator wrote it: Teme cleans every
     * insertion after the plugin hands it over, so the plugin does not escape or filter it itself.
     */
    public function render(Ad $ad, Version $version): string
    {
        $id = 'adplacr-ad-'.$ad->slot;
        $hasMobile = trim($version->mobileCode) !== '';

        $inner = $hasMobile
            ? '<div class="adplacr__slot adplacr__slot--universal">'.$version->code.'</div>'
                .'<div class="adplacr__slot adplacr__slot--mobile">'.$version->mobileCode.'</div>'
            : '<div class="adplacr__slot adplacr__slot--all">'.$version->code.'</div>';

        $css = $this->css('#'.$id, $ad->devices, $hasMobile);

        if ($ad->alignment !== 'none') {
            $inner = '<div class="adplacr-align adplacr-align--'.$this->attr($ad->alignment).'">'.$inner.'</div>';
            $css .= self::ALIGN_CSS;
        }

        return ($css !== '' ? '<style id="adplacr-css-'.$ad->slot.'">'.$css.'</style>' : '')
            .'<div id="'.$id.'" class="adplacr adplacr-pos-'.$this->attr($ad->position).'"'
            .' data-ad-id="'.$ad->slot.'" data-version-id="'.$this->attr($version->id).'">'
            .$inner
            .'</div>';
    }

    /**
     * @param  list<string>  $devices
     */
    private function css(string $selector, array $devices, bool $hasMobile): string
    {
        $css = '';

        if ($hasMobile) {
            $css .= '@media (max-width:'.$this->mobileMax.'px){'.$selector.' .adplacr__slot--universal{display:none!important}'
                .$selector.' .adplacr__slot--mobile{'.self::SLOT_VISIBLE.'}}';
            $css .= '@media (min-width:'.($this->mobileMax + 1).'px){'.$selector.' .adplacr__slot--mobile{display:none!important}}';
        }

        if (! in_array('mobile', $devices, true)) {
            $css .= '@media (max-width:'.$this->mobileMax.'px){'.$selector.'{display:none!important}}';
        }

        if (! in_array('tablet', $devices, true)) {
            $css .= '@media (min-width:'.($this->mobileMax + 1).'px) and (max-width:'.$this->tabletMax.'px){'.$selector.'{display:none!important}}';
        }

        if (! in_array('desktop', $devices, true)) {
            $css .= '@media (min-width:'.($this->tabletMax + 1).'px){'.$selector.'{display:none!important}}';
        }

        return $css;
    }

    private function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
