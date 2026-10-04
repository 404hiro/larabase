package main

import (
	"net/url"
	"testing"
)

func TestIsYouTubeChannelURL(t *testing.T) {
	tests := []struct {
		rawURL string
		want   bool
	}{
		{"https://www.youtube.com/@gridlink", true},
		{"https://youtube.com/channel/UC123", true},
		{"https://m.youtube.com/c/GridLink", true},
		{"https://www.youtube.com/user/GridLink", true},
		{"https://www.youtube.com/watch?v=abc123", false},
		{"https://youtu.be/abc123", false},
	}

	for _, tt := range tests {
		parsed, err := url.Parse(tt.rawURL)
		if err != nil {
			t.Fatalf("parse url: %v", err)
		}
		if got := isYouTubeChannelURL(parsed); got != tt.want {
			t.Fatalf("isYouTubeChannelURL(%q) = %v, want %v", tt.rawURL, got, tt.want)
		}
	}
}

func TestYouTubeChannelMetadata(t *testing.T) {
	htmlText := `
		<html>
			<head><meta property="og:title" content="Fallback - YouTube"></head>
			<script>
				var ytInitialData = {
					"metadata": {
						"channelMetadataRenderer": {
							"title": "Grid Channel",
							"avatar": {
								"thumbnails": [
									{"url": "https:\/\/yt3.ggpht.com\/small=s88"},
									{"url": "https:\/\/yt3.ggpht.com\/large=s176"}
								]
							}
						}
					}
				};
			</script>
		</html>`

	title, thumbnail := youtubeChannelMetadata(htmlText)
	if title != "Grid Channel" {
		t.Fatalf("title = %q, want Grid Channel", title)
	}
	if thumbnail != "https://yt3.ggpht.com/large=s176" {
		t.Fatalf("thumbnail = %q, want largest avatar url", thumbnail)
	}
}
