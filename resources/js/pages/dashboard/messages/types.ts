export type DashboardMessage = {
    id: string;
    body: string;
    sender_mode: 'anonymous' | 'named';
    sender_display_name: string | null;
    is_public: boolean;
    is_read: boolean;
    created_at: string;
    reply_body: string | null;
    sender: {
        id: number;
        name: string;
        avatar_url: string | null;
    };
    link: {
        id: string;
        slug: string;
        display_name: string;
        avatar_url: string | null;
    };
};
