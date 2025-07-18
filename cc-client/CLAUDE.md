# Commons Connect Client - Project Guide

## Overview
WordPress plugin that provides blocks interfacing with the Commons Connect API. This is part of the Knowledge Commons network infrastructure.

## Architecture
- **Plugin Type**: WordPress plugin
- **Namespace**: `MeshResearch\\CCClient\\`
- **Block Development**: Uses WordPress Gutenberg blocks with TypeScript
- **API Integration**: Connects to Commons Connect search service via REST API

## Development Setup

### Prerequisites
- Lando (for local development)
- Node.js 20.x (installed via Lando)
- PHP with Composer
- Running cc-search service (for search functionality)

### Initial Setup
```bash
# 1. Start the local environment
lando start

# 2. Access the site at:
# https://commons-connect-client.lndo.site/

# 3. Admin login:
# URL: https://commons-connect-client.lndo.site/wp-admin/
# Username: admin
# Password: admin
```

### Setting up Search API
```bash
# 1. Start the search service (in cc-search directory)
cd ../cc-search
lando start

# 2. Verify connection (back in cc-client)
cd ../cc-client
lando wp cc search status

# 3. Load test data
lando wp cc search provision_test_docs
```

## Build Commands

### JavaScript/Block Development
```bash
# Development build with watch mode
lando npm run start

# Production build
lando npm run build

```

### Code Quality
```bash
# Format code
lando npm run format

# Lint JavaScript
lando npm run lint:js

# Lint CSS
lando npm run lint:css

# Update npm packages
lando npm run packages-update
```

## Testing

### PHPUnit Tests
Tests require a running cc-search API (default: http://commonsconnect-search.lndo.site)

```bash
# Run all tests
lando phpunit

# Run with Xdebug
lando phpunit-debug

# Run specific test
lando phpunit --filter <test-name>
```

### Test Configuration
- Tests located in `/tests` directory
- Bootstrap file: `tests/bootstrap.php`
- Test files must be prefixed with `test-` and suffixed with `.php`
- Can override server configuration with environment variables

## WP-CLI Commands
```bash
# Run WP-CLI commands
lando wp <command>

# Run with Xdebug
lando wpd <command>

# Commons Connect specific commands
lando wp cc search status
lando wp cc search provision_test_docs
```

## Deployment

### Pushing to Packagist/Composer
Used for distribution including Knowledge Commons deployment:

```bash
# From root commons-connect directory
cd ..
./cc-client-subtree-push.sh
```

## Environment Variables
Key environment variables set in Lando:
- `CC_SEARCH_KEY`: 12345
- `CC_SEARCH_ENDPOINT`: http://commonsconnect-search.lndo.site/v1
- `CC_SEARCH_ADMIN_KEY`: 12345
- `CC_INCREMENTAL_PROVISIONING_ENABLED`: 1
- `WP_HOME`: https://commons-connect-client.lndo.site
- `WP_SITEURL`: https://commons-connect-client.lndo.site

## Project Structure
- `/src/blocks/` - Gutenberg block source files
- `/src/admin/` - Admin panel TypeScript files
- `/src/` - PHP source files (PSR-4 autoloaded)
- `/tests/` - PHPUnit test files
- `/bin/` - Shell scripts (e.g., WordPress test setup)
- `/.lando/` - Lando configuration and setup scripts

## Coding Standards
- PHP: PSR-4 autoloading standard
- JavaScript: WordPress coding standards via @wordpress/scripts
- Prettier configuration: 4 spaces, no tabs

## Dependencies
- **PHP**: Guzzle HTTP client for API requests
- **JavaScript**: WordPress packages (@wordpress/components, @wordpress/api-fetch, etc.)
- **Development**: PHPUnit, WordPress scripts, TypeScript support
