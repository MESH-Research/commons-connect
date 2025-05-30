# Commons Connect Client Plugin

This plugin provides WordPress blocks that interface with the Commons Connect API.

## Getting Started

1. Install [Lando](https://lando.dev/).
2. Clone the CommonsConnect repository.
3. In the `cc-client` directory, run `lando start`.
4. Open the test site at <https://commons-connect-client.lndo.site/>.
5. You can login to the WordPress admin at <https://commons-connect-client.lndo.site/wp-admin/> with the username `admin` and password `admin`.

## Interacting with the Search API

The Search block requires a running CommonsConnect search service:

1. Change to the `cc-search` directory.
2. Run `lando start`.
3. In the `cc-client` directory, run `lando wp cc search status` to verify that the plugin can connect to the search service.
4. In the `cc-client` directory, run `lando wp cc search provision_test_docs` to load test data into the search service.

## Running Tests

Tests use the WordPress test functionality and PHPUnit. They require a running cc-search API. By default this is the local API running on `http://commonsconnect-search.lndo.site`.

Tests are located in the `tests` directory.

1. Change to the `cc-search` directory.
2. Run `lando start`.
3. In the `cc-client` directory, run `lando start`.
4. Run `lando phpunit` or `lando phpunit-debug`. To run a specific test run `lando phpunit --filter <test-name>`.

You can test against a different server configuration by overriding environment variables, as in `dev-search-lando-override.lando.yml`.

## Pushing to Composer

[Packagist](https://packagist.org/) is used to distribute the plugin, including for use on Knowledge Commons.

To push changes to Compose:

1. Change to the root commons-connect directory.
2. Run `./cc-client-subtree-push.sh`
