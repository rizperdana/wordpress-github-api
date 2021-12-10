<?php
/** 
  Plugin Name: Demo GitHub API
  description: Sample wordpress plugin utilizing github api.
  Version: 1.0
  Author: Rizki Perdana
  License: MIT
  Text Domain: demo-github-api
*/
defined( 'ABSPATH' ) or die( 'No direct access allowed!' );

require_once 'vendor/autoload.php';

class MyGithub extends \Github\Client {};

function github_issues_func(  $atts, $gh=null ) {
    $gh = ( $gh ) ? $gh : new MyGithub();

    $issues = $gh->api("issue")->all(get_option("gh_org"), get_option("gh_repo"));

    if ( empty($issues) )
        return "<strong>" . __("No issues to show", 'github-api') . "</strong>";
    $return = "<ul>";
    foreach( $issues as $issue ) {
        $return .= "<li>{$issue['title']}</li>";
    }
    $return .= "</ul>";
    
    return $return;
}
add_shortcode( 'github_issues', 'github_issues_func' );

add_action( "admin_menu", "gh_plugin_menu_func" );
function gh_plugin_menu_func() {
    add_submenu_page(   
      "options-general.php",
      "Github",             
      "Github",             
      "manage_options",     
      "github",             
      "gh_plugin_options"   
    );
}

function gh_plugin_options() {
  if ( !current_user_can( "manage_options" ) )  {
      wp_die( __( "You do not have sufficient permissions to access this page." ) );
  }

  if ( isset($_GET['status']) && $_GET['status']=='success') { 
  ?>
      <div id="message" class="updated notice is-dismissible">
          <p><?php _e("Settings updated!", "github-api"); ?></p>
          <button type="button" class="notice-dismiss">
              <span class="screen-reader-text"><?php _e("Dismiss this notice.", "github-api"); ?></span>
          </button>
      </div>
  <?php
  }

  ?>
    <form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">
    <input type="hidden" name="action" value="update_github_settings" />
    <div class="notice notice-primary">
      <p>
        Example:
        <br>
        <b>repo</b>: symfony 
        <b>org</b>: symfony
        <br>
        this will get entire current issue of Symfony frameworks. 
      </p>
    </div>
    <h3><?php _e("Github Repository Info", "github-api"); ?></h3>
    <table class="form-table" role="presentation">
      <tr>
        <th scope="row"><label for="gh_org"><?php _e("Github Organization:", "github-api"); ?></label></th>
        <td><input type="text" name="gh_org" value="<?php echo get_option('gh_org'); ?>" /></td>
      </tr>
      <tr>
        <th scope="row"><label for="gh_repo"><?php _e("Github repository (slug):", "github-api"); ?></label></th>
        <td><input type="text" name="gh_repo" value="<?php echo get_option('gh_repo'); ?>" /></td>
      </tr>
    </table>
    <input class="button button-primary" type="submit" value="<?php _e("Save", "github-api"); ?>" />
    </form>

    <form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">
      <input type="hidden" name="action" value="oauth_submit" />
      <h3>Oauth 2.0 (in case your repo is private)</h3>
      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="client_id"><?php _e("Github Application Client ID:", "github-api"); ?></label>
            <td><input type="text" name="client_id" value="<?php echo get_option('client_id')?>"></td>
          </th>
        </tr>
        <tr>
          <th scope="row">
            <label for="client_secret"><?php _e("Github Application Client Secret:", "github-api"); ?></label>
            <td><input type="text" name="client_secret" value="<?php echo get_option('client_secret')?>"></td>
          </th>
        </tr>
      </table>
      <input class="button button-primary" type="submit" value="<?php _e("Authorize", "github-api"); ?>" />
    </form>
  <?php
}

add_action( 'admin_post_oauth_submit', 'handle_oauth' );
function handle_oauth() {
    if (    isset($_POST["client_id"]) && 
            isset($_POST["client_secret"])
    ) {
      update_option( "client_id", $_POST["client_id"], TRUE );
      update_option("client_secret", $_POST["client_secret"], TRUE);
    }

    // Get the saved application info
    $client_id = get_option("client_id");
    $client_secret = get_option("client_secret");

    if ($client_id && $client_secret)
    {
      $provider = new League\OAuth2\Client\Provider\Github([
        "clientId"          =>  $client_id,
        "clientSecret"      =>  $client_secret,
        "redirectUri"       => admin_url("options-general.php?page=github"),
      ]);
    }

    if (!isset($_GET["code"]) && $_SERVER["REQUEST_METHOD"] === "POST") {
      $authUrl = $provider->getAuthorizationUrl();
      $_SESSION["oauth2state"] = $provider->getState();
      header("Location: ".$authUrl);
      exit;
    } elseif (empty($_GET["state"]) || ($_GET["state"] !== $_SESSION["oauth2state"])) {
      unset($_SESSION["oauth2state"]);
      exit("Invalid state");
    } else {
      $token = $provider->getAccessToken("authorization_code", [
          "code" => $_GET["code"]
      ]);
      update_option( "github_token", $token->getToken(), TRUE );
    }

}

add_action( 'admin_post_update_github_settings', 'github_handle_save' );
function github_handle_save() {
    $org = (!empty($_POST["gh_org"])) ? $_POST["gh_org"] : NULL;
    $repo = (!empty($_POST["gh_repo"])) ? $_POST["gh_repo"] : NULL;

    update_option( "gh_repo", $repo, TRUE );
    update_option("gh_org", $org, TRUE);

    $redirect_url = get_bloginfo("url") . "/wp-admin/options-general.php?page=github&status=success";
    header("Location: ".$redirect_url);
    exit;
}