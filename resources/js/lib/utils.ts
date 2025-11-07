import { InertiaLinkProps } from '@inertiajs/vue3';
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function urlIsActive(
    urlToCheck: NonNullable<InertiaLinkProps['href']>,
    currentUrl: string,
) {
    const url = toUrl(urlToCheck);
    
    // 完全一致の場合
    if (url === currentUrl) {
        return true;
    }
    
    // 特定のパスの場合は部分一致も許可
    if (url === '/admin/users' && currentUrl.startsWith('/admin/users')) {
        return true;
    }
    
    return false;
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url;
}
