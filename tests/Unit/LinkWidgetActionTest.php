<?php

test('link widget action labels render as unified button-like spans', function () {
    $basePath = dirname(__DIR__, 2);
    $content = file_get_contents($basePath.'/resources/js/components/links/LinkWidgetContent.vue');

    expect($content)
        ->toContain('const actionPillClass')
        ->toContain('inline-flex h-8 w-fit items-center justify-center')
        ->toContain('socialActionPillClasses')
        ->toContain('actionServicePillClasses')
        ->toContain('actionServiceLabel')
        ->toContain("shape === '2x1'")
        ->toContain("shape === '1x2'")
        ->toContain("shape === '2x2'")
        ->toContain("actionLabel: 'プレイ'")
        ->toContain("config.actionLabel = 'プレイ'")
        ->toContain("config.actionLabel = 'フォロー'")
        ->toContain('{{ actionServiceLabel }}')
        ->not->toContain('<Play')
        ->not->toContain("actionLabel: 'Play'");

    expect(substr_count($content, 'mb-3 flex items-start justify-between'))->toBe(1);
    expect(substr_count($content, ":class=\"[socialActionPillClasses, 'mt-2']\""))->toBe(2);
    expect(substr_count($content, ":class=\"[actionServicePillClasses, 'mt-2']\""))->toBe(2);
});
