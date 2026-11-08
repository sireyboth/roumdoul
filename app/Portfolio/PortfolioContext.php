<?php

namespace App\Portfolio;

/**
 * Everything a portfolio view needs about "where am I": the portfolio, the
 * language, the current page, plus URL/translation helpers. Shared to every
 * portfolio view as $pf.
 */
class PortfolioContext
{
    public function __construct(
        public readonly Portfolio $portfolio,
        public readonly string $locale,
        public readonly string $page,
        public readonly array $params = [],
    ) {}

    /** URL to a portfolio page in the current language. */
    public function url(string $page, array $params = [], ?string $locale = null): string
    {
        return route("portfolio.{$page}", ['portfolio' => $this->portfolio->slug, 'locale' => $locale ?? $this->locale] + $params);
    }

    /** The same page in another language (used by the language switcher and hreflang). */
    public function switchUrl(string $locale): string
    {
        return $this->url($this->page, $this->params, $locale);
    }

    public function canonical(): string
    {
        return $this->switchUrl($this->locale);
    }

    /** Shared interface strings (lang/{locale}/portfolio.php). */
    public function t(string $key, array $replace = []): string
    {
        return __("portfolio.{$key}", $replace, $this->locale);
    }

    /** This portfolio's own page copy (portfolios/{slug}/content/{locale}.php). */
    public function copy(string $key, mixed $default = null): mixed
    {
        return $this->portfolio->text($this->locale, $key, $default);
    }

    /** lang="" value for a piece of page copy (differs only when it fell back). */
    public function copyLang(string $key): string
    {
        return $this->portfolio->textLocale($this->locale, $key);
    }

    public function name(): string
    {
        return $this->portfolio->name($this->locale);
    }

    public function isActive(string $section): bool
    {
        return $this->page === $section || str_starts_with($this->page, $section.'.');
    }

    public function image(?string $path): ?array
    {
        return $this->portfolio->media()->image($path);
    }

    public function editorHints(): bool
    {
        return (bool) config('portfolio.editor_hints');
    }

    public function isKhmer(): bool
    {
        return $this->locale === 'km';
    }

    /** Khmer numerals for Khmer pages (section numbers, indexes). */
    public function num(int|string $n, int $pad = 2): string
    {
        $s = is_int($n) ? str_pad((string) $n, $pad, '0', STR_PAD_LEFT) : $n;

        return $this->isKhmer() ? strtr($s, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']) : $s;
    }
}
