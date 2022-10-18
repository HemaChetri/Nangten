<?php

/**
 * @see       https://github.com/laminas/laminas-development-mode for the canonical source repository
 * @copyright https://github.com/laminas/laminas-development-mode/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-development-mode/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\DevelopmentMode;

use RuntimeException;

/**
 * Shared functionality for the Disable/Enable commands.
 */
trait ConfigDiscoveryTrait
{
    /**
     * @var null|array
     */
    private $applicationConfig;

    /**
     * @var string
     */
    private $applicationConfigPath = 'config/application.config.php';

    /**
     * @var string
     */
    private $mezzioConfigPath = 'config/config.php';

    /**
     * @var string Base name for configuration cache.
     */
    private $configCacheBase = 'module-config-cache';

    /**
     * Removes the application configuration cache file, if present.
     */
    private function removeConfigCacheFile()
    {
        $configCacheFile = $this->getConfigCacheFile();

        if (! $configCacheFile || ! file_exists($configCacheFile)) {
            return;
        }

        unlink($configCacheFile);
    }

    /**
     * Retrieve the config cache file, if any.
     *
     * @return false|string
     */
    private function getConfigCacheFile()
    {
        $config = $this->getApplicationConfig();

        if (isset($config['config_cache_path'])) {
            return $config['config_cache_path'];
        }

        $configCacheDir = $this->getConfigCacheDir();

        if (! $configCacheDir) {
            return false;
        }

        $path = sprintf('%s/%s.', $configCacheDir, $this->configCacheBase);

        $configCacheKey = $this->getConfigCacheKey();
        if ($configCacheKey) {
            $path .= $configCacheKey . '.';
        }

        return $path . 'php';
    }

    /**
     * Return the configured configuration cache directory, if any.
     *
     * @return null|string
     */
    private function getConfigCacheDir()
    {
        $config = $this->getApplicationConfig();
        if (empty($config['module_listener_options']['cache_dir'])) {
            return null;
        }

        return $config['module_listener_options']['cache_dir'];
    }

    /**
     * Return the configured configuration cache key, if any.
     *
     * @return null|string
     */
    private function getConfigCacheKey()
    {
        $config = $this->getApplicationConfig();
        if (empty($config['module_listener_options']['config_cache_key'])) {
            return null;
        }

        return $config['module_listener_options']['config_cache_key'];
    }

    /**
     * Return the application configuration.
     *
     * Raises an exception if retrieved configuration is not an array.
     *
     * @return array
     * @throws RuntimeException if config/application.config.php does not
     *     return an array
     */
    private function getApplicationConfig()
    {
        if (null !== $this->applicationConfig) {
            return $this->applicationConfig;
        }

        $configFile = isset($this->projectDir)
            ? sprintf('%s/%s', $this->projectDir, $this->applicationConfigPath)
            : $this->applicationConfigPath;

        $configFile = file_exists($configFile) ? $configFile : $this->mezzioConfigPath;

        if (! file_exists($configFile)) {
            $this->applicationConfig = [];
            return $this->applicationConfig;
        }

        $this->applicationConfig = include $configFile;

        if (! is_array($this->applicationConfig)) {
            throw new RuntimeException(
                'Invalid configuration returned from config/application.config.php or config/config.php;' . PHP_EOL
                . 'is this a Laminas or Laminas Mezzio application?' . PHP_EOL
            );
        }

        return $this->applicationConfig;
    }
}
