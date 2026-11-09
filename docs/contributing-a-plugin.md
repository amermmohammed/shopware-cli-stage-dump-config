# Contributing a plugin config

This guide explains how to add GDPR dump rules for your plugin **without copying** rules that already exist elsewhere.

## What is already covered

| Layer | Handles |
|-------|---------|
| `shopware-cli project dump --anonymize` | Standard Shopware PII columns (`customer`, `order_customer`, `order_address`, …) |
| `shopware-cli project dump --clean` | Ephemeral Shopware tables (`cart`, `log_entry`, `messenger_messages`, …) |
| **This repo** | Plugin-owned tables + columns your plugin adds to core tables |

Do **not** re-declare standard Shopware columns. If your plugin runs `ALTER TABLE customer ADD COLUMN …`, only add rewrite rules for **your new columns**.

## Step 1 — Generate a draft

Clone this repository and point the scanner at your Shopware installation:

```bash
git clone https://github.com/winkelwagen-de/shopware-cli-stage-dump-config.git
cd shopware-cli-stage-dump-config && composer install
bin/scan-plugin-migrations /path/to/shopware
```

The scanner walks `custom/plugins/` and `custom/static-plugins/`, reads each plugin's `composer.json`, and writes drafts to `plugins/{vendor}/{package}.yaml` in this repo. Existing files are skipped — use `--force` to regenerate.

Metadata is detected automatically:

| Field | Source |
|-------|--------|
| `plugin.name` | `composer.json` → `"name"` |
| `plugin.label` | `composer.json` → `"description"` |
| output path | `plugins/{vendor}/{package}.yaml` |

The scanner lists **every table and every column** from migrations. Heuristics pre-fill suggestions (`admin_mail` → `email`, etc.); everything else is marked `review` or commented as `skip` (structural columns). Tables matching nodata heuristics are flagged for removal.

```bash
bin/scan-plugin-migrations /path/to/shopware
# review drafts — static-plugins land in plugins/_vendor/ (gitignored)
bin/cleanup-plugin-config plugins/vendor/plugin.yaml
bin/build-dump-config
bin/validate-dump-config
```

## Step 2 — Use shorthand (stay DRY)

Authoring configs use shorthand that `bin/build-dump-config` expands to shopware-cli/faker expressions:

| Shorthand | Expands to |
|-----------|------------|
| `email` | `faker.Internet.SafeEmail()` |
| `first_name` | `faker.Person.FirstName()` |
| `last_name` | `faker.Person.LastName()` |
| `phone_number` | `faker.Phone.Number()` |
| `street` | `faker.Address.StreetAddress()` |
| `zipcode` | `faker.Address.PostCode()` |
| `city` | `faker.Address.City()` |
| `nodata: true` | export schema only, omit row data |

Reuse shorthand bundles from [`config/fragments/pii-rewrites.yaml`](../config/fragments/pii-rewrites.yaml) — copy the column keys you need:

```yaml
dump:
  tables:
    your_plugin_subscriber:
      rewrite:
        first_name: first_name
        last_name: last_name
        email: email
```

## Step 3 — Example config

```yaml
plugin:
  name: acme/foo-plugin
  label: Acme Foo Plugin
  maintainer: "@acme-team"

dump:
  tables:
    acme_foo_subscriber:
      rewrite:
        email: email
        first_name: first_name
        last_name: last_name

    # Plugin added columns to a core table
    customer:
      rewrite:
        acme_foo_loyalty_notes: "'redacted'"

    acme_foo_import_log:
      nodata: true

    acme_foo_search_index:
      nodata: true
```

## `nodata`

Use `nodata: true` for logs, queues, caches, staging tables, and other plugin tables where the schema may be useful but row data should not appear in dumps.

See [`config/fragments/plugin-patterns.yaml`](../config/fragments/plugin-patterns.yaml) for common naming patterns.

## Step 4 — Validate and open a PR

```bash
bin/validate-dump-config
```

Do **not** commit `dist/shopware-gdpr-dump.yml`. CI rebuilds it on merge to `main`. PRs that modify that file are blocked.

CI validates YAML syntax, schema, unique plugin names, merge conflicts, verifies the merged config builds, rejects manual dist changes, and rejects scanner draft headers or `# TODO` comments in plugin configs.

## File naming

One file per plugin, named after the Composer package:

```
plugins/acme/foo-plugin.yaml   # for composer name acme/foo-plugin
plugins/swag/newsletter.yaml   # for composer name swag/newsletter
```

A file with only `plugin:` metadata and no `dump` section means the plugin was scanned and needs no GDPR dump rules.
