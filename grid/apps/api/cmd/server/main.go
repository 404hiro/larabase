package main

import (
	"context"
	"crypto/rand"
	"crypto/sha256"
	"database/sql"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"html"
	"io"
	"log"
	"mime/multipart"
	"net/http"
	"net/url"
	"os"
	"path/filepath"
	"regexp"
	"strconv"
	"strings"
	"time"

	_ "github.com/jackc/pgx/v5/stdlib"
	"golang.org/x/oauth2"
	"golang.org/x/oauth2/google"
)

type server struct {
	db          *sql.DB
	oauth       *oauth2.Config
	frontendURL string
	cookieName  string
}

type user struct {
	ID        int64   `json:"id"`
	Name      string  `json:"name"`
	GoogleID  string  `json:"google_id,omitempty"`
	AvatarURL *string `json:"avatar_url"`
}

type link struct {
	ID                  string           `json:"id"`
	UserID              int64            `json:"user_id"`
	Slug                string           `json:"slug"`
	DisplayName         string           `json:"display_name"`
	Bio                 *string          `json:"bio"`
	AvatarURL           *string          `json:"avatar_url"`
	ThemeConfig         *json.RawMessage `json:"theme_config"`
	IsPublished         bool             `json:"is_published"`
	HasWebDisplay       bool             `json:"has_web_display"`
	IsAcceptingMessages bool             `json:"is_accepting_messages"`
	MessageSettings     *json.RawMessage `json:"message_settings"`
	Widgets             []widget         `json:"widgets"`
}

type widget struct {
	ID           int64            `json:"id"`
	LinkID       string           `json:"link_id,omitempty"`
	Type         string           `json:"type"`
	Content      *string          `json:"content"`
	ThumbnailURL *string          `json:"thumbnail_url"`
	X            int              `json:"x"`
	Y            int              `json:"y"`
	W            int              `json:"w"`
	H            int              `json:"h"`
	XMobile      int              `json:"x_mobile"`
	YMobile      int              `json:"y_mobile"`
	WMobile      int              `json:"w_mobile"`
	HMobile      int              `json:"h_mobile"`
	Settings     *json.RawMessage `json:"settings"`
}

func main() {
	db, err := sql.Open("pgx", env("DATABASE_URL", "postgres://grid:grid@localhost:5433/grid?sslmode=disable"))
	if err != nil {
		log.Fatal(err)
	}
	if err := db.Ping(); err != nil {
		log.Fatal(err)
	}
	if err := runMigrations(db); err != nil {
		log.Fatal(err)
	}

	s := &server{
		db:          db,
		frontendURL: strings.TrimRight(env("FRONTEND_URL", "http://localhost:5173"), "/"),
		cookieName:  env("SESSION_COOKIE_NAME", "grid_session"),
		oauth: &oauth2.Config{
			ClientID:     os.Getenv("GOOGLE_CLIENT_ID"),
			ClientSecret: os.Getenv("GOOGLE_CLIENT_SECRET"),
			RedirectURL:  env("GOOGLE_REDIRECT_URI", "http://localhost:8080/auth/google/callback"),
			Scopes:       []string{"openid", "profile"},
			Endpoint:     google.Endpoint,
		},
	}

	mux := http.NewServeMux()
	mux.HandleFunc("/", s.route)

	port := env("PORT", env("API_PORT", "8080"))
	log.Printf("grid api listening on :%s", port)
	log.Fatal(http.ListenAndServe(":"+port, mux))
}

func (s *server) route(w http.ResponseWriter, r *http.Request) {
	s.withCORS(w, r)
	if r.Method == http.MethodOptions {
		w.WriteHeader(http.StatusNoContent)
		return
	}

	path := strings.Trim(r.URL.Path, "/")
	switch {
	case path == "api/me" && r.Method == http.MethodGet:
		s.handleMe(w, r)
	case strings.HasPrefix(path, "api/links/") && strings.HasSuffix(path, "/message") && r.Method == http.MethodGet:
		s.handleShowMessageForm(w, r, strings.TrimSuffix(strings.TrimPrefix(path, "api/links/"), "/message"))
	case strings.HasPrefix(path, "api/links/") && r.Method == http.MethodGet:
		s.handleShowLink(w, r, strings.TrimPrefix(path, "api/links/"))
	case strings.HasPrefix(path, "@") && strings.Contains(path, "/widgets/") && strings.HasSuffix(path, "/click") && r.Method == http.MethodGet:
		s.handleWidgetClick(w, r, path)
	case path == "auth/google" && r.Method == http.MethodGet:
		s.handleGoogleRedirect(w, r)
	case path == "auth/google/callback" && r.Method == http.MethodGet:
		s.handleGoogleCallback(w, r)
	case strings.HasPrefix(path, "links/") && strings.HasSuffix(path, "/widgets/sync") && r.Method == http.MethodPost:
		s.handleSyncWidgets(w, r, strings.TrimSuffix(strings.TrimPrefix(path, "links/"), "/widgets/sync"))
	case strings.HasPrefix(path, "links/") && strings.HasSuffix(path, "/messages") && r.Method == http.MethodPost:
		s.handleCreateMessage(w, r, strings.TrimSuffix(strings.TrimPrefix(path, "links/"), "/messages"))
	case strings.HasPrefix(path, "links/") && (r.Method == http.MethodPut || r.Method == http.MethodPost):
		s.handleUpdateLink(w, r, strings.TrimPrefix(path, "links/"))
	case path == "widgets/upload-image" && r.Method == http.MethodPost:
		s.handleUploadImage(w, r)
	case path == "fetch-ogp" && r.Method == http.MethodPost:
		s.handleFetchOGP(w, r)
	case strings.HasPrefix(path, "uploads/") && (r.Method == http.MethodGet || r.Method == http.MethodHead):
		s.handleUploadedFile(w, r, strings.TrimPrefix(path, "uploads/"))
	default:
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "not found"})
	}
}

func (s *server) withCORS(w http.ResponseWriter, r *http.Request) {
	origin := r.Header.Get("Origin")
	if origin == s.frontendURL || strings.HasPrefix(origin, "http://localhost:") {
		w.Header().Set("Access-Control-Allow-Origin", origin)
		w.Header().Set("Access-Control-Allow-Credentials", "true")
	}
	w.Header().Set("Access-Control-Allow-Headers", "Content-Type")
	w.Header().Set("Access-Control-Allow-Methods", "GET,POST,PUT,DELETE,OPTIONS")
}

func (s *server) handleMe(w http.ResponseWriter, r *http.Request) {
	current, err := s.currentUser(r)
	if err != nil {
		writeJSON(w, http.StatusOK, map[string]any{"user": nil})
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"user": current})
}

func (s *server) handleShowLink(w http.ResponseWriter, r *http.Request, slug string) {
	current, _ := s.currentUser(r)
	item, err := s.findLinkBySlug(r.Context(), slug)
	if err != nil {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}
	isOwner := current != nil && current.ID == item.UserID
	if !item.IsPublished && !isOwner {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}
	if !shouldExclude(r, current, item.UserID) {
		_, _ = s.db.ExecContext(r.Context(), `
			INSERT INTO link_view_daily_stats (link_id, date, view_count)
			VALUES ($1, CURRENT_DATE, 1)
			ON CONFLICT (link_id, date)
			DO UPDATE SET view_count = link_view_daily_stats.view_count + 1, updated_at = NOW()
		`, item.ID)
	}
	item.Widgets, _ = s.widgetsForLink(r.Context(), item.ID)
	if item.Widgets == nil {
		item.Widgets = []widget{}
	}
	writeJSON(w, http.StatusOK, map[string]any{
		"link":       item,
		"is_owner":   isOwner,
		"is_editing": isOwner && r.URL.Query().Get("edit") == "1",
	})
}

func (s *server) handleWidgetClick(w http.ResponseWriter, r *http.Request, path string) {
	parts := strings.Split(path, "/")
	if len(parts) != 4 {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "not found"})
		return
	}
	slug := strings.TrimPrefix(parts[0], "@")
	widgetID, err := strconv.ParseInt(parts[2], 10, 64)
	if err != nil {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "not found"})
		return
	}

	current, _ := s.currentUser(r)
	item, err := s.findLinkBySlug(r.Context(), slug)
	if err != nil {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}
	isOwner := current != nil && current.ID == item.UserID
	if !item.IsPublished && !isOwner {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}

	var target string
	err = s.db.QueryRowContext(r.Context(), `SELECT content FROM widgets WHERE id = $1 AND link_id = $2`, widgetID, item.ID).Scan(&target)
	if err != nil || strings.TrimSpace(target) == "" {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "widget not found"})
		return
	}

	if !shouldExclude(r, current, item.UserID) {
		_, _ = s.db.ExecContext(r.Context(), `
			INSERT INTO widget_click_daily_stats (link_id, widget_id, date, click_count)
			VALUES ($1, $2, CURRENT_DATE, 1)
			ON CONFLICT (widget_id, date)
			DO UPDATE SET click_count = widget_click_daily_stats.click_count + 1, updated_at = NOW()
		`, item.ID, widgetID)
	}
	http.Redirect(w, r, target, http.StatusFound)
}

func (s *server) handleGoogleRedirect(w http.ResponseWriter, r *http.Request) {
	if s.oauth.ClientID == "" || s.oauth.ClientSecret == "" {
		http.Redirect(w, r, s.frontendURL+"/login?error=google_not_configured", http.StatusFound)
		return
	}
	state := randomToken(24)
	http.SetCookie(w, &http.Cookie{Name: "grid_oauth_state", Value: state, Path: "/", HttpOnly: true, SameSite: http.SameSiteLaxMode, MaxAge: 600})
	http.Redirect(w, r, s.oauth.AuthCodeURL(state), http.StatusFound)
}

func (s *server) handleGoogleCallback(w http.ResponseWriter, r *http.Request) {
	stateCookie, err := r.Cookie("grid_oauth_state")
	if err != nil || stateCookie.Value == "" || stateCookie.Value != r.URL.Query().Get("state") {
		http.Redirect(w, r, s.frontendURL+"/login?error=invalid_state", http.StatusFound)
		return
	}
	token, err := s.oauth.Exchange(r.Context(), r.URL.Query().Get("code"))
	if err != nil {
		http.Redirect(w, r, s.frontendURL+"/login?error=google_auth_failed", http.StatusFound)
		return
	}
	client := s.oauth.Client(r.Context(), token)
	resp, err := client.Get("https://www.googleapis.com/oauth2/v3/userinfo")
	if err != nil {
		http.Redirect(w, r, s.frontendURL+"/login?error=google_profile_failed", http.StatusFound)
		return
	}
	defer resp.Body.Close()

	var profile struct {
		Sub     string `json:"sub"`
		Name    string `json:"name"`
		Picture string `json:"picture"`
	}
	if err := json.NewDecoder(resp.Body).Decode(&profile); err != nil || profile.Sub == "" {
		http.Redirect(w, r, s.frontendURL+"/login?error=google_profile_failed", http.StatusFound)
		return
	}

	current, err := s.upsertGoogleUser(r.Context(), profile.Sub, profile.Name, profile.Picture)
	if err != nil {
		http.Redirect(w, r, s.frontendURL+"/login?error=user_save_failed", http.StatusFound)
		return
	}
	tokenValue := randomToken(32)
	hash := hashToken(tokenValue)
	_, _ = s.db.ExecContext(r.Context(), `DELETE FROM sessions WHERE user_id = $1 OR expires_at < NOW()`, current.ID)
	_, err = s.db.ExecContext(r.Context(), `INSERT INTO sessions (token_hash, user_id, expires_at) VALUES ($1, $2, $3)`, hash, current.ID, time.Now().Add(30*24*time.Hour))
	if err != nil {
		http.Redirect(w, r, s.frontendURL+"/login?error=session_failed", http.StatusFound)
		return
	}
	http.SetCookie(w, &http.Cookie{Name: s.cookieName, Value: tokenValue, Path: "/", HttpOnly: true, SameSite: http.SameSiteLaxMode, MaxAge: 30 * 24 * 60 * 60})

	defaultLink, err := s.ensureDefaultLink(r.Context(), current)
	if err != nil {
		http.Redirect(w, r, s.frontendURL+"/login?error=link_failed", http.StatusFound)
		return
	}
	http.Redirect(w, r, s.frontendURL+"/@"+defaultLink.Slug, http.StatusFound)
}

func (s *server) handleUpdateLink(w http.ResponseWriter, r *http.Request, slug string) {
	current, err := s.requireUser(r)
	if err != nil {
		writeJSON(w, http.StatusUnauthorized, map[string]string{"error": "unauthorized"})
		return
	}
	item, err := s.findLinkBySlug(r.Context(), slug)
	if err != nil || item.UserID != current.ID {
		writeJSON(w, http.StatusForbidden, map[string]string{"error": "forbidden"})
		return
	}

	var payload struct {
		DisplayName   string          `json:"display_name"`
		Bio           string          `json:"bio"`
		AvatarURL     *string         `json:"avatar_url"`
		DeleteAvatar  bool            `json:"delete_avatar"`
		ThemeConfig   json.RawMessage `json:"theme_config"`
		IsPublished   *bool           `json:"is_published"`
		HasWebDisplay *bool           `json:"has_web_display"`
	}
	if strings.Contains(r.Header.Get("Content-Type"), "multipart/form-data") {
		_ = r.ParseMultipartForm(10 << 20)
		payload.DisplayName = r.FormValue("display_name")
		payload.Bio = r.FormValue("bio")
		if r.FormValue("avatar_url") != "" {
			value := r.FormValue("avatar_url")
			payload.AvatarURL = &value
		}
		payload.DeleteAvatar = r.FormValue("delete_avatar") == "true" || r.FormValue("delete_avatar") == "1"
		payload.ThemeConfig = json.RawMessage(r.FormValue("theme_config"))
		if r.FormValue("is_published") != "" {
			value := r.FormValue("is_published") == "true" || r.FormValue("is_published") == "1"
			payload.IsPublished = &value
		}
		if r.FormValue("has_web_display") != "" {
			value := r.FormValue("has_web_display") == "true" || r.FormValue("has_web_display") == "1"
			payload.HasWebDisplay = &value
		}
	} else {
		_ = json.NewDecoder(r.Body).Decode(&payload)
	}

	if strings.TrimSpace(payload.DisplayName) == "" {
		payload.DisplayName = item.DisplayName
	}
	bio := nullable(strings.TrimSpace(payload.Bio))
	theme := nullableJSON(payload.ThemeConfig)
	isPublished := item.IsPublished
	if payload.IsPublished != nil {
		isPublished = *payload.IsPublished
	}
	hasWebDisplay := item.HasWebDisplay
	if payload.HasWebDisplay != nil {
		hasWebDisplay = *payload.HasWebDisplay
	}
	avatarURL := item.AvatarURL
	if payload.DeleteAvatar {
		avatarURL = nil
	} else if payload.AvatarURL != nil {
		value := strings.TrimSpace(*payload.AvatarURL)
		if value == "" {
			avatarURL = nil
		} else {
			avatarURL = &value
		}
	}

	_, err = s.db.ExecContext(r.Context(), `
		UPDATE links
		SET display_name = $1, bio = $2, avatar_url = $3, theme_config = COALESCE($4, theme_config),
			is_published = $5, has_web_display = $6, updated_at = NOW()
		WHERE id = $7
	`, strings.TrimSpace(payload.DisplayName), bio, avatarURL, theme, isPublished, hasWebDisplay, item.ID)
	if err != nil {
		writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "save failed"})
		return
	}
	writeJSON(w, http.StatusOK, map[string]string{"status": "saved"})
}

func (s *server) handleSyncWidgets(w http.ResponseWriter, r *http.Request, slug string) {
	current, err := s.requireUser(r)
	if err != nil {
		writeJSON(w, http.StatusUnauthorized, map[string]string{"error": "unauthorized"})
		return
	}
	item, err := s.findLinkBySlug(r.Context(), slug)
	if err != nil || item.UserID != current.ID {
		writeJSON(w, http.StatusForbidden, map[string]string{"error": "forbidden"})
		return
	}
	var payload struct {
		Widgets []widget `json:"widgets"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		writeJSON(w, http.StatusBadRequest, map[string]string{"error": "invalid json"})
		return
	}
	if len(payload.Widgets) > 50 {
		writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "too many widgets"})
		return
	}
	tx, err := s.db.BeginTx(r.Context(), nil)
	if err != nil {
		writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "transaction failed"})
		return
	}
	defer tx.Rollback()
	keptWidgetIDs := make([]int64, 0, len(payload.Widgets))
	savedWidgets := make([]widget, 0, len(payload.Widgets))
	for _, widget := range payload.Widgets {
		if !validWidget(widget) {
			writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "invalid widget"})
			return
		}
		if widget.Content != nil && len(*widget.Content) > 2000 {
			writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "content too long"})
			return
		}
		if len(stringValue(widget.Settings)) > 12000 {
			writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "settings too large"})
			return
		}
		if !validWidgetSettings(widget.Type, widget.Settings) {
			writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "invalid widget settings"})
			return
		}
		if widget.ID > 0 {
			result, err := tx.ExecContext(r.Context(), `
				UPDATE widgets
				SET type = $1, content = $2, thumbnail_url = $3, x = $4, y = $5, w = $6, h = $7, x_mobile = $8, y_mobile = $9, w_mobile = $10, h_mobile = $11, settings = $12
				WHERE id = $13 AND link_id = $14
			`, widget.Type, widget.Content, widget.ThumbnailURL, widget.X, widget.Y, widget.W, widget.H, widget.XMobile, widget.YMobile, widget.WMobile, widget.HMobile, nullableJSONRaw(widget.Settings), widget.ID, item.ID)
			if err != nil {
				writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "update failed"})
				return
			}
			if affected, _ := result.RowsAffected(); affected > 0 {
				keptWidgetIDs = append(keptWidgetIDs, widget.ID)
				widget.LinkID = item.ID
				savedWidgets = append(savedWidgets, widget)
				continue
			}
		}
		err = tx.QueryRowContext(r.Context(), `
			INSERT INTO widgets (link_id, type, content, thumbnail_url, x, y, w, h, x_mobile, y_mobile, w_mobile, h_mobile, settings)
			VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12,$13)
			RETURNING id
		`, item.ID, widget.Type, widget.Content, widget.ThumbnailURL, widget.X, widget.Y, widget.W, widget.H, widget.XMobile, widget.YMobile, widget.WMobile, widget.HMobile, nullableJSONRaw(widget.Settings)).Scan(&widget.ID)
		if err != nil {
			writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "insert failed"})
			return
		}
		keptWidgetIDs = append(keptWidgetIDs, widget.ID)
		widget.LinkID = item.ID
		savedWidgets = append(savedWidgets, widget)
	}
	if len(keptWidgetIDs) == 0 {
		if _, err := tx.ExecContext(r.Context(), `DELETE FROM widgets WHERE link_id = $1`, item.ID); err != nil {
			writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "delete failed"})
			return
		}
	} else {
		args := make([]any, 0, len(keptWidgetIDs)+1)
		args = append(args, item.ID)
		placeholders := make([]string, 0, len(keptWidgetIDs))
		for index, widgetID := range keptWidgetIDs {
			args = append(args, widgetID)
			placeholders = append(placeholders, fmt.Sprintf("$%d", index+2))
		}
		if _, err := tx.ExecContext(r.Context(), fmt.Sprintf(`DELETE FROM widgets WHERE link_id = $1 AND id NOT IN (%s)`, strings.Join(placeholders, ",")), args...); err != nil {
			writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "delete failed"})
			return
		}
	}
	if err := tx.Commit(); err != nil {
		writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "commit failed"})
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"status": "saved", "widgets": savedWidgets})
}

func (s *server) handleUploadImage(w http.ResponseWriter, r *http.Request) {
	if _, err := s.requireUser(r); err != nil {
		writeJSON(w, http.StatusUnauthorized, map[string]string{"error": "unauthorized"})
		return
	}
	if err := r.ParseMultipartForm(8 << 20); err != nil {
		writeJSON(w, http.StatusBadRequest, map[string]string{"error": "invalid upload"})
		return
	}
	file, header, err := r.FormFile("image")
	if err != nil {
		writeJSON(w, http.StatusBadRequest, map[string]string{"error": "image required"})
		return
	}
	defer file.Close()
	urlPath, err := saveUpload(file, header)
	if err != nil {
		writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "upload failed"})
		return
	}
	writeJSON(w, http.StatusOK, map[string]string{"url": urlPath})
}

func (s *server) handleUploadedFile(w http.ResponseWriter, r *http.Request, name string) {
	name = filepath.Base(name)
	if strings.TrimSpace(name) == "" || name == "." || name == string(filepath.Separator) {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "not found"})
		return
	}

	w.Header().Set("Cross-Origin-Resource-Policy", "cross-origin")
	w.Header().Set("Cache-Control", "public, max-age=31536000, immutable")
	http.ServeFile(w, r, filepath.Join("uploads", name))
}

func (s *server) handleFetchOGP(w http.ResponseWriter, r *http.Request) {
	var payload struct {
		URL string `json:"url"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil || len(payload.URL) > 2000 {
		writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "invalid url"})
		return
	}
	result, err := fetchOGP(r.Context(), payload.URL)
	if err != nil {
		writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "URLから情報を取得できませんでした"})
		return
	}
	writeJSON(w, http.StatusOK, result)
}

func (s *server) handleShowMessageForm(w http.ResponseWriter, r *http.Request, slug string) {
	item, err := s.findLinkBySlug(r.Context(), slug)
	if err != nil {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}
	if !item.IsPublished {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{
		"link": map[string]any{
			"slug":                  item.Slug,
			"display_name":          item.DisplayName,
			"avatar_url":            item.AvatarURL,
			"is_accepting_messages": item.IsAcceptingMessages,
			"message_settings":      item.MessageSettings,
		},
	})
}

func (s *server) handleCreateMessage(w http.ResponseWriter, r *http.Request, slug string) {
	item, err := s.findLinkBySlug(r.Context(), slug)
	if err != nil {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}
	if !item.IsPublished {
		writeJSON(w, http.StatusNotFound, map[string]string{"error": "link not found"})
		return
	}
	if !item.IsAcceptingMessages {
		writeJSON(w, http.StatusForbidden, map[string]string{"error": "messages disabled"})
		return
	}

	var payload struct {
		Body        string `json:"body"`
		SenderName  string `json:"sender_name"`
		SenderEmail string `json:"sender_email"`
	}
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		writeJSON(w, http.StatusBadRequest, map[string]string{"error": "invalid json"})
		return
	}
	body := strings.TrimSpace(payload.Body)
	if body == "" || len([]rune(body)) > 1000 {
		writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "message must be 1-1000 characters"})
		return
	}
	senderName := strings.TrimSpace(payload.SenderName)
	if len([]rune(senderName)) > 80 {
		writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "sender name too long"})
		return
	}
	senderEmail := strings.TrimSpace(payload.SenderEmail)
	if senderEmail != "" && (len(senderEmail) > 255 || !strings.Contains(senderEmail, "@")) {
		writeJSON(w, http.StatusUnprocessableEntity, map[string]string{"error": "invalid sender email"})
		return
	}

	_, err = s.db.ExecContext(r.Context(), `
		INSERT INTO messages (link_id, body, sender_name, sender_email, user_agent, ip_address)
		VALUES ($1, $2, $3, $4, $5, $6)
	`, item.ID, body, nullable(senderName), nullable(senderEmail), nullable(r.UserAgent()), nullable(clientIP(r)))
	if err != nil {
		writeJSON(w, http.StatusInternalServerError, map[string]string{"error": "message save failed"})
		return
	}
	writeJSON(w, http.StatusCreated, map[string]string{"status": "sent"})
}

func (s *server) currentUser(r *http.Request) (*user, error) {
	cookie, err := r.Cookie(s.cookieName)
	if err != nil || cookie.Value == "" {
		return nil, errors.New("missing session")
	}
	row := s.db.QueryRowContext(r.Context(), `
		SELECT users.id, users.name, users.google_id, users.avatar_url
		FROM sessions
		JOIN users ON users.id = sessions.user_id
		WHERE sessions.token_hash = $1 AND sessions.expires_at > NOW()
	`, hashToken(cookie.Value))
	var current user
	if err := row.Scan(&current.ID, &current.Name, &current.GoogleID, &current.AvatarURL); err != nil {
		return nil, err
	}
	return &current, nil
}

func (s *server) requireUser(r *http.Request) (*user, error) {
	return s.currentUser(r)
}

func (s *server) upsertGoogleUser(ctx context.Context, googleID string, name string, avatar string) (*user, error) {
	if strings.TrimSpace(name) == "" {
		name = "Grid User"
	}
	avatarValue := nullable(avatar)
	var current user
	err := s.db.QueryRowContext(ctx, `
		INSERT INTO users (google_id, name, avatar_url)
		VALUES ($1, $2, $3)
		ON CONFLICT (google_id)
		DO UPDATE SET name = EXCLUDED.name, avatar_url = EXCLUDED.avatar_url, updated_at = NOW()
		RETURNING id, name, google_id, avatar_url
	`, googleID, name, avatarValue).Scan(&current.ID, &current.Name, &current.GoogleID, &current.AvatarURL)
	return &current, err
}

func (s *server) ensureDefaultLink(ctx context.Context, current *user) (*link, error) {
	row := s.db.QueryRowContext(ctx, `
		SELECT id, user_id, slug, display_name, bio, avatar_url, theme_config, is_published, has_web_display, is_accepting_messages, message_settings
		FROM links
		WHERE user_id = $1
		ORDER BY updated_at DESC
		LIMIT 1
	`, current.ID)
	item, err := scanLink(row)
	if err == nil {
		return item, nil
	}

	base := slugify(current.Name)
	if base == "" {
		base = fmt.Sprintf("user-%d", current.ID)
	}
	for i := 0; i < 20; i++ {
		slug := base
		if i > 0 {
			slug = fmt.Sprintf("%s-%d", base, i+1)
		}
		row = s.db.QueryRowContext(ctx, `
			INSERT INTO links (user_id, slug, display_name, bio, avatar_url, theme_config, is_published, has_web_display)
			VALUES ($1, $2, $3, $4, $5, '{"theme":"light","widget_style":"rounded"}', TRUE, FALSE)
			RETURNING id, user_id, slug, display_name, bio, avatar_url, theme_config, is_published, has_web_display, is_accepting_messages, message_settings
		`, current.ID, slug, current.Name, nil, current.AvatarURL)
		item, err = scanLink(row)
		if err == nil {
			return item, nil
		}
	}
	return nil, errors.New("could not create link")
}

func (s *server) findLinkBySlug(ctx context.Context, slug string) (*link, error) {
	row := s.db.QueryRowContext(ctx, `
		SELECT id, user_id, slug, display_name, bio, avatar_url, theme_config, is_published, has_web_display, is_accepting_messages, message_settings
		FROM links
		WHERE slug = $1
	`, slug)
	return scanLink(row)
}

func (s *server) widgetsForLink(ctx context.Context, linkID string) ([]widget, error) {
	rows, err := s.db.QueryContext(ctx, `
		SELECT id, link_id, type, content, thumbnail_url, x, y, w, h, x_mobile, y_mobile, w_mobile, h_mobile, settings
		FROM widgets
		WHERE link_id = $1
		ORDER BY y, x
	`, linkID)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	var widgets []widget
	for rows.Next() {
		var item widget
		if err := rows.Scan(&item.ID, &item.LinkID, &item.Type, &item.Content, &item.ThumbnailURL, &item.X, &item.Y, &item.W, &item.H, &item.XMobile, &item.YMobile, &item.WMobile, &item.HMobile, &item.Settings); err != nil {
			return nil, err
		}
		widgets = append(widgets, item)
	}
	return widgets, rows.Err()
}

type rowScanner interface {
	Scan(dest ...any) error
}

func scanLink(row rowScanner) (*link, error) {
	var item link
	err := row.Scan(&item.ID, &item.UserID, &item.Slug, &item.DisplayName, &item.Bio, &item.AvatarURL, &item.ThemeConfig, &item.IsPublished, &item.HasWebDisplay, &item.IsAcceptingMessages, &item.MessageSettings)
	if err != nil {
		return nil, err
	}
	return &item, nil
}

func runMigrations(db *sql.DB) error {
	if _, err := db.Exec(`
		CREATE TABLE IF NOT EXISTS schema_migrations (
			name TEXT PRIMARY KEY,
			applied_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
		)
	`); err != nil {
		return err
	}
	entries, err := os.ReadDir("migrations")
	if err != nil {
		if errors.Is(err, os.ErrNotExist) {
			return nil
		}
		return err
	}
	for _, entry := range entries {
		if entry.IsDir() || !strings.HasSuffix(entry.Name(), ".sql") {
			continue
		}
		var exists bool
		if err := db.QueryRow(`SELECT EXISTS(SELECT 1 FROM schema_migrations WHERE name = $1)`, entry.Name()).Scan(&exists); err != nil {
			return err
		}
		if exists {
			continue
		}
		body, err := os.ReadFile(filepath.Join("migrations", entry.Name()))
		if err != nil {
			return err
		}
		tx, err := db.Begin()
		if err != nil {
			return err
		}
		if _, err := tx.Exec(string(body)); err != nil {
			_ = tx.Rollback()
			return fmt.Errorf("migration %s failed: %w", entry.Name(), err)
		}
		if _, err := tx.Exec(`INSERT INTO schema_migrations (name) VALUES ($1)`, entry.Name()); err != nil {
			_ = tx.Rollback()
			return err
		}
		if err := tx.Commit(); err != nil {
			return err
		}
		log.Printf("applied migration %s", entry.Name())
	}
	return nil
}

func shouldExclude(r *http.Request, current *user, ownerID int64) bool {
	if current != nil && current.ID == ownerID {
		return true
	}
	agent := strings.ToLower(r.UserAgent())
	if agent == "" {
		return true
	}
	bots := []string{"bot", "crawler", "spider", "slurp", "search", "fetch", "mediapartners", "lighthouse", "google", "bing", "yandex", "baidu", "duckduckbot"}
	for _, bot := range bots {
		if strings.Contains(agent, bot) {
			return true
		}
	}
	return false
}

func clientIP(r *http.Request) string {
	if forwarded := strings.TrimSpace(r.Header.Get("X-Forwarded-For")); forwarded != "" {
		parts := strings.Split(forwarded, ",")
		return strings.TrimSpace(parts[0])
	}
	host := r.RemoteAddr
	if index := strings.LastIndex(host, ":"); index > -1 {
		return host[:index]
	}
	return host
}

func fetchOGP(ctx context.Context, target string) (map[string]any, error) {
	parsed, err := url.ParseRequestURI(target)
	if err != nil || parsed.Scheme == "" || parsed.Host == "" {
		return nil, errors.New("invalid url")
	}
	ctx, cancel := context.WithTimeout(ctx, 3*time.Second)
	defer cancel()
	req, _ := http.NewRequestWithContext(ctx, http.MethodGet, target, nil)
	req.Header.Set("User-Agent", "Mozilla/5.0 (compatible; GridLinkBot/1.0; +https://grid.local)")
	req.Header.Set("Accept-Language", "ja,en;q=0.8")
	resp, err := http.DefaultClient.Do(req)
	if err != nil {
		return nil, err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(io.LimitReader(resp.Body, 512*1024))
	htmlText := string(body)
	title := parsed.Host
	if match := metaContent(htmlText, "og:title"); match != "" {
		title = match
	} else if match := regexp.MustCompile(`(?is)<title[^>]*>(.*?)</title>`).FindStringSubmatch(htmlText); len(match) > 1 {
		title = strings.TrimSpace(html.UnescapeString(match[1]))
	}
	thumbnail := metaContent(htmlText, "og:image")
	if thumbnail != "" {
		thumbnail = resolveURL(parsed, thumbnail)
	}
	if isYouTubeChannelURL(parsed) {
		if youtubeTitle, youtubeThumbnail := youtubeChannelMetadata(htmlText); youtubeTitle != "" || youtubeThumbnail != "" {
			if youtubeTitle != "" {
				title = youtubeTitle
			}
			if youtubeThumbnail != "" {
				thumbnail = resolveURL(parsed, youtubeThumbnail)
			}
		}
	}
	return map[string]any{"title": title, "thumbnail_url": nullableString(thumbnail), "url": target}, nil
}

func metaContent(text string, property string) string {
	patterns := []*regexp.Regexp{
		regexp.MustCompile(`(?is)<meta[^>]*(?:property|name)=["']` + regexp.QuoteMeta(property) + `["'][^>]*content=["']([^"']+)["'][^>]*>`),
		regexp.MustCompile(`(?is)<meta[^>]*content=["']([^"']+)["'][^>]*(?:property|name)=["']` + regexp.QuoteMeta(property) + `["'][^>]*>`),
	}
	for _, pattern := range patterns {
		if match := pattern.FindStringSubmatch(text); len(match) > 1 {
			return strings.TrimSpace(html.UnescapeString(match[1]))
		}
	}
	return ""
}

func isYouTubeChannelURL(parsed *url.URL) bool {
	host := strings.ToLower(strings.TrimPrefix(parsed.Hostname(), "www."))
	host = strings.TrimPrefix(host, "m.")
	if host != "youtube.com" {
		return false
	}
	parts := strings.Split(strings.Trim(parsed.Path, "/"), "/")
	if len(parts) == 0 || parts[0] == "" {
		return false
	}
	return strings.HasPrefix(parts[0], "@") || parts[0] == "channel" || parts[0] == "c" || parts[0] == "user"
}

func youtubeChannelMetadata(text string) (string, string) {
	title := ""
	thumbnail := ""
	if match := regexp.MustCompile(`(?is)"channelMetadataRenderer"\s*:\s*\{\s*"title"\s*:\s*"((?:\\.|[^"\\])*)"`).FindStringSubmatch(text); len(match) > 1 {
		title = strings.TrimSpace(unquoteJSONString(match[1]))
	}
	if title == "" {
		title = strings.TrimSpace(strings.TrimSuffix(metaContent(text, "og:title"), " - YouTube"))
	}
	if title == "" {
		title = strings.TrimSpace(strings.TrimSuffix(metaContent(text, "twitter:title"), " - YouTube"))
	}

	if index := strings.Index(text, `"channelMetadataRenderer"`); index > -1 {
		section := text[index:]
		if avatarIndex := strings.Index(section, `"avatar"`); avatarIndex > -1 {
			avatarSection := section[avatarIndex:]
			if endIndex := strings.Index(avatarSection, `]`); endIndex > -1 {
				avatarSection = avatarSection[:endIndex]
			}
			for _, match := range regexp.MustCompile(`"url"\s*:\s*"((?:\\.|[^"\\])*)"`).FindAllStringSubmatch(avatarSection, -1) {
				if len(match) > 1 {
					thumbnail = strings.TrimSpace(unquoteJSONString(match[1]))
				}
			}
		}
	}
	if thumbnail == "" {
		thumbnail = metaContent(text, "og:image")
	}
	if thumbnail == "" {
		thumbnail = metaContent(text, "twitter:image")
	}
	return title, thumbnail
}

func unquoteJSONString(value string) string {
	unquoted, err := strconv.Unquote(`"` + value + `"`)
	if err != nil {
		return html.UnescapeString(strings.ReplaceAll(value, `\/`, `/`))
	}
	return html.UnescapeString(unquoted)
}

func resolveURL(base *url.URL, value string) string {
	if value == "" {
		return ""
	}
	parsedValue, err := url.Parse(value)
	if err != nil {
		return value
	}
	if parsedValue.IsAbs() {
		return parsedValue.String()
	}
	resolvedBase := *base
	resolvedBase.Path = "/"
	resolvedBase.RawQuery = ""
	resolvedBase.Fragment = ""
	if resolved, err := resolvedBase.Parse(value); err == nil {
		return resolved.String()
	}
	return value
}

func saveUpload(file multipart.File, header *multipart.FileHeader) (string, error) {
	if err := os.MkdirAll("uploads", 0o755); err != nil {
		return "", err
	}
	ext := strings.ToLower(filepath.Ext(header.Filename))
	allowedExtensions := map[string]bool{".jpg": true, ".jpeg": true, ".png": true, ".webp": true, ".gif": true, ".apng": true}
	if ext == "" {
		ext = ".jpg"
	}
	if !allowedExtensions[ext] {
		return "", errors.New("unsupported file type")
	}
	name := fmt.Sprintf("%d-%s%s", time.Now().UnixNano(), randomToken(8), ext)
	path := filepath.Join("uploads", name)
	out, err := os.Create(path)
	if err != nil {
		return "", err
	}
	defer out.Close()
	if _, err := io.Copy(out, io.LimitReader(file, 6<<20)); err != nil {
		return "", err
	}
	return "/uploads/" + name, nil
}

func randomToken(size int) string {
	bytes := make([]byte, size)
	if _, err := rand.Read(bytes); err != nil {
		panic(err)
	}
	return hex.EncodeToString(bytes)
}

func hashToken(token string) string {
	sum := sha256.Sum256([]byte(token))
	return hex.EncodeToString(sum[:])
}

func slugify(value string) string {
	value = strings.ToLower(strings.TrimSpace(value))
	re := regexp.MustCompile(`[^a-z0-9]+`)
	value = strings.Trim(re.ReplaceAllString(value, "-"), "-")
	if len(value) > 40 {
		value = strings.Trim(value[:40], "-")
	}
	return value
}

func nullable(value string) any {
	value = strings.TrimSpace(value)
	if value == "" {
		return nil
	}
	return value
}

func nullableString(value string) any {
	if strings.TrimSpace(value) == "" {
		return nil
	}
	return value
}

func nullableJSON(value json.RawMessage) any {
	if len(strings.TrimSpace(string(value))) == 0 {
		return nil
	}
	return value
}

func nullableJSONRaw(value *json.RawMessage) any {
	if value == nil || len(strings.TrimSpace(string(*value))) == 0 {
		return nil
	}
	return *value
}

func stringValue(value *json.RawMessage) string {
	if value == nil {
		return ""
	}
	return string(*value)
}

func validWidgetSettings(widgetType string, raw *json.RawMessage) bool {
	if raw == nil || len(strings.TrimSpace(string(*raw))) == 0 {
		return true
	}
	var settings map[string]any
	if err := json.Unmarshal(*raw, &settings); err != nil {
		return false
	}
	if title, ok := settings["title"].(string); ok {
		limit := 4500
		if widgetType == "link" {
			limit = 100
		}
		if len([]rune(title)) > limit {
			return false
		}
	}
	return true
}

func validWidget(item widget) bool {
	allowedTypes := map[string]bool{
		"link":    true,
		"image":   true,
		"text":    true,
		"section": true,
		"map":     true,
	}
	if !allowedTypes[item.Type] {
		return false
	}
	if item.X < 0 || item.Y < 0 || item.XMobile < 0 || item.YMobile < 0 {
		return false
	}
	if item.W < 1 || item.W > 4 || item.H < 1 || item.H > 12 {
		return false
	}
	if item.WMobile < 1 || item.WMobile > 2 || item.HMobile < 1 || item.HMobile > 12 {
		return false
	}
	return true
}

func writeJSON(w http.ResponseWriter, status int, data any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(data)
}

func env(key string, fallback string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return fallback
}
