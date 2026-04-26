#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

fail() {
  echo "[FAIL] $1" >&2
  exit 1
}

pass() {
  echo "[PASS] $1"
}

# 1) No Blade page should extend the legacy layout.
if rg -n "@extends\('layouts\.app'\)" resources/views -g '*.blade.php' >/dev/null; then
  fail "Found Blade views still extending layouts.app"
else
  pass "No Blade view extends layouts.app"
fi

# 2) Role pages should use the unified dashboard layout.
role_pages_count=$(find resources/views/roles -type f -name '*.blade.php' ! -path '*/partials/*' | wc -l | tr -d ' ')
role_new_layout_count=$(rg -n "@extends\('layouts\.new-theme-dashboard'\)" resources/views/roles -g '*.blade.php' | wc -l | tr -d ' ')
if [[ "$role_new_layout_count" -lt "$role_pages_count" ]]; then
  fail "Not all role pages use layouts.new-theme-dashboard ($role_new_layout_count/$role_pages_count)"
else
  pass "All role pages use layouts.new-theme-dashboard ($role_new_layout_count/$role_pages_count)"
fi

# 3) Layout should not contain hardcoded locale/theme labels.
if rg -n "\>\s*(English|العربية|Dark mode|Light mode)\s*\<" resources/views/layouts/new-theme-dashboard.blade.php >/dev/null; then
  fail "Found hardcoded locale/theme labels in new-theme-dashboard layout"
else
  pass "Layout locale/theme labels are translation-backed"
fi

# 4) Required dictionary keys exist in frontend locale JSONs.
for locale in ar en; do
  file="public/assets/new-theme/locales/$locale/common.json"
  rg -n '"switch_to_english"' "$file" >/dev/null || fail "Missing switch_to_english in $file"
  rg -n '"switch_to_arabic"' "$file" >/dev/null || fail "Missing switch_to_arabic in $file"
  pass "Locale keys exist in $file"
done

# 5) Required Laravel translation keys exist.
for file in resources/lang/ar/app.php resources/lang/en/app.php; do
  rg -n "'switch_to_english'" "$file" >/dev/null || fail "Missing switch_to_english in $file"
  rg -n "'switch_to_arabic'" "$file" >/dev/null || fail "Missing switch_to_arabic in $file"
  pass "Laravel translation keys exist in $file"
done

pass "Theme migration verification checks completed successfully"
