CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE TABLE IF NOT EXISTS users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) NOT NULL UNIQUE,
    avatar_url TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS links (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    slug VARCHAR(255) NOT NULL UNIQUE,
    display_name VARCHAR(255) NOT NULL,
    bio TEXT,
    avatar_url TEXT,
    theme_config JSONB,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    has_web_display BOOLEAN NOT NULL DEFAULT FALSE,
    is_accepting_messages BOOLEAN NOT NULL DEFAULT TRUE,
    message_settings JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS links_user_updated_idx ON links(user_id, updated_at DESC);

CREATE TABLE IF NOT EXISTS widgets (
    id BIGSERIAL PRIMARY KEY,
    link_id UUID NOT NULL REFERENCES links(id) ON DELETE CASCADE,
    type VARCHAR(64) NOT NULL DEFAULT 'link',
    content TEXT,
    thumbnail_url TEXT,
    x INTEGER NOT NULL DEFAULT 0,
    y INTEGER NOT NULL DEFAULT 0,
    w INTEGER NOT NULL DEFAULT 1,
    h INTEGER NOT NULL DEFAULT 1,
    x_mobile INTEGER NOT NULL DEFAULT 0,
    y_mobile INTEGER NOT NULL DEFAULT 0,
    w_mobile INTEGER NOT NULL DEFAULT 1,
    h_mobile INTEGER NOT NULL DEFAULT 1,
    settings JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS widgets_link_desktop_idx ON widgets(link_id, y, x);
CREATE INDEX IF NOT EXISTS widgets_link_mobile_idx ON widgets(link_id, y_mobile, x_mobile);

CREATE TABLE IF NOT EXISTS messages (
    id BIGSERIAL PRIMARY KEY,
    link_id UUID NOT NULL REFERENCES links(id) ON DELETE CASCADE,
    body TEXT NOT NULL,
    sender_name VARCHAR(255),
    sender_email VARCHAR(255),
    user_agent TEXT,
    ip_address VARCHAR(255),
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS messages_link_created_idx ON messages(link_id, created_at DESC);

CREATE TABLE IF NOT EXISTS link_view_daily_stats (
    id BIGSERIAL PRIMARY KEY,
    link_id UUID NOT NULL REFERENCES links(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    view_count INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(link_id, date)
);

CREATE TABLE IF NOT EXISTS widget_click_daily_stats (
    id BIGSERIAL PRIMARY KEY,
    link_id UUID NOT NULL REFERENCES links(id) ON DELETE CASCADE,
    widget_id BIGINT NOT NULL REFERENCES widgets(id) ON DELETE CASCADE,
    date DATE NOT NULL,
    click_count INTEGER NOT NULL DEFAULT 0,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(widget_id, date)
);

CREATE TABLE IF NOT EXISTS sessions (
    token_hash CHAR(64) PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    expires_at TIMESTAMPTZ NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
