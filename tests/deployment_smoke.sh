#!/usr/bin/env bash
# Deployment smoke test — exercises a freshly deployed NorthWest instance
# over HTTP only. Works against any base URL (local harness or a real host).
#
#   ./tests/deployment_smoke.sh http://127.0.0.1:8080 /path/to/extracted/app
#
# Requirements: bash, curl, grep. No PHP CLI, no MySQL CLI, no Terminal access
# on the target host — the test only performs browser-equivalent requests.
set -euo pipefail
BASE=${1:?base URL required (e.g. http://127.0.0.1:8080)}
ROOT=${2:?extracted application root required}
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

# 1. Homepage loads
for i in {1..30}; do curl -fsS "$BASE/" -o "$TMP/home.html" && break; sleep 1; done
grep -q 'Banking that moves with you' "$TMP/home.html"

# 2. Customer login (captcha shown on the page, then credentials)
curl -fsS -c "$TMP/customer.cookies" "$BASE/user/login" -o "$TMP/login.html"
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/login.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
CODE=$(grep -o '<div class="captcha"><strong>[^<]*' "$TMP/login.html" | sed 's/.*<strong>//;s/ //g')
test -n "$TOKEN"; test -n "$CODE"
curl -fsS -b "$TMP/customer.cookies" -c "$TMP/customer.cookies" --data-urlencode "nw_csrf_token=$TOKEN" --data-urlencode "code=$CODE" "$BASE/verify" -o /dev/null
curl -fsS -b "$TMP/customer.cookies" -c "$TMP/customer.cookies" "$BASE/user/login?credentials=1" -o "$TMP/credentials.html"
grep -q 'Account number or email' "$TMP/credentials.html"
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/credentials.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
curl -fsS -b "$TMP/customer.cookies" -c "$TMP/customer.cookies" --data-urlencode "nw_csrf_token=$TOKEN" --data-urlencode "identity=james.davidson@example.com" --data-urlencode "password=Demo@12345" "$BASE/auth/customer_login" -o /dev/null
curl -fsS -b "$TMP/customer.cookies" "$BASE/dashboard" -o "$TMP/dashboard.html"
grep -q 'Dashboard' "$TMP/dashboard.html"

# 3. Session persisted and settings page works
curl -fsS -b "$TMP/customer.cookies" "$BASE/settings" -o "$TMP/settings.html"
grep -q 'Personal information' "$TMP/settings.html"

# 4. Avatar upload lands in the extracted package's writable directory
base64 -d > "$TMP/avatar.png" <<'PNG'
iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=
PNG
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/settings.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
curl -fsS -L -b "$TMP/customer.cookies" -c "$TMP/customer.cookies" \
  -F "nw_csrf_token=$TOKEN" -F first_name=James -F last_name=Davidson \
  -F email=james.davidson@example.com -F phone='+1 212 555 0187' \
  -F city='New York' -F country='United States' -F address='284 Park Avenue' \
  -F "avatar=@$TMP/avatar.png;type=image/png" \
  "$BASE/settings" -o "$TMP/upload-result.html"
grep -q 'Profile updated' "$TMP/upload-result.html"
test -n "$(find "$ROOT/assets/uploads" -maxdepth 1 -type f -name '*.png' | head -1)"

# 5. Administrator login (included in production.sql — change after first run)
curl -fsS -c "$TMP/admin.cookies" "$BASE/login" -o "$TMP/admin.html"
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/admin.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
curl -fsS -L -b "$TMP/admin.cookies" -c "$TMP/admin.cookies" \
  --data-urlencode "nw_csrf_token=$TOKEN" --data-urlencode identity=northadmin \
  --data-urlencode "password=Admin@12345" "$BASE/login" -o "$TMP/admin-dashboard.html"
grep -q 'Operations overview' "$TMP/admin-dashboard.html"

# 6. Sessions were written server-side
test -n "$(find "$ROOT/assets/logs/sessions" -type f 2>/dev/null | head -1)"

echo "SMOKE TEST PASSED"
