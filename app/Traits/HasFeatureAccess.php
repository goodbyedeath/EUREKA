<?php

namespace App\Traits;

use App\Models\FeatureSetting;

trait HasFeatureAccess
{
    /**
     * Check if a feature is enabled
     */
    public function featureEnabled(string $featureKey): bool
    {
        return FeatureSetting::isEnabled($featureKey);
    }

    /**
     * Get enabled features for the dashboard
     */
    public function getEnabledFeatures()
    {
        return FeatureSetting::getEnabledFeatures();
    }

    /**
     * Get feature metadata
     */
    public function getFeatureMetadata(string $featureKey): ?array
    {
        return FeatureSetting::getFeatureMetadata($featureKey);
    }

    /**
     * Check multiple features at once
     */
    public function featuresEnabled(array $featureKeys): array
    {
        $result = [];
        foreach ($featureKeys as $key) {
            $result[$key] = $this->featureEnabled($key);
        }
        return $result;
    }

    /**
     * Get feature dashboard cards data
     */
    public function getFeatureDashboardData(): array
    {
        $features = $this->getEnabledFeatures();
        $dashboardCards = [];

        foreach ($features as $feature) {
            $metadata = $feature->metadata ?? [];
            
            // Skip features without route information or that are placeholders
            if (!isset($metadata['route']) || $metadata['route'] === null) {
                continue;
            }

            $dashboardCards[] = [
                'title' => $feature->feature_name,
                'description' => $feature->description,
                'icon' => $metadata['icon'] ?? 'fas fa-cog',
                'color' => $metadata['color'] ?? 'gray',
                'route' => $metadata['route'],
                'enabled' => $feature->is_enabled,
                'sort_order' => $feature->sort_order
            ];
        }

        // Sort by sort_order
        usort($dashboardCards, function ($a, $b) {
            return $a['sort_order'] <=> $b['sort_order'];
        });

        return $dashboardCards;
    }

    /**
     * Get color classes for feature cards
     */
    public function getFeatureColorClasses(string $color): array
    {
        $colorMaps = [
            'blue' => [
                'bg' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
                'icon' => 'bg-blue-600 text-white',
                'text' => 'text-blue-600 dark:text-blue-400'
            ],
            'green' => [
                'bg' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
                'icon' => 'bg-green-600 text-white',
                'text' => 'text-green-600 dark:text-green-400'
            ],
            'purple' => [
                'bg' => 'bg-purple-50 dark:bg-purple-900/20 border-purple-200 dark:border-purple-800',
                'icon' => 'bg-purple-600 text-white',
                'text' => 'text-purple-600 dark:text-purple-400'
            ],
            'indigo' => [
                'bg' => 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-200 dark:border-indigo-800',
                'icon' => 'bg-indigo-600 text-white',
                'text' => 'text-indigo-600 dark:text-indigo-400'
            ],
            'yellow' => [
                'bg' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
                'icon' => 'bg-yellow-600 text-white',
                'text' => 'text-yellow-600 dark:text-yellow-400'
            ],
            'red' => [
                'bg' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
                'icon' => 'bg-red-600 text-white',
                'text' => 'text-red-600 dark:text-red-400'
            ]
        ];

        return $colorMaps[$color] ?? $colorMaps['blue'];
    }
}