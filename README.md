# Wordpress_IDN_Module
Wordpress IDN module

Wordpress is blog CMS which provides default theme for blog posts.
In order to access those blog post as REST API, one can install plugin WP REST API.http://v2.wp-api.org/

To make this REST API accessible only for IDN users(one who has valid token), you can installed this plugin on wordpress to use it as IDN module.

PreRequisite:
  WP REST API.http://v2.wp-api.org/

Steps:

Add this file under wordpress wp-content/plugins/
Login to admin panel of wordpress. 
Enable on Plugins tab.
After enabling you should see under Settings > NBSO IDN Settings
Enter NBOS url and credentials and save.

Test:
http://<wordpress_site_url>/wp-json/wp/v2/categories/?per_page=20
