#!/usr/bin/env bash
set -euo pipefail

echo "[1/5] Asset/view structure checks"
scripts/verify_view_assets.sh

echo "[2/5] Blade syntax checks"
php -l resources/views/pages/agenda/events/index.blade.php >/dev/null
php -l resources/views/pages/agenda/events/_form.blade.php >/dev/null
php -l resources/views/pages/monthly_activities/activities/index.blade.php >/dev/null
php -l resources/views/pages/monthly_activities/activities/_form.blade.php >/dev/null
php -l resources/views/pages/monthly_activities/activities/edit.blade.php >/dev/null
php -l resources/views/pages/monthly_activities/activities/show.blade.php >/dev/null

echo "[3/5] Shared integration checks"
rg -n "event-ui-shared\.css|ui-shared\.js" resources/views/pages/agenda/events/index.blade.php resources/views/pages/monthly_activities/activities/index.blade.php >/dev/null
rg -n "ZahaUi\.initViewToggle|ZahaUi\.readJsonScript" public/assets/js/agenda-events-index.js public/assets/js/monthly-activities-index.js public/assets/js/monthly-activity-form.js public/assets/js/monthly-activity-edit.js >/dev/null

echo "[4/5] JS syntax checks"
node -e "const fs=require('fs'); for (const f of fs.readdirSync('public/assets/js')) { if (f.endsWith('.js')) new Function(fs.readFileSync('public/assets/js/'+f,'utf8')); }"

echo "[5/5] Laravel runtime availability check"
if [[ -f vendor/autoload.php ]]; then
  php artisan --version >/dev/null
  echo "Laravel runtime check: OK"
else
  echo "WARNING: vendor/autoload.php not found; skipping artisan runtime checks" >&2
fi

echo "Validation finished successfully."
