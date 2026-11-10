<?php

declare(strict_types=1);

namespace ShopwareGdprDump\Build;

use Symfony\Component\Yaml\Yaml;

final class DumpConfigBuilder
{
    public function __construct(
        private readonly string $projectRoot,
        private readonly PluginConfigMerger $merger = new PluginConfigMerger(),
    ) {
    }

    public function build(): string
    {
        $pluginConfigs = $this->loadPluginConfigs();
        $merged = $this->merger->merge($pluginConfigs);

        $output = ['dump' => array_filter([
            'rewrite' => $merged['rewrite'] !== [] ? $merged['rewrite'] : null,
            'nodata' => $merged['nodata'] !== [] ? $merged['nodata'] : null,
            'where' => $this->buildWhere() ?: null,
        ])];

        return Yaml::dump($output, 6, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
    }

    /**
     * @return array<string, string>
     */
    private function buildWhere(): array
    {
        $file = $this->projectRoot . '/config/fragments/system-config-exclusions.yaml';
        if (!is_file($file)) {
            return [];
        }

        $parsed = Yaml::parseFile($file);
        $keys = $this->quoteList($parsed['system_config_excluded_keys'] ?? [], 'system_config_excluded_keys');
        $patterns = $this->quoteList($parsed['system_config_excluded_key_patterns'] ?? [], 'system_config_excluded_key_patterns');

        $conditions = [];
        if ($keys !== []) {
            $conditions[] = sprintf('configuration_key NOT IN (%s)', implode(', ', $keys));
        }
        foreach ($patterns as $pattern) {
            $conditions[] = sprintf('configuration_key NOT LIKE %s', $pattern);
        }

        return $conditions === [] ? [] : ['system_config' => implode(' AND ', $conditions)];
    }

    /**
     * @return list<string>
     */
    private function quoteList(mixed $values, string $name): array
    {
        if (!\is_array($values)) {
            throw new \RuntimeException(sprintf('%s must be a list', $name));
        }

        $quoted = [];
        foreach ($values as $value) {
            if (!\is_string($value) || $value === '') {
                throw new \RuntimeException(sprintf('%s must contain non-empty strings', $name));
            }
            $quoted[] = "'" . str_replace("'", "''", $value) . "'";
        }
        sort($quoted);

        return $quoted;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadPluginConfigs(): array
    {
        $pluginsDir = $this->projectRoot . '/plugins';
        if (!is_dir($pluginsDir)) {
            return [];
        }

        $configs = [];
        $files = $this->findPluginConfigFiles($pluginsDir);

        foreach ($files as $file) {
            $parsed = Yaml::parseFile($file);
            if (!\is_array($parsed)) {
                throw new \RuntimeException(sprintf('Invalid plugin config: %s', $file));
            }

            $name = $parsed['plugin']['name'] ?? null;
            if (!\is_string($name) || $name === '') {
                throw new \RuntimeException(sprintf('Missing plugin.name in %s', $file));
            }

            if (isset($configs[$name])) {
                throw new \RuntimeException(sprintf('Duplicate plugin.name "%s" in %s', $name, $file));
            }

            $configs[$name] = $parsed;
        }

        return $configs;
    }

    /**
     * @return list<string>
     */
    private function findPluginConfigFiles(string $pluginsDir): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            if (preg_match('/\.ya?ml$/', $path) === 1) {
                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }
}
