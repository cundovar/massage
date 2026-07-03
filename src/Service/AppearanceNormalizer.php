<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Normalizes appearance settings with validation and defaults.
 */
class AppearanceNormalizer
{
    private const VALID_PRESETS = ['ayurveda', 'spa-luxe', 'nature', 'zen', 'energique'];
    private const VALID_HEADER_STYLES = ['transparent', 'solid', 'sticky'];

    /**
     * @param array<string, mixed>|null $data
     * @return array<string, mixed>
     */
    public function normalize(?array $data): array
    {
        $data = $data ?? [];
        $preset = (string) ($data['themePreset'] ?? 'ayurveda');
        $headerStyle = (string) ($data['headerStyle'] ?? 'sticky');

        return [
            'themePreset' => in_array($preset, self::VALID_PRESETS, true) ? $preset : 'ayurveda',
            'useCustomAccent' => (bool) ($data['useCustomAccent'] ?? false),
            'customAccentColor' => $data['customAccentColor'] ?? null,
            'headerStyle' => in_array($headerStyle, self::VALID_HEADER_STYLES, true) ? $headerStyle : 'sticky',
            'showDarkModeToggle' => (bool) ($data['showDarkModeToggle'] ?? true),
            'bodyBackgroundImage' => is_string($data['bodyBackgroundImage'] ?? null) && trim((string) $data['bodyBackgroundImage']) !== ''
                ? trim((string) $data['bodyBackgroundImage'])
                : null,
        ];
    }
}
