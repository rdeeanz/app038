<?php

namespace App\Modules;

use Illuminate\Support\ServiceProvider;

abstract class BaseModule
{
    /**
     * Module name
     */
    protected string $name;

    /**
     * Module namespace
     */
    protected string $namespace;

    /**
     * Get module name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get module namespace
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Get module path
     */
    public function getPath(): string
    {
        return app_path('Modules/' . $this->name);
    }

    /**
     * Register module routes
     */
    abstract public function registerRoutes(): void;

    /**
     * Register module services
     */
    abstract public function registerServices(): void;
}

