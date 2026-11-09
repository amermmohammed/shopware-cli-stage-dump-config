<?php

declare(strict_types=1);

namespace ShopwareGdprDump\Build;

final class ShorthandExpander
{
    /** @var array<string, string> */
    private const COLUMN_SHORTHANDS = [
        'email' => 'faker.Internet.SafeEmail()',
        'first_name' => 'faker.Person.FirstName()',
        'last_name' => 'faker.Person.LastName()',
        'phone_number' => 'faker.Phone.Number()',
        'street' => 'faker.Address.StreetAddress()',
        'address' => 'faker.Address.StreetAddress()',
        'zipcode' => 'faker.Address.PostCode()',
        'city' => 'faker.Address.City()',
        'company' => 'faker.Person.Name()',
        'title' => 'faker.Person.Name()',
        'remote_address' => 'faker.Internet.Ipv4()',
    ];

    /**
     * @param array<string, mixed> $pluginConfig
     *
     * @return array{rewrite: array<string, array<string, string>>, nodata: list<string>}
     */
    public function expandPluginDump(array $pluginConfig): array
    {
        $dump = $pluginConfig['dump'] ?? [];
        $tables = $dump['tables'] ?? [];

        $rewrite = [];
        $nodata = [];

        foreach ($tables as $tableName => $tableConfig) {
            if (!\is_array($tableConfig)) {
                continue;
            }

            $tableName = (string) $tableName;

            if (($tableConfig['nodata'] ?? false) === true || ($tableConfig['ignore'] ?? false) === true) {
                $nodata[] = $tableName;
            }

            $columnRewrites = $tableConfig['rewrite'] ?? [];
            if (!\is_array($columnRewrites)) {
                continue;
            }

            foreach ($columnRewrites as $column => $value) {
                $expanded = $this->expandValue((string) $value);
                if ($expanded === null) {
                    continue;
                }
                $rewrite[$tableName][(string) $column] = $expanded;
            }
        }

        return [
            'rewrite' => $rewrite,
            'nodata' => $nodata,
        ];
    }

    public function expandValue(string $value): ?string
    {
        $trimmed = trim($value);

        if (\in_array($trimmed, ['skip', 'review'], true)) {
            return null;
        }

        if (isset(self::COLUMN_SHORTHANDS[$trimmed])) {
            return self::COLUMN_SHORTHANDS[$trimmed];
        }

        if (str_starts_with($trimmed, 'faker.') || (str_starts_with($trimmed, "'") && str_ends_with($trimmed, "'"))) {
            return $trimmed;
        }

        return $trimmed;
    }
}
