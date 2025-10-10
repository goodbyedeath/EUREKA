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
     * Get feature dashboard cards data
     */
    public function getFeatureDashboardData(): array
    {
        $features = $this->getEnabledFeatures();
        $dashboardCards = [];

        foreach ($features as $feature) {
            try {
                $metadata = $feature->metadata ?? [];
                
                // Ensure metadata is an array
                if (is_string($metadata)) {
                    $metadata = json_decode($metadata, true) ?? [];
                }
                
                // Skip features without route information or that are placeholders
                if (!isset($metadata['route']) || $metadata['route'] === null) {
                    continue;
                }
            } catch (\Exception $e) {
                \Log::warning('Error processing feature metadata: ' . $e->getMessage(), ['feature_id' => $feature->id]);
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
}