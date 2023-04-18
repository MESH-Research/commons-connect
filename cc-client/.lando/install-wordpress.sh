#!/bin/sh

if ! wp core is-installed; then
	echo "Installing WordPress on $LANDO_SERVICE_NAME..."
	wp core install \
		--url=$LANDO_APP_NAME.$LANDO_DOMAIN \
		--title="Commons Connect Client Test Site" \
		--admin_user=admin \
		--admin_password=admin \
		--admin_email=admin@$LANDO_APP_NAME.$LANDO_DOMAIN
	echo "Done installing WordPress on $LANDO_SERVICE_NAME..."
else
	echo "WordPress is already installed on $LANDO_SERVICE_NAME..."
fi

wp plugin activate commons-connect-client
wp plugin delete akismet --quiet
wp plugin delete hello --quiet