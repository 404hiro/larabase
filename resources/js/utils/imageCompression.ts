import imageCompression from 'browser-image-compression';

type ImageCompressionPreset = 'avatar' | 'card';

type ImageCompressionOptions = {
    preset?: ImageCompressionPreset;
    maxWidthOrHeight?: number;
    quality?: number;
};

const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
const DEFAULT_QUALITY = 0.8;
let webpSupport: boolean | null = null;

const compressionPresets: Record<
    ImageCompressionPreset,
    { maxWidthOrHeight: number }
> = {
    avatar: {
        maxWidthOrHeight: 512,
    },
    card: {
        maxWidthOrHeight: 1600,
    },
};

const showImageUploadError = (description: string) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.dispatchEvent(
        new CustomEvent('app-toast', {
            detail: {
                variant: 'error',
                title: 'エラー',
                description,
            },
        }),
    );
};

const normalizeQuality = (quality?: number) => {
    if (quality === undefined) {
        return DEFAULT_QUALITY;
    }

    return Math.min(Math.max(quality, 0.75), 0.85);
};

const browserSupportsWebp = () => {
    if (webpSupport !== null) {
        return webpSupport;
    }

    if (typeof document === 'undefined') {
        webpSupport = false;

        return webpSupport;
    }

    const canvas = document.createElement('canvas');
    canvas.width = 1;
    canvas.height = 1;
    webpSupport = canvas.toDataURL('image/webp').startsWith('data:image/webp');

    return webpSupport;
};

const outputMimeType = (file: File) => {
    return file.type === 'image/jpeg' || !browserSupportsWebp()
        ? 'image/jpeg'
        : 'image/webp';
};

const outputFileName = (fileName: string, mimeType: string) => {
    const extension = mimeType === 'image/jpeg' ? 'jpg' : 'webp';

    return fileName.replace(/\.[^.]+$/, '') + `.${extension}`;
};

const isAnimatedImageFile = (file: File) => {
    const fileName = file.name.toLowerCase();

    return (
        file.type === 'image/gif' ||
        file.type === 'image/apng' ||
        fileName.endsWith('.gif') ||
        fileName.endsWith('.apng')
    );
};

/**
 * 渡された画像ファイルを圧縮します。
 * - 元ファイルが5MB以上の場合はアップロード前に拒否します。
 * - avatar: 長辺512px以下
 * - card: 長辺1600px以下
 * - WebP または JPEG に変換します。
 */
export async function compressImage(
    file: File,
    options: ImageCompressionOptions = {},
): Promise<File | null> {
    if (file.size >= MAX_UPLOAD_BYTES) {
        showImageUploadError('画像は5MB未満のファイルを選択してください。');

        return null;
    }

    if (isAnimatedImageFile(file)) {
        return file;
    }

    const preset = compressionPresets[options.preset ?? 'card'];
    const fileType = outputMimeType(file);
    const compressionOptions = {
        maxSizeMB: 5,
        maxWidthOrHeight: options.maxWidthOrHeight ?? preset.maxWidthOrHeight,
        initialQuality: normalizeQuality(options.quality),
        fileType,
        useWebWorker: true,
    };

    try {
        const compressedFile = await imageCompression(file, compressionOptions);

        if (compressedFile.size >= MAX_UPLOAD_BYTES) {
            showImageUploadError('画像は5MB未満に圧縮できませんでした。');

            return null;
        }

        // browser-image-compression may return a File with the name of the original but sometimes just a Blob.
        // Ensure it is a File with a name and type that match the converted image.
        return new File(
            [compressedFile],
            outputFileName(file.name, compressedFile.type),
            {
                type: compressedFile.type,
                lastModified: Date.now(),
            },
        );
    } catch (error) {
        console.error('Image compression failed:', error);
        showImageUploadError('画像の圧縮に失敗しました。');

        return null;
    }
}
