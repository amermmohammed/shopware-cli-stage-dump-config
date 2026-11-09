# Shopware GDPR Dump Config

Community registry of [shopware-cli](https://developer.shopware.com/docs/products/tools/cli/project-commands/mysql-dump.html) dump rules for **plugin-owned data** — custom plugin tables and columns plugins add to Shopware core tables.

Repository: [github.com/winkelwagen-de/shopware-cli-stage-dump-config](https://github.com/winkelwagen-de/shopware-cli-stage-dump-config)

Standard Shopware core columns are handled by shopware-cli itself via `--anonymize` and `--clean`. This package extends that for installed plugins.

## Install

```bash
composer require winkelwagen/shopware-gdpr-dump
```

Add to your `.shopware-project.yml`:

```yaml
include:
  - vendor/winkelwagen/shopware-gdpr-dump/dist/shopware-gdpr-dump.yml
```

## Create a GDPR dump

From your Shopware 6 project root:

**1. Install [shopware-cli](https://developer.shopware.com/docs/products/cli/installation.html)**

```bash
# macOS
brew install shopware/tap/shopware-cli

# Debian/Ubuntu
curl -1sLf 'https://dl.cloudsmith.io/public/friendsofshopware/stable/setup.deb.sh' | sudo -E bash
sudo apt install shopware-cli
```

**2. Download the community config**

```bash
curl -fsSL -o shopware-gdpr-dump.yml \
  https://raw.githubusercontent.com/winkelwagen-de/shopware-cli-stage-dump-config/main/dist/shopware-gdpr-dump.yml
```

**3. Add it to `.shopware-project.yml`**

Create the file (or add an `include` entry if it already exists):

```yaml
include:
  - shopware-gdpr-dump.yml
```

If you installed via Composer (see [Install](#install) above), point `include` at `vendor/winkelwagen/shopware-gdpr-dump/dist/shopware-gdpr-dump.yml` instead.

**4. Run the dump**

Always pass both flags so Shopware core data is anonymized and ephemeral tables are stripped:

```bash
shopware-cli project dump --anonymize --clean --output dump.sql.zst
```

## Contribute a plugin config

See [docs/contributing-a-plugin.md](docs/contributing-a-plugin.md).

Quick start:

```bash
git clone https://github.com/winkelwagen-de/shopware-cli-stage-dump-config.git
cd shopware-cli-stage-dump-config && composer install
bin/scan-plugin-migrations /path/to/shopware
# review plugins/, cleanup, then open a PR
bin/cleanup-plugin-config plugins/vendor/plugin.yaml
bin/validate-dump-config
```

Do **not** commit `dist/shopware-gdpr-dump.yml` — CI rebuilds it automatically after merge to `main`. PRs that change that file are rejected.

## Repository layout

| Path | Purpose |
|------|---------|
| `plugins/*.yaml` | Per-plugin authoring configs (maintained here) |
| `config/fragments/` | Reusable PII shorthand bundles |
| `dist/shopware-gdpr-dump.yml` | Generated merged config (updated by CI on `main`; shipped in the Composer package) |

## License

MIT
