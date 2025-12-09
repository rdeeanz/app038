<?php

namespace App\Modules\ERPIntegration\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Yaml\Yaml;

class YamlMappingService
{
    /**
     * Transform data using YAML mapping configuration
     *
     * @param array $sourceData Source data to transform
     * @param string $mappingFile Path to YAML mapping file
     * @return array Transformed data
     */
    public function transform(array $sourceData, string $mappingFile): array
    {
        try {
            $mapping = $this->loadMapping($mappingFile);

            return $this->applyMapping($sourceData, $mapping);
        } catch (\Exception $e) {
            Log::error('YAML mapping transformation failed', [
                'mapping_file' => $mappingFile,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Load YAML mapping file
     *
     * @param string $mappingFile Path to mapping file
     * @return array Mapping configuration
     */
    protected function loadMapping(string $mappingFile): array
    {
        $fullPath = base_path("config/mappings/{$mappingFile}");

        if (!File::exists($fullPath)) {
            throw new \Exception("Mapping file not found: {$mappingFile}");
        }

        $content = File::get($fullPath);
        $mapping = Yaml::parse($content);

        if (!is_array($mapping)) {
            throw new \Exception("Invalid mapping file format: {$mappingFile}");
        }

        return $mapping;
    }

    /**
     * Apply mapping to source data
     *
     * @param array $sourceData Source data
     * @param array $mapping Mapping configuration
     * @return array Transformed data
     */
    protected function applyMapping(array $sourceData, array $mapping): array
    {
        $result = [];

        foreach ($mapping['fields'] ?? [] as $targetField => $mappingConfig) {
            // Handle array/collection mapping
            if (isset($mappingConfig['transform']['type']) && $mappingConfig['transform']['type'] === 'array') {
                $value = $this->mapArrayField($sourceData, $mappingConfig);
            } else {
                $value = $this->mapField($sourceData, $mappingConfig);
            }

            if ($value !== null || ($mappingConfig['required'] ?? false)) {
                $result[$targetField] = $value;
            }
        }

        // Apply transformations if defined
        if (isset($mapping['transformations'])) {
            $result = $this->applyTransformations($result, $mapping['transformations']);
        }

        return $result;
    }

    /**
     * Map a single field
     *
     * @param array $sourceData Source data
     * @param array|string $mappingConfig Mapping configuration
     * @return mixed Mapped value
     */
    protected function mapField(array $sourceData, array|string $mappingConfig)
    {
        // Simple string mapping (source field name)
        if (is_string($mappingConfig)) {
            return data_get($sourceData, $mappingConfig);
        }

        // Array mapping configuration
        $source = $mappingConfig['source'] ?? null;
        $default = $mappingConfig['default'] ?? null;
        $transform = $mappingConfig['transform'] ?? null;

        if ($source === null) {
            return $default;
        }

        $value = data_get($sourceData, $source, $default);

        // Apply transformation if defined
        if ($transform && $value !== null) {
            $value = $this->applyTransform($value, $transform);
        }

        return $value;
    }

    /**
     * Apply transformation to a value
     *
     * @param mixed $value Value to transform
     * @param string|array $transform Transformation definition
     * @return mixed Transformed value
     */
    protected function applyTransform(mixed $value, string|array $transform): mixed
    {
        if (is_string($transform)) {
            return $this->applySimpleTransform($value, $transform);
        }

        if (is_array($transform)) {
            $type = $transform['type'] ?? null;
            $params = $transform['params'] ?? [];

            return match ($type) {
                'date' => $this->transformDate($value, $params),
                'number' => $this->transformNumber($value, $params),
                'string' => $this->transformString($value, $params),
                'lookup' => $this->transformLookup($value, $params),
                'concat' => $this->transformConcat($value, $params),
                default => $value,
            };
        }

        return $value;
    }

    /**
     * Apply simple transformation
     */
    protected function applySimpleTransform(mixed $value, string $transform): mixed
    {
        return match ($transform) {
            'uppercase' => strtoupper((string) $value),
            'lowercase' => strtolower((string) $value),
            'trim' => trim((string) $value),
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            default => $value,
        };
    }

    /**
     * Transform date
     */
    protected function transformDate(mixed $value, array $params): string
    {
        $fromFormat = $params['from'] ?? 'Y-m-d';
        $toFormat = $params['to'] ?? 'Ymd';

        try {
            $date = \DateTime::createFromFormat($fromFormat, (string) $value);
            return $date ? $date->format($toFormat) : (string) $value;
        } catch (\Exception $e) {
            return (string) $value;
        }
    }

    /**
     * Transform number
     */
    protected function transformNumber(mixed $value, array $params): float|int
    {
        $decimals = $params['decimals'] ?? 2;
        $multiplier = $params['multiplier'] ?? 1;

        $number = (float) $value * $multiplier;

        return $decimals === 0 ? (int) $number : round($number, $decimals);
    }

    /**
     * Transform string
     */
    protected function transformString(mixed $value, array $params): string
    {
        $value = (string) $value;

        if (isset($params['max_length'])) {
            $value = substr($value, 0, $params['max_length']);
        }

        if (isset($params['pad'])) {
            $length = $params['pad']['length'] ?? 0;
            $padString = $params['pad']['string'] ?? ' ';
            $padType = $params['pad']['type'] ?? STR_PAD_RIGHT;
            $value = str_pad($value, $length, $padString, $padType);
        }

        return $value;
    }

    /**
     * Transform lookup (value mapping)
     */
    protected function transformLookup(mixed $value, array $params): mixed
    {
        $mapping = $params['mapping'] ?? [];
        return $mapping[$value] ?? $params['default'] ?? $value;
    }

    /**
     * Transform concat (concatenate multiple fields)
     */
    protected function transformConcat(mixed $value, array $params): string
    {
        $fields = $params['fields'] ?? [];
        $separator = $params['separator'] ?? '';

        $values = array_map(fn($field) => data_get($value, $field, ''), $fields);

        return implode($separator, $values);
    }

    /**
     * Map array field (for collections/items)
     *
     * @param array $sourceData Source data
     * @param array $mappingConfig Mapping configuration
     * @return array Mapped array
     */
    protected function mapArrayField(array $sourceData, array $mappingConfig): array
    {
        $source = $mappingConfig['source'] ?? null;
        $itemMapping = $mappingConfig['transform']['params']['item_mapping'] ?? [];

        if ($source === null) {
            return [];
        }

        $sourceArray = data_get($sourceData, $source, []);

        if (!is_array($sourceArray)) {
            return [];
        }

        $result = [];

        foreach ($sourceArray as $index => $item) {
            $mappedItem = [];

            foreach ($itemMapping as $targetKey => $itemConfig) {
                // Replace {{index}} placeholder
                $itemConfigString = is_string($itemConfig) ? $itemConfig : json_encode($itemConfig);
                $itemConfigString = str_replace('{{index}}', (string) ($index + 1), $itemConfigString);
                $itemConfig = is_string($itemConfig) ? $itemConfigString : json_decode($itemConfigString, true);

                if (is_string($itemConfig)) {
                    $mappedItem[$targetKey] = data_get($item, $itemConfig);
                } elseif (is_array($itemConfig)) {
                    $sourceField = $itemConfig['source'] ?? null;
                    $default = $itemConfig['default'] ?? null;
                    $transform = $itemConfig['transform'] ?? null;

                    $value = $sourceField ? data_get($item, $sourceField, $default) : $default;

                    if ($transform && $value !== null) {
                        $value = $this->applyTransform($value, $transform);
                    }

                    $mappedItem[$targetKey] = $value;
                }
            }

            $result[] = $mappedItem;
        }

        return $result;
    }

    /**
     * Apply global transformations
     */
    protected function applyTransformations(array $data, array $transformations): array
    {
        foreach ($transformations as $transformation) {
            $type = $transformation['type'] ?? null;
            $target = $transformation['target'] ?? null;

            if ($type === 'nested' && $target) {
                $nestedData = [];
                foreach ($transformation['fields'] ?? [] as $field) {
                    if (isset($data[$field])) {
                        $nestedData[$field] = $data[$field];
                        unset($data[$field]);
                    }
                }
                $data[$target] = $nestedData;
            }
        }

        return $data;
    }
}

