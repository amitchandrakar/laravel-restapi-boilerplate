#!/bin/sh

# Stop execution on error
set -e

echo "Starting Project Setup..."

# 1. Install Dependencies
echo "Installing Composer dependencies..."
composer install

echo "Installing NPM dependencies (Prettier)..."
npm install

# 2. Environment Setup
if [ ! -f .env ]; then
    echo "Copying .env.local to .env..."
    cp .env.local .env
else
    echo "Warning: .env file already exists. Skipping copy."
fi

# 3. Generate App Key
echo "Generating Application Key..."
# We run this even if .env exists to ensure a key is present, but usually it only updates if missing. 
# passing --force would overwrite, but we'll stick to default behavior or check if key is set?
# The standard behavior is it updates if present. Let's just run it.
php artisan key:generate

# 4. Setup Git Hooks
echo "Setting up Git Hooks..."
if [ -d "git-hooks" ]; then
    cp git-hooks/pre-commit .git/hooks/pre-commit
    chmod +x .git/hooks/pre-commit
    echo "   [OK] Git pre-commit hook installed."
else
    echo "   [ERROR] Error: git-hooks directory not found."
    exit 1
fi

# 5. Verify Setup (Pint & Larastan)
echo "Verifying setup with Pint and Larastan..."

# Function to run check but not exit strict script on failure so we can report all status
run_check() {
    "$@"
    local status=$?
    if [ $status -eq 0 ]; then
        echo "   [OK] '$1' passed."
    else
        echo "   [WARN] '$1' found issues."
    fi
}

# We use 'composer lint' (check only) and 'composer analyse'
# note: set -e will abort if these fail, so we might want to temporarily disable it or use '|| true'
set +e
composer lint
LINT_STATUS=$?
composer analyse
ANALYSE_STATUS=$?
echo "   Checking Prettier formatting..."
npx prettier --check . > /dev/null 2>&1
PRETTIER_STATUS=$?
set -e

if [ $LINT_STATUS -eq 0 ] && [ $ANALYSE_STATUS -eq 0 ] && [ $PRETTIER_STATUS -eq 0 ]; then
    echo "[OK] All verification checks passed!"
else
    echo "[WARN] Some verification checks found issues. See output above."
fi

echo "Project setup completed successfully!"
