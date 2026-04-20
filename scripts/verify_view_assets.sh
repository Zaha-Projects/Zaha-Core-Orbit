#!/usr/bin/env bash
set -euo pipefail

TARGETS=(
  resources/views/pages/agenda/events/index.blade.php
  resources/views/pages/agenda/events/_form.blade.php
  resources/views/pages/monthly_activities/activities/index.blade.php
  resources/views/pages/monthly_activities/activities/_form.blade.php
  resources/views/pages/monthly_activities/activities/edit.blade.php
  resources/views/pages/monthly_activities/activities/show.blade.php
  resources/views/pages/monthly_activities/approvals/index.blade.php
  resources/views/pages/monthly_activities/lookups/admin.blade.php
)

echo "[1/3] Checking inline <style> blocks..."
if rg -n "<style>" "${TARGETS[@]}"; then
  echo "Found inline <style> blocks. Please extract to assets." >&2
  exit 1
fi

echo "[2/3] Checking script tags are only src/json bootstraps..."
# Allows <script src=...> and <script type="application/json" ...>
if rg -n "<script(?![^>]*(src=|type=\"application/json\"))" "${TARGETS[@]}" --pcre2; then
  echo "Found inline executable <script> blocks. Please extract to assets." >&2
  exit 1
fi

echo "[3/3] Checking shared assets are wired on index pages..."
rg -n "event-ui-shared\.css" resources/views/pages/agenda/events/index.blade.php resources/views/pages/monthly_activities/activities/index.blade.php >/dev/null
rg -n "ui-shared\.js" resources/views/pages/agenda/events/index.blade.php resources/views/pages/monthly_activities/activities/index.blade.php >/dev/null

echo "OK: view assets verification passed."
