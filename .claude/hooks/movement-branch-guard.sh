#!/usr/bin/env bash
# PreToolUse guard (Edit|Write|MultiEdit|NotebookEdit): blocks writes to Movement module files
# unless the repository is on branch `movement-module`. See CLAUDE.md "Movement module — plan discipline".
# Exit 0 = allow, exit 2 = block (stderr is shown to Claude).
set -u

# Migrations dated strictly after this prefix are guarded (plan: protected migration cutoff).
PROTECTED_CUTOFF="2026_09_10_000100"

payload="$(cat)"
file_path="$(printf '%s' "$payload" | jq -r '.tool_input.file_path // .tool_input.notebook_path // empty' 2>/dev/null)"
[ -z "$file_path" ] && exit 0

repo_root="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null)}"
[ -z "$repo_root" ] && exit 0

# Path relative to the repository root; files outside the repository are not guarded.
case "$file_path" in
  "$repo_root"/*) rel="${file_path#"$repo_root"/}" ;;
  /*) exit 0 ;;
  *) rel="${file_path#./}" ;;
esac

guarded=0
case "$rel" in
  # Module code, routes and the plan.
  app/Modules/Movement/*|routes/movement.php|docs/movement-module-implementation-plan.md) guarded=1 ;;
  # Plan-discipline rules, skills, hooks and Claude settings.
  CLAUDE.md|.claude/*) guarded=1 ;;
  # Seeders that own Movement roles/permissions.
  database/seeders/Movement*|database/seeders/RolesSeeder.php|database/seeders/rightDatabaseSeeder.php) guarded=1 ;;
  # Artisan commands, config and tests.
  app/Console/Commands/Movement*|config/movement.php) guarded=1 ;;
  tests/*/Movement*|tests/Feature/DropLegacyFleetTripTablesMigrationTest.php) guarded=1 ;;
  # Any database file named after the module.
  database/*movement*|database/*Movement*) guarded=1 ;;
  # Migrations dated after the protected cutoff (includes the Phase 1 legacy drop migration).
  database/migrations/[0-9][0-9][0-9][0-9]_[0-9][0-9]_[0-9][0-9]_[0-9][0-9][0-9][0-9][0-9][0-9]_*)
    stamp="${rel#database/migrations/}"
    stamp="${stamp:0:17}"
    [[ "$stamp" > "$PROTECTED_CUTOFF" ]] && guarded=1
    ;;
esac
[ "$guarded" -eq 0 ] && exit 0

branch="$(git -C "$repo_root" branch --show-current 2>/dev/null)"
if [ "$branch" != "movement-module" ]; then
  echo "Blocked by movement-branch-guard: '$rel' is Movement module work and may only be edited on branch 'movement-module' (current: '${branch:-detached/unknown}'). See CLAUDE.md → 'Movement module — plan discipline'." >&2
  exit 2
fi
exit 0
