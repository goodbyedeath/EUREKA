#!/bin/bash

# EUREKA App Redundant File Cleanup Script
# This script identifies and removes redundant files safely

echo "🧹 EUREKA Redundant File Cleanup"
echo "=================================="

# Create backup directory
BACKUP_DIR="./cleanup-backup-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"
echo "📦 Created backup directory: $BACKUP_DIR"

# Function to safely remove file with backup
safe_remove() {
    local file="$1"
    local reason="$2"
    
    if [ -f "$file" ]; then
        echo "🗑️  Removing: $file ($reason)"
        cp "$file" "$BACKUP_DIR/$(basename $file).backup" 2>/dev/null
        rm "$file"
        echo "   ✅ Backed up and removed"
    else
        echo "   ⚠️  File not found: $file"
    fi
}

# Function to safely remove directory with backup
safe_remove_dir() {
    local dir="$1"
    local reason="$2"
    
    if [ -d "$dir" ]; then
        echo "🗑️  Removing directory: $dir ($reason)"
        cp -r "$dir" "$BACKUP_DIR/$(basename $dir)_backup" 2>/dev/null
        rm -rf "$dir"
        echo "   ✅ Backed up and removed"
    else
        echo "   ⚠️  Directory not found: $dir"
    fi
}

echo ""
echo "1. 🔍 Identifying Duplicate Files..."
echo "====================================="

# Remove duplicate JavaScript files (keeping resources/ versions as source)
echo ""
echo "📜 JavaScript Duplicates:"
safe_remove "public/js/quiz-timer-manual.js" "Duplicate of resources/js/quiz-timer.js"
safe_remove "public/js/session-timeout.js" "Duplicate of resources/js/session-timeout.js"
safe_remove "public/js/simple-session-timer.js" "Duplicate of resources/js/simple-session-timer.js"

echo ""
echo "2. 🗂️  Backup Files..."
echo "======================"

# Remove backup files
safe_remove "public/sw.js.backup" "Service worker backup (replaced with enhanced version)"
safe_remove ".env.backup.20250702_184626" "Old environment backup"

echo ""
echo "3. 📋 Log Files..."
echo "=================="

# Clean up redundant log files (keep main ones)
safe_remove "public/error_log" "Duplicate error log"
safe_remove_dir "public/ClaragonwwwEUREKAstorageframeworkviews" "Development cache directory"
safe_remove_dir "public/ClaragonwwwEUREKAstoragelogs" "Development log directory"

echo ""
echo "4. 🏗️  Development Files..."
echo "============================"

# Remove development-only files from production
safe_remove "activate-offline.js" "Development activation script"

echo ""
echo "5. 📄 Documentation Consolidation..."
echo "====================================="

# Optional: Remove redundant documentation (keep essential ones)
echo "📋 Found documentation files:"
find . -maxdepth 1 -name "*.md" | while read -r doc; do
    echo "   📄 $doc"
done

echo ""
echo "   💡 Recommendation: Keep essential docs (README.md, OFFLINE_GUIDE.md)"
echo "   💡 Consider removing: BUILD_INSTRUCTIONS.md, PANDUAN_PENGGUNA.md if not needed"

echo ""
echo "6. 🎯 Compilation Assets..."
echo "============================"

# Check for uncompiled assets
if [ -d "public/build" ]; then
    echo "✅ Compiled assets found in public/build"
else
    echo "⚠️  No compiled assets found - run 'npm run build'"
fi

echo ""
echo "7. 🔍 Large Files Analysis..."
echo "=============================="

echo "📊 Largest directories:"
du -sh node_modules vendor storage public 2>/dev/null | sort -hr

echo ""
echo "📊 File type counts:"
echo "   JavaScript: $(find . -name "*.js" | grep -v node_modules | wc -l) files"
echo "   CSS: $(find . -name "*.css" | grep -v node_modules | wc -l) files"
echo "   PHP: $(find . -name "*.php" | grep -v vendor | wc -l) files"
echo "   Blade: $(find . -name "*.blade.php" | wc -l) files"

echo ""
echo "8. 🧼 Cache Cleanup..."
echo "======================"

# Laravel cache cleanup
if [ -f "artisan" ]; then
    echo "🔧 Laravel cache cleanup..."
    php artisan config:clear 2>/dev/null && echo "   ✅ Config cache cleared"
    php artisan route:clear 2>/dev/null && echo "   ✅ Route cache cleared"
    php artisan view:clear 2>/dev/null && echo "   ✅ View cache cleared"
else
    echo "⚠️  Laravel artisan not found"
fi

echo ""
echo "🎉 Cleanup Summary"
echo "=================="
echo "✅ Removed duplicate files"
echo "✅ Cleaned up backup files"
echo "✅ Removed development artifacts"
echo "✅ Cleared Laravel caches"
echo ""
echo "📦 Backup created in: $BACKUP_DIR"
echo "🔄 To restore a file: cp $BACKUP_DIR/[filename].backup [original_location]"

# Calculate space saved
if [ -d "$BACKUP_DIR" ]; then
    SAVED_SPACE=$(du -sh "$BACKUP_DIR" | cut -f1)
    echo "💾 Space potentially saved: $SAVED_SPACE"
fi

echo ""
echo "🚀 Next Steps:"
echo "1. Test your application to ensure everything works"
echo "2. If all good, you can remove the backup directory"
echo "3. Consider running this cleanup periodically"
echo "4. Run 'npm run build' if assets were affected"

echo ""
echo "✨ Cleanup completed successfully!"