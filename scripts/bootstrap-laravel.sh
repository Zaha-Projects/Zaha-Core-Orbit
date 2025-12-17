#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ -f "$ROOT_DIR/artisan" ]]; then
  echo "Laravel project already exists at $ROOT_DIR"
  exit 0
fi

TMP_DIR="$(mktemp -d)"
cleanup() {
  rm -rf "$TMP_DIR"
}
trap cleanup EXIT

echo "Creating Laravel project in temporary directory: $TMP_DIR"
composer create-project laravel/laravel "$TMP_DIR/app"

echo "Copying files into repository root..."
rsync -a --exclude='.git' "$TMP_DIR/app/" "$ROOT_DIR/"

echo "Bootstrap complete. You can now configure .env and run php artisan key:generate"
