#!/bin/bash

# Script to generate translation file templates for a new locale
# Usage: ./bin/generate-locale-templates.sh <locale_code>
# Example: ./bin/generate-locale-templates.sh de

set -e

if [ -z "$1" ]; then
    echo "Usage: $0 <locale_code>"
    echo "Example: $0 de"
    exit 1
fi

LOCALE=$1
TRANSLATIONS_DIR="translations"

# Check if locale already exists
if [ -f "$TRANSLATIONS_DIR/app.$LOCALE.yaml" ]; then
    echo "Translation files for locale '$LOCALE' already exist!"
    echo "Remove them first if you want to regenerate."
    exit 1
fi

echo "Generating translation templates for locale: $LOCALE"
echo "---"

# Define all translation domains
declare -a DOMAINS=("app" "login" "dashboard" "bank_account" "transaction" "flash")

for DOMAIN in "${DOMAINS[@]}"; do
    SOURCE_FILE="$TRANSLATIONS_DIR/${DOMAIN}.en.yaml"
    TARGET_FILE="$TRANSLATIONS_DIR/${DOMAIN}.${LOCALE}.yaml"
    
    if [ ! -f "$SOURCE_FILE" ]; then
        echo "Warning: Source file $SOURCE_FILE not found, skipping..."
        continue
    fi
    
    # Copy English file as template and add header comment
    echo "# ${DOMAIN^} translations for locale: $LOCALE" > "$TARGET_FILE"
    echo "# TODO: Translate all values below" >> "$TARGET_FILE"
    tail -n +2 "$SOURCE_FILE" >> "$TARGET_FILE"
    
    echo "✓ Created: $TARGET_FILE"
done

echo "---"
echo "Translation templates created successfully!"
echo ""
echo "Next steps:"
echo "1. Edit the generated .yaml files in $TRANSLATIONS_DIR/ directory"
echo "2. Translate all values (keep the keys unchanged)"
echo "3. Update src/UserManagement/Domain/ValueObject/Locale.php - add new enum case"
echo "4. Update src/UserManagement/Symfony/Twig/LocaleExtension.php - add flag and name"
echo "5. Create database migration to update CHECK constraint"
echo "6. Rebuild containers and run tests"
echo ""
echo "See docs/ADDING_LOCALES.md for detailed instructions."
