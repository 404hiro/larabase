export const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080';

export type CurrentUser = {
  id: number;
  name: string;
  avatar_url: string | null;
};

export type LinkWidget = {
  id: number | string;
  type: 'link' | 'image' | 'text' | 'section' | 'map' | string;
  content: string | null;
  thumbnail_url: string | null;
  x: number;
  y: number;
  w: number;
  h: number;
  x_mobile: number;
  y_mobile: number;
  w_mobile: number;
  h_mobile: number;
  settings: Record<string, any> | null;
};

export type GridLink = {
  id: string;
  user_id: number;
  slug: string;
  display_name: string;
  bio: string | null;
  avatar_url: string | null;
  theme_config: {
    theme?: 'light' | 'dark';
    widget_style?: 'sharp' | 'soft' | 'rounded';
  } | null;
  is_published: boolean;
  has_web_display: boolean;
  is_accepting_messages: boolean;
  message_settings: Record<string, any> | null;
  widgets: LinkWidget[];
};

export async function apiGet<T>(path: string): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });
  if (!response.ok) {
    throw new Error(`GET ${path} failed`);
  }
  return response.json();
}

export async function apiJSON<T>(path: string, method: string, body: unknown): Promise<T> {
  const response = await fetch(`${apiBaseUrl}${path}`, {
    method,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(body),
  });
  if (!response.ok) {
    throw new Error(`${method} ${path} failed`);
  }
  return response.json();
}

export function apiUrl(path: string): string {
  if (/^https?:\/\//.test(path)) {
    return path;
  }
  return `${apiBaseUrl}${path}`;
}
