<?php

test('frontend image compression uses upload limits and image presets', function () {
    $basePath = dirname(__DIR__, 2);
    $compression = file_get_contents($basePath.'/resources/js/utils/imageCompression.ts');
    $toolbar = file_get_contents($basePath.'/resources/js/components/links/LinkToolbar.vue');
    $widgetContent = file_get_contents($basePath.'/resources/js/components/links/LinkWidgetContent.vue');
    $linkProfile = file_get_contents($basePath.'/resources/js/components/links/LinkProfile.vue');
    $settingsProfile = file_get_contents($basePath.'/resources/js/pages/settings/Profile.vue');

    expect($compression)
        ->toContain('const MAX_UPLOAD_BYTES = 5 * 1024 * 1024')
        ->toContain('const DEFAULT_QUALITY = 0.8')
        ->toContain('maxWidthOrHeight: 512')
        ->toContain('maxWidthOrHeight: 1600')
        ->toContain('const browserSupportsWebp = () =>')
        ->toContain('const isAnimatedImageFile = (file: File) =>')
        ->toContain("file.type === 'image/gif'")
        ->toContain("file.type === 'image/apng'")
        ->toContain("fileName.endsWith('.apng')")
        ->toContain('if (isAnimatedImageFile(file))')
        ->toContain("file.type === 'image/jpeg' || !browserSupportsWebp()")
        ->toContain("'image/webp'")
        ->toContain('画像は5MB未満のファイルを選択してください。');

    expect($toolbar)
        ->toContain("compressImage(file, { preset: 'card' })")
        ->toContain('if (!compressedFile) return;');

    expect($widgetContent)
        ->toContain("compressImage(file, { preset: 'card' })")
        ->toContain('if (!compressedFile) return;');

    expect($linkProfile)
        ->toContain("compressImage(file, { preset: 'avatar' })")
        ->toContain('if (!compressedFile) return;');

    expect($settingsProfile)
        ->toContain("compressImage(avatar, { preset: 'avatar' })")
        ->toContain('if (!compressedAvatar)')
        ->toContain('reader.readAsDataURL(compressedAvatar)');
});

test('settings profile avatar accepts browser compressed webp files', function () {
    $basePath = dirname(__DIR__, 2);
    $request = file_get_contents($basePath.'/app/Http/Requests/Settings/ProfileUpdateRequest.php');

    expect($request)
        ->toContain('mimetypes:image/jpeg,image/png,image/gif,image/webp,image/apng');
});
