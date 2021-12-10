<?php
/** 
  Plugin Name: Demo Github API
  description: Sample wordpress plugin utilizing github api.
  Version: 1.0
  Author: Rizki Perdana
  License: MIT
  Text Domain: demo-github-api
*/

defined( 'ABSPATH' ) or die( 'No direct access allowed!' );
require_once 'vendor/autoload.php';

$testing = new \Github\Client();
class MyGithub extends \Github\Client {};

function github_issues_func( $atts, $gh = null ) {
  $gh = ( $gh ) ? $gh : new MyGithub();
  $issues = $gh->api('issue')->all(get_option("gh_org"));
  get_option("gh_repo");

  if ( empty($issues) )
    return "<strong>" . __("No issues to show", 'github-api') . "</strong>";
    $return = "<ul>";
    foreach( $issues as $issue ) {
      $return .= "<li>{$issue['title']}</li>";
    }
    $return = "</ul>";
  return $return;
};

add_shortcode("github_issues", "github_issues_func");

function gh_plugin_menu_func() {
  add_submenu_page("options-general.php", // set menu parent
    "GitHub",             // page title
    "GitHub",             // menu title
    "manage_options",     // capability 
    "github",             // menu slug
    "gh_plugin_options"   // print markup
  );
}

function gh_plugin_options() {
  if ( !current_user_can( "manage_options" ) ) {
    wp_die( __( "You do not have sufficient permission to access this page." ) );
  }

  if ( isset($_GET['status']) && $_GET['status']=='success') {
    ?>
      <div id="message" class="updated notice is-dismissible">
        <p>
          <?php _e("Settings updated!", "github-api"); ?>
        </p>
        <buton type="button" class="notice-dismiss">
          <span class="screen-reader-text">
            <?php _e("Dismiss this notice.", "github-api"); ?>
          </span>
        </buton>
      </div>
    <?php
  }
  
  ?>
    <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
      <input type="hidden" name="action" value="update_github_settings" />

      <h3>
        <?php _e("GitHub Repository Info", "github-api"); ?>
      </h3>
      <p>
        <label><?php _e("GitHub Organization:", "github-api"); ?></label>
        <input type="text" name="gh_org" value="<?php echo get_option('gh_org'); ?>">
      </p>

      <p>
        <label><?php _e("GitHub repository (slug):", "github-api"); ?></label>
        <input class="" type="text" name="gh_repo" value="<?php echo get_option('gh_repo'); ?>" />
      </p>

      <input type="submit" class="button buttong-primary" value="<?php _e("Save", "github-api") ?>" />
    </form>
  <?php
}

add_action( "admin_menu", "gh_plugin_menu_func" );

function github_handle_save() {
  $org = (!empty($_POST["gh_org"])) ? $_POST["gh_org"] : NULL;
  $repo = (!empty($_POST["gh_repo"])) ? $_POST["gh_repo"] : NULL;

  update_option("gh_org", $org, TRUE);
  update_option("gh_repo", $repo, TRUE);

  $redirected_url = get_bloginfo("url") . "/wp-admin/options-general.php?page=github&status=success";
  header("Location: ".$redirected_url);
  exit;
}

add_action( 'admin_post_update_github_settings', 'github_handle_save' );
