# Commons Connect Client Plugin

This plugin provides WordPress blocks that interface with the Commons Connect API.

## Getting Started

1. Install [Lando](https://lando.dev/).
2. Clone the CommonsConnect repository.
3. In the `cc-client` directory, run `lando start`.
4. Open the test site at https://commons-connect-client.lndo.site/.
5. You can login to the WordPress admin at https://commons-connect-client.lndo.site/wp-admin/ with the username `admin` and password `admin`.

### Interacting with the Search API

The Search block requires a running CommonsConnect search service:

1. Change to the `cc-search` directory.
2. Run `lando start`.
3. In the `cc-client` directory, run `lando wp cc search status` to verify that the plugin can connect to the search service.
4. In the `cc-client` directory, run `lando wp cc search provision_test_docs` to load test data into the search service.