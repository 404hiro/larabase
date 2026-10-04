package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"os"
	"time"

	_ "github.com/jackc/pgx/v5/stdlib"
)

func main() {
	sourceURL := os.Getenv("LARAVEL_DATABASE_URL")
	targetURL := os.Getenv("DATABASE_URL")
	if sourceURL == "" || targetURL == "" {
		log.Fatal("LARAVEL_DATABASE_URL and DATABASE_URL are required")
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Minute)
	defer cancel()

	source, err := sql.Open("pgx", sourceURL)
	if err != nil {
		log.Fatal(err)
	}
	defer source.Close()

	target, err := sql.Open("pgx", targetURL)
	if err != nil {
		log.Fatal(err)
	}
	defer target.Close()

	if err := importUsers(ctx, source, target); err != nil {
		log.Fatal(err)
	}
	if err := importLinks(ctx, source, target); err != nil {
		log.Fatal(err)
	}
	if err := importWidgets(ctx, source, target); err != nil {
		log.Fatal(err)
	}

	log.Println("import completed")
}

func importUsers(ctx context.Context, source *sql.DB, target *sql.DB) error {
	rows, err := source.QueryContext(ctx, `SELECT id, name, google_id, avatar_url FROM users WHERE google_id IS NOT NULL`)
	if err != nil {
		return err
	}
	defer rows.Close()

	count := 0
	for rows.Next() {
		var id int64
		var name string
		var googleID string
		var avatarURL sql.NullString
		if err := rows.Scan(&id, &name, &googleID, &avatarURL); err != nil {
			return err
		}
		_, err := target.ExecContext(ctx, `
			INSERT INTO users (id, name, google_id, avatar_url)
			VALUES ($1, $2, $3, $4)
			ON CONFLICT (google_id)
			DO UPDATE SET name = EXCLUDED.name, avatar_url = EXCLUDED.avatar_url, updated_at = NOW()
		`, id, name, googleID, nullValue(avatarURL))
		if err != nil {
			return err
		}
		count++
	}
	log.Printf("users imported: %d", count)
	return rows.Err()
}

func importLinks(ctx context.Context, source *sql.DB, target *sql.DB) error {
	rows, err := source.QueryContext(ctx, `
		SELECT id, user_id, slug, display_name, bio, avatar_url, theme_config, is_published, has_web_display, is_accepting_messages, message_settings
		FROM links
	`)
	if err != nil {
		return err
	}
	defer rows.Close()

	count := 0
	for rows.Next() {
		var id string
		var userID int64
		var slug string
		var displayName string
		var bio sql.NullString
		var avatarURL sql.NullString
		var themeConfig json.RawMessage
		var isPublished bool
		var hasWebDisplay bool
		var isAcceptingMessages bool
		var messageSettings json.RawMessage
		if err := rows.Scan(&id, &userID, &slug, &displayName, &bio, &avatarURL, &themeConfig, &isPublished, &hasWebDisplay, &isAcceptingMessages, &messageSettings); err != nil {
			return err
		}
		_, err := target.ExecContext(ctx, `
			INSERT INTO links (id, user_id, slug, display_name, bio, avatar_url, theme_config, is_published, has_web_display, is_accepting_messages, message_settings)
			VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)
			ON CONFLICT (slug)
			DO UPDATE SET display_name = EXCLUDED.display_name, bio = EXCLUDED.bio, avatar_url = EXCLUDED.avatar_url,
				theme_config = EXCLUDED.theme_config, is_published = EXCLUDED.is_published, has_web_display = EXCLUDED.has_web_display,
				is_accepting_messages = EXCLUDED.is_accepting_messages, message_settings = EXCLUDED.message_settings, updated_at = NOW()
		`, id, userID, slug, displayName, nullValue(bio), nullValue(avatarURL), nullJSON(themeConfig), isPublished, hasWebDisplay, isAcceptingMessages, nullJSON(messageSettings))
		if err != nil {
			return err
		}
		count++
	}
	log.Printf("links imported: %d", count)
	return rows.Err()
}

func importWidgets(ctx context.Context, source *sql.DB, target *sql.DB) error {
	rows, err := source.QueryContext(ctx, `
		SELECT id, link_id, type, content, thumbnail_url, x, y, w, h, x_mobile, y_mobile, w_mobile, h_mobile, settings
		FROM widgets
	`)
	if err != nil {
		return err
	}
	defer rows.Close()

	count := 0
	for rows.Next() {
		var item struct {
			ID           int64
			LinkID       string
			Type         string
			Content      sql.NullString
			ThumbnailURL sql.NullString
			X            int
			Y            int
			W            int
			H            int
			XMobile      int
			YMobile      int
			WMobile      int
			HMobile      int
			Settings     json.RawMessage
		}
		if err := rows.Scan(&item.ID, &item.LinkID, &item.Type, &item.Content, &item.ThumbnailURL, &item.X, &item.Y, &item.W, &item.H, &item.XMobile, &item.YMobile, &item.WMobile, &item.HMobile, &item.Settings); err != nil {
			return err
		}
		_, err := target.ExecContext(ctx, `
			INSERT INTO widgets (id, link_id, type, content, thumbnail_url, x, y, w, h, x_mobile, y_mobile, w_mobile, h_mobile, settings)
			VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13,$14)
			ON CONFLICT (id)
			DO UPDATE SET type = EXCLUDED.type, content = EXCLUDED.content, thumbnail_url = EXCLUDED.thumbnail_url,
				x = EXCLUDED.x, y = EXCLUDED.y, w = EXCLUDED.w, h = EXCLUDED.h,
				x_mobile = EXCLUDED.x_mobile, y_mobile = EXCLUDED.y_mobile, w_mobile = EXCLUDED.w_mobile, h_mobile = EXCLUDED.h_mobile,
				settings = EXCLUDED.settings, updated_at = NOW()
		`, item.ID, item.LinkID, item.Type, nullValue(item.Content), nullValue(item.ThumbnailURL), item.X, item.Y, item.W, item.H, item.XMobile, item.YMobile, item.WMobile, item.HMobile, nullJSON(item.Settings))
		if err != nil {
			return err
		}
		count++
	}
	log.Printf("widgets imported: %d", count)
	return rows.Err()
}

func nullValue(value sql.NullString) any {
	if value.Valid {
		return value.String
	}
	return nil
}

func nullJSON(value json.RawMessage) any {
	if len(value) == 0 || string(value) == "null" {
		return nil
	}
	return value
}

func init() {
	if _, ok := os.LookupEnv("LARAVEL_DATABASE_URL"); !ok {
		fmt.Fprintln(os.Stderr, "usage: LARAVEL_DATABASE_URL=postgres://... DATABASE_URL=postgres://... go run ./cmd/import-laravel")
	}
}
