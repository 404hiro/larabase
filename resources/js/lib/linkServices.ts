export type LinkService = {
    name: string;
    account: string;
    color: string;
    backgroundColor: string;
    actionLabel?: string;
    isAppStore?: boolean;
    isCommerce?: boolean;
    isFanPlatform?: boolean;
    isMusic?: boolean;
    isSupport?: boolean;
};

export const linkServicesConfig: Record<string, Omit<LinkService, 'account'> & { account?: string }> = {
    'x.com': {
        name: 'X',
        color: 'bg-black text-white hover:bg-gray-800',
        backgroundColor: 'bg-gray-50',
    },
    'twitter.com': {
        name: 'X',
        color: 'bg-black text-white hover:bg-gray-800',
        backgroundColor: 'bg-gray-50',
    },
    'instagram.com': {
        name: 'Instagram',
        color: 'bg-gradient-to-r from-[#833ab4] via-[#fd1d1d] to-[#fcb045] text-white hover:opacity-90',
        backgroundColor: 'bg-[#fff1f6]',
    },
    'threads.net': {
        name: 'Threads',
        color: 'bg-black text-white hover:bg-gray-800',
        backgroundColor: 'bg-gray-50',
    },
    'youtube.com': {
        name: 'YouTube',
        color: 'bg-[#FF0000] text-white hover:bg-[#CC0000]',
        backgroundColor: 'bg-[#fff1f1]',
    },
    'music.youtube.com': {
        name: 'YouTube Music',
        color: 'bg-[#FF0000] text-white hover:bg-[#CC0000]',
        backgroundColor: 'bg-[#fff1f1]',
        actionLabel: 'プレイ',
        isMusic: true,
    },
    'tiktok.com': {
        name: 'TikTok',
        color: 'bg-black text-white hover:bg-gray-800',
        backgroundColor: 'bg-gray-50',
    },
    'facebook.com': {
        name: 'Facebook',
        color: 'bg-[#1877F2] text-white hover:bg-[#0C63D4]',
        backgroundColor: 'bg-[#eff6ff]',
    },
    'tumblr.com': {
        name: 'Tumblr',
        color: 'bg-[#36465D] text-white hover:bg-[#253142]',
        backgroundColor: 'bg-[#f1f5f9]',
    },
    'pixiv.net': {
        name: 'Pixiv',
        color: 'bg-[#0096FA] text-white hover:bg-[#007AD1]',
        backgroundColor: 'bg-[#eff8ff]',
    },
    'snapchat.com': {
        name: 'Snapchat',
        color: 'bg-[#FFFC00] text-black hover:bg-[#E6E200]',
        backgroundColor: 'bg-[#fffde7]',
    },
    'github.com': {
        name: 'GitHub',
        color: 'bg-[#24292F] text-white hover:bg-[#000000]',
        backgroundColor: 'bg-gray-50',
    },
    'linkedin.com': {
        name: 'LinkedIn',
        color: 'bg-[#0A66C2] text-white hover:bg-[#004182]',
        backgroundColor: 'bg-[#eff6ff]',
    },
    'twitch.tv': {
        name: 'Twitch',
        color: 'bg-[#9146FF] text-white hover:bg-[#772CE8]',
        backgroundColor: 'bg-[#f5f0ff]',
    },
    'medium.com': {
        name: 'Medium',
        color: 'bg-black text-white hover:bg-gray-800',
        backgroundColor: 'bg-gray-50',
    },
    'dribbble.com': {
        name: 'Dribbble',
        color: 'bg-[#ea4c89] text-white hover:bg-[#e0357a]',
        backgroundColor: 'bg-[#fff1f6]',
    },
    'figma.com': {
        name: 'Figma',
        color: 'bg-[#F24E1E] text-white hover:bg-[#E14013]',
        backgroundColor: 'bg-[#fff4ef]',
    },
    'note.com': {
        name: 'Note',
        color: 'bg-[#2CB696] text-white hover:bg-[#239B7F]',
        backgroundColor: 'bg-[#effcf8]',
    },
    'music.apple.com': {
        name: 'Apple Music',
        color: 'bg-[#FA243C] text-white hover:bg-[#dd1830]',
        backgroundColor: 'bg-[#fff1f3]',
        actionLabel: 'プレイ',
        isMusic: true,
    },
    'itunes.apple.com': {
        name: 'Apple Music',
        color: 'bg-[#FA243C] text-white hover:bg-[#dd1830]',
        backgroundColor: 'bg-[#fff1f3]',
        actionLabel: 'プレイ',
        isMusic: true,
    },
    'open.spotify.com': {
        name: 'Spotify',
        color: 'bg-[#1DB954] text-white hover:bg-[#169c46]',
        backgroundColor: 'bg-[#effaf3]',
        actionLabel: 'プレイ',
        isMusic: true,
    },
    'spotify.com': {
        name: 'Spotify',
        color: 'bg-[#1DB954] text-white hover:bg-[#169c46]',
        backgroundColor: 'bg-[#effaf3]',
        actionLabel: 'プレイ',
        isMusic: true,
    },
    'music.google.com': {
        name: 'Google Music',
        color: 'bg-[#1A73E8] text-white hover:bg-[#1558b0]',
        backgroundColor: 'bg-[#eff6ff]',
        actionLabel: 'プレイ',
        isMusic: true,
    },
    'vimeo.com': {
        name: 'Vimeo',
        color: 'bg-[#1AB7EA] text-white hover:bg-[#16A3D1]',
        backgroundColor: 'bg-[#f0fbff]',
    },
    'behance.net': {
        name: 'Behance',
        color: 'bg-[#1769FF] text-white hover:bg-[#0057E7]',
        backgroundColor: 'bg-[#f0f5ff]',
    },
};
