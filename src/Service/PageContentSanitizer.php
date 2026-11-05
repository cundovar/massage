<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Cleans the HTML and CSS exported by GrapesJS before they reach the public site.
 */
final class PageContentSanitizer
{
    private const FORBIDDEN_CSS_PATTERN = '~</style|@import|javascript:~i';

    /** Accessibility attributes emitted by the GrapesJS blocks. */
    private const ARIA_ATTRIBUTES = [
        'aria-controls',
        'aria-expanded',
        'aria-hidden',
        'aria-label',
        'aria-labelledby',
        'aria-live',
        'aria-roledescription',
        'aria-selected',
        'aria-valuemax',
        'aria-valuemin',
        'aria-valuenow',
    ];

    private readonly HtmlSanitizer $htmlSanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            ->allowStaticElements()
            ->allowAttribute('id', '*')
            ->allowAttribute('class', '*')
            ->allowAttribute('data-dynamic', '*')
            ->allowAttribute('data-animate', '*')
            ->allowAttribute('data-slides', '*')
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->allowMediaSchemes(['http', 'https'])
            ->allowRelativeMedias()
            ->withMaxInputLength(-1);

        foreach (self::ARIA_ATTRIBUTES as $attribute) {
            $config = $config->allowAttribute($attribute, '*');
        }

        $this->htmlSanitizer = new HtmlSanitizer($config);
    }

    public function sanitizeHtml(string $html): string
    {
        return $this->htmlSanitizer->sanitize($html);
    }

    public function isCssAllowed(string $css): bool
    {
        return preg_match(self::FORBIDDEN_CSS_PATTERN, $css) !== 1;
    }
}
