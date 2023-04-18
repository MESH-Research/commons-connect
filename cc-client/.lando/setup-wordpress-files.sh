#!/bin/sh

echo "Downloading WordPress on $LANDO_SERVICE_NAME ..."
cd /commons-connect-client/.lando
rm -rf wordpress
curl -O https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
rm latest.tar.gz
echo "Done downloading WordPress on $LANDO_SERVICE_NAME ..."

echo "Setting up WordPress on $LANDO_SERVICE_NAME ..."
rm -rf /wordpress
cp -r /commons-connect-client/.lando/wordpress /
chown -R www-data:dialout /wordpress

rm -rf /wordpress/wp-content/plugins/commons-connect-client
rm -rf /wordpress/wp-config.php
ln -s /commons-connect-client/.lando/wp-config.php /wordpress/wp-config.php
ln -s /commons-connect-client /wordpress/wp-content/plugins/commons-connect-client
echo "Done setting up WordPress on $LANDO_SERVICE_NAME ..."

cd /commons-connect-client/