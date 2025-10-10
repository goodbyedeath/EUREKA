#!/bin/bash

# Smart build script for resource-constrained environments
# Tries multiple strategies with increasing resource conservation

echo "🔄 Starting smart build process..."

# Strategy 1: Normal build with low memory
echo "📦 Attempt 1: Standard build (128MB memory limit)..."
if NODE_OPTIONS="--max-old-space-size=128 --max-semi-space-size=64" timeout 60s npx vite build 2>/dev/null; then
    echo "✅ Build successful with standard settings"
    exit 0
fi

echo "⚠️ Standard build failed, waiting 10 seconds for resource recovery..."
sleep 10

# Strategy 2: Safe build with very low memory
echo "📦 Attempt 2: Safe build (64MB memory limit)..."
if NODE_OPTIONS="--max-old-space-size=64 --max-semi-space-size=32" timeout 45s npx vite build 2>/dev/null; then
    echo "✅ Build successful with safe settings"
    exit 0
fi

echo "⚠️ Safe build failed, waiting 15 seconds for resource recovery..."
sleep 15

# Strategy 3: Ultra-conservative build
echo "📦 Attempt 3: Ultra-conservative build (32MB memory limit)..."
if NODE_OPTIONS="--max-old-space-size=32 --max-semi-space-size=16" timeout 30s npx vite build 2>/dev/null; then
    echo "✅ Build successful with ultra-conservative settings"
    exit 0
fi

echo "❌ All build attempts failed. Server may be under heavy load."
echo "💡 Try again in a few minutes when server load is lower."
echo "🔍 Current server load is very high. Wait for lower system load and try again."
exit 1