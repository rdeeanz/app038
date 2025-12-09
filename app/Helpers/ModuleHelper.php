<?php

namespace App\Helpers;

class ModuleHelper
{
    /**
     * Get all available modules with their information
     *
     * @return array
     */
    public static function getModules(): array
    {
        return [
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'route' => '/dashboard',
                'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                'color' => 'indigo',
                'description' => 'Overview and statistics',
                'category' => 'main',
            ],
            [
                'name' => 'Sales',
                'slug' => 'sales',
                'route' => '/sales',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'color' => 'blue',
                'description' => 'Order management and sales tracking',
                'category' => 'business',
            ],
            [
                'name' => 'Inventory',
                'slug' => 'inventory',
                'route' => '/inventory',
                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                'color' => 'emerald',
                'description' => 'Product inventory and stock management',
                'category' => 'business',
            ],
            [
                'name' => 'ERP Integration',
                'slug' => 'erp-integration',
                'route' => '/integration-monitor',
                'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
                'color' => 'purple',
                'description' => 'SAP and ERP system integration',
                'category' => 'integration',
            ],
            [
                'name' => 'Mapping Editor',
                'slug' => 'mapping-editor',
                'route' => '/mapping-editor',
                'icon' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                'color' => 'cyan',
                'description' => 'YAML mapping configuration editor',
                'category' => 'integration',
            ],
            [
                'name' => 'Monitoring',
                'slug' => 'monitoring',
                'route' => '/monitoring',
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'color' => 'cyan',
                'description' => 'System health and performance monitoring',
                'category' => 'system',
            ],
            [
                'name' => 'Settings',
                'slug' => 'settings',
                'route' => '/settings',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                'color' => 'gray',
                'description' => 'Application settings and configuration',
                'category' => 'system',
                'requiresRole' => 'Super Admin',
            ],
        ];
    }

    /**
     * Get modules by category
     *
     * @return array
     */
    public static function getModulesByCategory(): array
    {
        $modules = self::getModules();
        $categorized = [];

        foreach ($modules as $module) {
            $category = $module['category'] ?? 'other';
            if (!isset($categorized[$category])) {
                $categorized[$category] = [];
            }
            $categorized[$category][] = $module;
        }

        return $categorized;
    }

    /**
     * Get module by slug
     *
     * @param string $slug
     * @return array|null
     */
    public static function getModule(string $slug): ?array
    {
        $modules = self::getModules();
        foreach ($modules as $module) {
            if ($module['slug'] === $slug) {
                return $module;
            }
        }
        return null;
    }
}

