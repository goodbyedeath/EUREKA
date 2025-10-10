#\!/bin/bash

echo "🔧 Starting progressive build process..."

# Check if existing build is working
if [ -d "public/build" ] && [ -f "public/build/manifest.json" ]; then
    echo "✅ Build directory exists with manifest. Skipping rebuild."
    exit 0
fi

echo "🧹 Cleaning old build files..."
rm -rf public/build/
rm -rf node_modules/.vite/

echo "⚡ Attempting build with different memory configurations..."

# Try different memory configurations in order of preference
memory_configs=(
    "NODE_OPTIONS=\"--max-old-space-size=256\""
    "NODE_OPTIONS=\"--max-old-space-size=192\""
    "NODE_OPTIONS=\"--max-old-space-size=128\""
    "NODE_OPTIONS=\"--max-old-space-size=96\""
)

for config in "${memory_configs[@]}"; do
    echo "🔄 Trying: $config"
    
    if eval "$config npx vite build" 2>/dev/null; then
        echo "✅ Build successful with: $config"
        echo "📁 Verifying build output..."
        
        if [ -f "public/build/manifest.json" ]; then
            echo "✅ Build verification successful\!"
            echo "📊 Build summary:"
            ls -la public/build/assets/ | head -5
            exit 0
        else
            echo "❌ Build verification failed - no manifest found"
        fi
    else
        echo "❌ Build failed with: $config"
    fi
    
    # Clean up failed attempts
    rm -rf public/build/ 2>/dev/null
    sleep 2
done

echo "❌ All build attempts failed. The server may be under heavy load."
echo "💡 Try running 'npm run build' manually later when server load is lower."
exit 1
EOF < /dev/null
