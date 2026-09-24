#!/bin/bash

# Smart build script that waits for optimal server conditions
# Usage: ./build-when-ready.sh

echo "🔍 Monitoring server load for optimal build conditions..."

MAX_ATTEMPTS=10
ATTEMPT=0
TARGET_LOAD=8.0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    CURRENT_LOAD=$(cat /proc/loadavg | cut -d' ' -f1)
    LOAD_INT=${CURRENT_LOAD%.*}
    
    echo "📊 Current load: $CURRENT_LOAD (attempt $((ATTEMPT + 1))/$MAX_ATTEMPTS)"
    
    # Check if load is acceptable (less than 8.0)
    if (( $(echo "$CURRENT_LOAD < $TARGET_LOAD" | bc -l) )); then
        echo "✅ Server load acceptable ($CURRENT_LOAD < $TARGET_LOAD). Starting build..."
        
        # Try the optimized build
        npm run build-single
        BUILD_STATUS=$?
        
        if [ $BUILD_STATUS -eq 0 ]; then
            echo "🎉 Build successful!"
            exit 0
        else
            echo "❌ Build failed even with acceptable load. Server may be under stress."
        fi
    else
        echo "⏳ Server load too high ($CURRENT_LOAD). Waiting 60 seconds..."
        sleep 60
    fi
    
    ATTEMPT=$((ATTEMPT + 1))
done

echo "⚠️  Maximum attempts reached. Consider using no-build mode:"
echo "   1. Change APP_ENV=local in .env"
echo "   2. Use: php artisan serve"
echo "   3. Your app works without building!"

exit 1