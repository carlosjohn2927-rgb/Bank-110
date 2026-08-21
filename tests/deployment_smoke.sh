#!/usr/bin/env bash
set -euo pipefail
ROOT=${1:?extracted application root required}
BASE=http://127.0.0.1:8080
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

for i in {1..30}; do curl -fsS "$BASE/" -o "$TMP/login.html" && break; sleep 1; done
grep -q 'Welcome to NorthWest' "$TMP/login.html"

curl -fsS -c "$TMP/customer.cookies" "$BASE/login" -o "$TMP/login.html"
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/login.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
CODE=$(grep -o '<div class="captcha"><strong>[^<]*' "$TMP/login.html" | sed 's/.*<strong>//;s/ //g')
test -n "$TOKEN"; test -n "$CODE"
curl -fsS -L -b "$TMP/customer.cookies" -c "$TMP/customer.cookies" -d "nw_csrf_token=$TOKEN&code=$CODE" "$BASE/verify" -o "$TMP/credentials.html"
grep -q 'Account number or email' "$TMP/credentials.html"
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/credentials.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
curl -fsS -L -b "$TMP/customer.cookies" -c "$TMP/customer.cookies" --data-urlencode "nw_csrf_token=$TOKEN" --data-urlencode 'identity=james.davidson@example.com' --data-urlencode 'password=Demo@12345' "$BASE/auth/customer_login" -o "$TMP/dashboard.html"
grep -q 'Good morning, James' "$TMP/dashboard.html"
curl -fsS -b "$TMP/customer.cookies" "$BASE/settings" -o "$TMP/settings.html"
grep -q 'Personal information' "$TMP/settings.html"

# Verify uploads in the extracted package's writable directory.
base64 -d > "$TMP/avatar.png" <<'PNG'
iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=
PNG
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/settings.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
curl -fsS -L -b "$TMP/customer.cookies" -c "$TMP/customer.cookies" -F "nw_csrf_token=$TOKEN" -F first_name=James -F last_name=Davidson -F email=james.davidson@example.com -F phone='+1 212 555 0187' -F city='New York' -F country='United States' -F address='284 Park Avenue' -F "avatar=@$TMP/avatar.png;type=image/png" "$BASE/settings" -o "$TMP/upload-result.html"
grep -q 'Profile updated' "$TMP/upload-result.html"
find "$ROOT/assets/uploads" -type f -name '*.png' | grep -q .

# Give the seeded admin the known smoke-test hash without changing production.sql.
mysql --protocol=tcp -h127.0.0.1 -P3306 -unorthwest -pnorthwest northwest_test -e "UPDATE users SET password_hash=(SELECT customer_hash FROM (SELECT password_hash customer_hash FROM users WHERE email='james.davidson@example.com') x) WHERE username='northadmin'"
curl -fsS -c "$TMP/admin.cookies" "$BASE/admin" -o "$TMP/admin.html"
TOKEN=$(grep -o 'name="nw_csrf_token" value="[^"]*"' "$TMP/admin.html" | head -1 | sed 's/.*value="\([^"]*\)"/\1/')
curl -fsS -L -b "$TMP/admin.cookies" -c "$TMP/admin.cookies" --data-urlencode "nw_csrf_token=$TOKEN" --data-urlencode identity=northadmin --data-urlencode password=Demo@12345 "$BASE/admin" -o "$TMP/admin-dashboard.html"
grep -q 'Operations overview' "$TMP/admin-dashboard.html"

test -n "$(find "$ROOT/assets/logs/sessions" -type f | head -1)"
mysql --protocol=tcp -h127.0.0.1 -P3306 -unorthwest -pnorthwest northwest_test -Nse "SELECT setting_value FROM settings WHERE setting_key='application_initialized'" | grep -qx 1
mysql --protocol=tcp -h127.0.0.1 -P3306 -unorthwest -pnorthwest northwest_test -Nse "SELECT COUNT(*) FROM users WHERE role='admin'" | grep -Eq '^[1-9][0-9]*$'
echo 'Portable clean-deployment runtime smoke test: PASS'
