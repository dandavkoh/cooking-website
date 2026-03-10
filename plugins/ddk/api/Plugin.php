<?php

namespace Ddk\Api;

use System\Classes\PluginBase;
use Backend\Models\UserRole;
use Ddk\Api\Console\GenerateTokenCommand;

/**
 * API Plugin for exposing Winter CMS data via REST endpoints.
 *
 * This plugin provides a robust, extensible API layer for managing
 * content programmatically. It follows Winter CMS conventions and
 * can be extended with additional endpoints for blogs, products, etc.
 */
class Plugin extends PluginBase
{
    /**
     * Plugin dependencies
     */
    public $require = [
        'Ddk.Recipe',
        'Winter.User',
    ];

    /**
     * Returns information about this plugin.
     */
    public function pluginDetails(): array
    {
        return [
            'name'        => 'ddk.api::lang.plugin.name',
            'description' => 'ddk.api::lang.plugin.description',
            'author'      => 'DDK',
            'icon'        => 'icon-plug',
        ];
    }

    /**
     * Boot method, called after all plugins are registered.
     */
    public function boot(): void
    {
        // Routes are loaded automatically from routes.php
    }

    /**
     * Register method, called when the plugin is first registered.
     */
    public function register(): void
    {
        $this->registerConsoleCommand('api.generate-token', GenerateTokenCommand::class);
    }

    /**
     * Registers the permissions provided by this plugin.
     */
    public function registerPermissions(): array
    {
        return [
            'ddk.api.access_api' => [
                'tab'   => 'ddk.api::lang.permissions.tab',
                'label' => 'ddk.api::lang.permissions.access_api',
                'roles' => [UserRole::CODE_DEVELOPER],
            ],
            'ddk.api.manage_recipes' => [
                'tab'   => 'ddk.api::lang.permissions.tab',
                'label' => 'ddk.api::lang.permissions.manage_recipes',
                'roles' => [UserRole::CODE_DEVELOPER, UserRole::CODE_PUBLISHER],
            ],
        ];
    }
}
