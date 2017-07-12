<?php
/**
 * The Admin 10X Plugin.
 *
 * Improves WP Admin performance when WP has lots of registered users.
 *
 * @package admin10x
 * @since 1.0.0
 */

/**
 * Plugin Name: Admin10X
 * Plugin URI:  https://github.com/bliles/wp-admin10x
 * Description: Admin10X improves the performance of some admin features when your site has many registered users.
 * Author:      Brandon Liles
 * Version:     1.0.2
 * License:     MIT (license.txt)
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class Admin10X {
  private $wpdb;
  private $authors_table;

  function __construct() {
    global $wpdb;
    $this->wpdb = $wpdb;
    $this->authors_table = $wpdb->prefix . "author_users";
  }

  public function get_authors_table_name() {
    return $this->authors_table;
  }

  public function plugin_install () {
    $charset_collate = $this->wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$this->authors_table} (
        user_id bigint(20) unsigned NOT NULL,
        PRIMARY KEY (user_id)
    ) {$charset_collate}";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $author_users_opt = array(
      'who' => 'authors',
      'name' => 'admin10x_install',
      'class'=> 'authors',
      'show' => 'display_name_with_login'
    );

    $authors = get_users( $author_users_opt );

    foreach($authors as $author) {
      error_log(json_encode($author->data));
      $this->add_author($author->data->ID);
    }
  }

  public function plugin_uninstall() {
    // the author users cache table data can easily be rebuilt so it's better to remove it on deactivate
    $sql = "DROP TABLE IF EXISTS {$this->authors_table}";
    $this->wpdb->query($sql);
  }

  public function add_author($user_id) {
    $this->wpdb->query($this->wpdb->prepare(
      "INSERT {$this->authors_table}
                 (user_id)
          SELECT u.ID
            FROM {$this->wpdb->users} u
            LEFT JOIN {$this->authors_table} a
              ON a.user_id = u.ID
           WHERE u.ID = %d
             AND a.user_id IS NULL",
      array($user_id)
    ));
  }

  public function del_author($user_id) {
    $this->wpdb->query($this->wpdb->prepare(
      "DELETE FROM {$this->authors_table}
           WHERE user_id = %d",
      array($user_id)
    ));
  }

}

add_action('pre_user_query', function($q) {
  global $wpdb;

  if ( !isset( $q->query_where ) ) {
    return;
  }

  if ( isset($q->query_vars['name']) && $q->query_vars['name'] == 'admin10x_install' ) {
    return;
  }

  $where_match = '/WHERE 1=1 AND \(\s*\( ' . $wpdb->usermeta . '.meta_key = \'' . $wpdb->prefix . 'user_level\' AND ' . $wpdb->usermeta . '.meta_value != \'0\' \)\s*\)/';
  if ( !preg_match($where_match, $q->query_where) ) {
    return;
  }

  $a10x = new Admin10X();
  $authors_table = $a10x->get_authors_table_name();

  $q->query_from  = "FROM {$authors_table} a
                    INNER JOIN {$wpdb->users}
                       ON ( {$wpdb->users}.ID = a.user_id )
                    INNER JOIN {$wpdb->usermeta}
                       ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id )";
});

add_action('set_user_role', function($user_id, $role)  {
  $a10x = new Admin10X();
  $author_roles = array('administrator', 'author', 'editor');

  if ( in_array($role, $author_roles)) {
    $a10x->add_author($user_id);
  } else {
    $a10x->del_author($user_id);
  }
}, 10, 3);

add_action('deleted_user', function($id, $reassign)  {
  $a10x = new Admin10X();
  $a10x->del_author($id);
}, 10, 2);

function admin10x_plugin_install() {
  $a10x = new Admin10X();
  $a10x->plugin_install();
}

function admin10x_plugin_uninstall() {
  $a10x = new Admin10X();
  $a10x->plugin_uninstall();
}

register_activation_hook(__FILE__, 'admin10x_plugin_install');
register_deactivation_hook(__FILE__, 'admin10x_plugin_uninstall');
