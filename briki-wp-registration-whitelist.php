<?php
/*
Plugin Name: Briki WP Registration Whitelist
Plugin URI: http://zindilis.com/
Description: Only allow registrations from users with emails in whitelisted domains.
Version: 0.1
Author: Marios Zindilis
Author URI: http://zindilis.com/
License: GPL2
*/

/*  Copyright 2011  Marios Zindilis <marios@zindilis.com>

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function briki_wp_registration_whitelist_install() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'briki_wp_registration_whitelist';
  if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
    $sql = 'CREATE TABLE '.$table_name.' (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      domain VARCHAR(255),
      UNIQUE KEY id (id)
	  );';

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
  }
} // function briki_wp_registration_whitelist_install()

function briki_wp_registration_whitelist_uninstall() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'briki_wp_registration_whitelist';
  $sql = 'DROP TABLE '. $table_name;
  $wpdb->query($sql);
} // function briki_wp_registration_whitelist_uninstall()

function briki_wp_registration_whitelist_admin_menu() {
  add_options_page('Briki WP Registration Whitelist', 'Registration Whitelist', 'manage_options', 'briki_wp_registration_whitelist_admin_menu', 'briki_wp_registration_whitelist_options');
} // function briki_wp_registration_whitelist_admin_menu()

function briki_wp_registration_whitelist_options() {
  if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
  global $wpdb;
  $table_name = $wpdb->prefix . 'briki_wp_registration_whitelist';

  if(isset($_POST['submit_whitelisted_domains'])) {
    if($whitelisted_domains = $_POST['whitelisted_domains'] ) {
      $wpdb->query('TRUNCATE '.$table_name.';');

      $whitelisted_domains = explode( "\n", $whitelisted_domains );
			foreach( $whitelisted_domains as $whitelisted_domain ) {
        $whitelisted_domain = trim($whitelisted_domain);
        $sql = 'INSERT INTO '.$table_name.' (id, domain) VALUES (NULL, "'.mysql_real_escape_string($whitelisted_domain).'");';
        $wpdb->query($sql);
			}
		}
?><div class="updated"><p><strong>Completed Adding Domains</strong></p></div>
<?php
  } // if(isset($_POST['submit_whitelisted_domains']))
?>
<div class="wrap">
  <h2>Briki WP Registration Whitelist</h2>
  <form method="post" action="">
    <fieldset>
      <legend>Add or remove domains you want to whitelist and allow registration for. One domain per line:</legend>
      <textarea name="whitelisted_domains" cols="40" rows="10" style="display: block;"><?php
        $sql = 'SELECT domain from '.$table_name;
        $whitelisted_domains = $wpdb->get_results($sql);
	      foreach($whitelisted_domains as $whitelisted_domain) {
          echo $whitelisted_domain->domain."\n";
      	}
      ?></textarea>
      <input class="button" type="submit" name="submit_whitelisted_domains" value="Submit Whitelisted Domains" />
    </fieldset>
  </form>
</div>
<?php
} // function briki_wp_registration_whitelist_options()

function check_whitelist() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'briki_wp_registration_whitelist';
  $sql = "SELECT domain FROM $table_name";
  $whitelisted_domains = $wpdb->get_results($sql);

  $is_whitelisted = false;
  foreach($whitelisted_domains as $whitelisted_domain ) {
    $whitelisted_domain = preg_quote($whitelisted_domain->domain );
    if( preg_match( "/$whitelisted_domain$/", $_POST['user_email'] )) {
      $is_whitelisted = true;
    }
  }

  if(!$is_whitelisted) {?>
<!DOCTYPE html><html><head><title>WordPress &raquo; Registration prohibited</title>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_settings('blog_charset'); ?>" />
<link rel="stylesheet" href="wp-admin/wp-admin.css" type="text/css"></head>
<body>

<div id="login">
	<h2>Prohibited Email</h2>
	<p>The email you used has been restricted from registering.<br />
	<a href="<?php echo get_settings('home'); ?>/" title="Go back to the blog">Home</a>	</p>
</div>

</body>
</html>
<?    die();
  }
} // function check_whitelist()

register_activation_hook(__FILE__,'briki_wp_registration_whitelist_install');
register_deactivation_hook(__FILE__,'briki_wp_registration_whitelist_uninstall');
add_action('admin_menu', 'briki_wp_registration_whitelist_admin_menu');
add_action('register_post', 'check_whitelist');
?>
