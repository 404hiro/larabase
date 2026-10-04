const baseUrl = process.env.SMOKE_BASE_URL || 'http://localhost:5173';
const profilePath = process.env.SMOKE_PROFILE_PATH || '/';
const paths = ['/', '/login', profilePath];

for (const path of [...new Set(paths)]) {
  const url = new URL(path, baseUrl);
  const response = await fetch(url);
  if (!response.ok) {
    throw new Error(`${url} returned ${response.status}`);
  }
  const body = await response.text();
  if (!body.includes('<div id="app">')) {
    throw new Error(`${url} did not return the Vue shell`);
  }
  console.log(`ok ${url}`);
}
