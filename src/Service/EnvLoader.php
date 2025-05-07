<?php

declare(strict_types=1);

namespace Uchman\CommissionTask\Service;

/**
 * EnvLoader class for loading and accessing environment variables.
 */
class EnvLoader
{
    private array $envVariables = [];
    private static ?EnvLoader $instance = null;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct(?string $envPath = null)
    {
        $this->loadEnvFile($envPath);
    }

    /**
     * Get a singleton instance.
     */
    public static function getInstance(?string $envPath = null): self
    {
        if (null === self::$instance) {
            self::$instance = new self($envPath);
        }

        return self::$instance;
    }

    /**
     * Reset instance (mainly for testing purposes).
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Get environment variable value.
     *
     * @param string $name    Name of the environment variable
     * @param mixed  $default Default value if the variable is not set
     *
     * @return mixed Value of the environment variable or default value
     */
    public function get(string $name, $default = null)
    {
        return $this->envVariables[$name] ?? $default;
    }

    /**
     * Check if environment variable exists.
     */
    public function has(string $name): bool
    {
        return isset($this->envVariables[$name]);
    }

    /**
     * Get all environment variables.
     */
    public function all(): array
    {
        return $this->envVariables;
    }

    /**
     * Load environment variables from .env file.
     */
    private function loadEnvFile(?string $envPath = null): void
    {
        // Default path relative to project root
        $defaultPath = dirname(__DIR__, 2).'/.env';

        // Use provided path or default
        $path = $envPath ?: $defaultPath;

        if (file_exists($path)) {
            $this->envVariables = parse_ini_file($path) ?: [];
        }
    }
}
