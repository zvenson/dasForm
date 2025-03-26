# genericSWJSPlugin
Shopware 6
You can add your JS Code to this file: 
/src/Resources/app/storefront/src/design-burgs-plugin/design-burgs-plugin.js

Install and activate the plugin by uploading content of master.zip to the folder 
/custom/plugins/DesignBurgsJavaScript and do

php bin/console plugin:refresh
php bin/console plugin:install --activate DesignBurgsJavaScript

Afterwards you need to 
/bin/build-storefront.sh
php bin/console cache:clear
php bin/console theme:compile

make sure you have node.js, npm installed on your server.

