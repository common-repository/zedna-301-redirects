<?php
/*
Plugin Name: Zedna 301 Redirects
Plugin URI: https://wordpress.org/plugins/zedna-301-redirects/
Description: In Settings, create a list of URLs that you would like to 301 redirect to another page or site. Support wildcards, GET parameters, import & export, bulk delete.
Version: 1.0
Author: Radek Mezulanik
Author URI: https://www.mezulanik.cz
*/

if (!class_exists("Zedna301redirects")) {

  class Zedna301redirects {
    
    /**
     * create_menu function
     * generate the link to the options page under settings
     * @access public
     * @return void
     */
    function create_menu() {
      add_options_page('301 Redirects', '301 Redirects', 'manage_options', '301options', array($this,'options_page'));
    }
    
    /**
     * options_page function
     * generate the options page in the wordpress admin
     * @access public
     * @return void
     */
    function options_page() {
    ?>
    <div class="wrap zedna_301_redirects">
      <script>
        //todo: This should be enqued
        jQuery(document).ready(function(){
          jQuery('span.wps301-delete').html('Delete').css({'color':'red','cursor':'pointer'}).click(function(){
              // remove element and submit
              jQuery(this).parent().parent().fadeOut(300, function(){ 
                jQuery(this).remove();
              });
          });
          
          jQuery('#remove').click(function(){
              // remove all elements and submit
              jQuery("table.widefat").fadeOut(300, function(){ 
                jQuery(this).empty();
                jQuery('#zedna_301_redirects_form').submit();
              });
          });
          
          jQuery('.zedna_301_redirects .documentation').hide().before('<p><a class="reveal-documentation" href="#">How to use</a></p>')
          jQuery('.reveal-documentation').click(function(){
            jQuery(this).parent().siblings('.documentation').slideToggle();
            return false;
          });

          jQuery("#import").change(function() {
            filename = this.files[0].name
          });

          jQuery("#import").change(function (e) {
            let importData;
            let file = e.target.files[0];
            let reader = new FileReader(file);
            reader.readAsText(file);
            reader.onload = async(e) => {
              let fileContent = e.target.result
              importData = await JSON.stringify(fileContent)

              jQuery.ajax({
              type: "POST",
              url: "/wp-admin/admin-ajax.php",
              data: {
                  action: 'import_redirects',
                  variable: importData // enter in anyname here instead of variable, you will need to catch this value using $_POST['variable'] in your php function.
              },
              success: function (output) {
                output = parseInt(output);
                if (isNaN(output)) {
                  jQuery('#zedna_301_redirects_form').after('<p class="import-error">Wrong input! Your file content is maybe empty or not in a JSON format.</p>');
                }else{
                  setTimeout(location.reload.bind(location), 2000);
                  jQuery('.import-error').remove();
                  jQuery('#zedna_301_redirects_form').after('<p>You have now <strong>'+output+'</strong> redirects! Reloading...</p>');
                }
              }
            });
            }
        });

        jQuery("#export").click(function () { 
          jQuery("<a />", {
            "download": window.location.host+"-301_redirects.json",
            "href" : "data:application/json;charset=utf-8," + encodeURIComponent(JSON.stringify(jQuery(this).data().obj)),
          }).appendTo("body")
          .click(function() {
            jQuery(this).remove()
          })[0].click();
        });

        jQuery("table.widefat").change(function (e) {
          let lastRequest = jQuery('tr:last',this).find(".table-request").val();
          let lastDestination = jQuery('tr:last',this).find(".table-destination").val();
          if(lastRequest || lastDestination){
            jQuery(this).append(`<tr>
            <td style="width:35%;"><input type="text" class="table-request" name="301_redirects[request][]" value="" style="width:99%;" /></td>
            <td style="width:2%;">&raquo;</td>
            <td style="width:60%;"><input type="text" class="table-destination" name="301_redirects[destination][]" value="" style="width:99%;" /></td>
            <td><span class="wps301-delete">Delete</span></td>
          </tr>`);
          }
        });

        });
      </script>
    
    <?php
      if (isset($_POST['301_redirects']) && is_array($_POST['301_redirects'])) {
        echo '<div id="message" class="updated"><p>Settings saved</p></div>';
      }
    ?>
    
      <h2>Zedna 301 Redirects</h2>
      
      <form method="post" id="zedna_301_redirects_form" action="options-general.php?page=301options&savedata=true">
      
      <?php wp_nonce_field( 'save_redirects', '_s301r_nonce' ); ?>

      <table class="widefat">
        <thead>
          <tr>
            <th colspan="2">Request</th>
            <th colspan="2">Destination</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td colspan="2"><small>example: /about-our-company/</small></td>
            <td colspan="2"><small>example: <?php echo get_option('home'); ?>/about/</small></td>
          </tr>
          <?php echo $this->expand_redirects(); ?>
          <tr>
            <td style="width:35%;"><input type="text" class="table-request" name="301_redirects[request][]" value="" style="width:99%;" /></td>
            <td style="width:2%;">&raquo;</td>
            <td style="width:60%;"><input type="text" class="table-destination" name="301_redirects[destination][]" value="" style="width:99%;" /></td>
            <td><span class="wps301-delete">Delete</span></td>
          </tr>
        </tbody>
      </table>

      <?php $wildcard_checked = (get_option('301_redirects_wildcard') === 'true' ? ' checked="checked"' : ''); ?>
      <p><input type="checkbox" name="301_redirects[wildcard]" id="wps301-wildcard"<?php echo $wildcard_checked; ?> /><label for="wps301-wildcard"> Use Wildcards?</label></p>
      
      <p class="submit"><input type="submit" name="submit_301" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
      </form>

      <button id="export" data-obj='<?php echo $this->export_redirects(); ?>'>Export to JSON</button>
      
      <button type="button">
      <label for="import" style="vertical-align: unset;">Import from JSON</label>
      </button>
      <input id="import" style="visibility:hidden; position: absolute; z-index: -1;" type="file" accept="application/json" />

      <button id="remove">Remove all</button>


      <div class="documentation">
        <h2>How to use</h2>
        <p>Zedna redirects work similar to the format that Apache uses: the request should be relative to your WordPress root. The destination can be either a full URL to any page on the web, or relative to your WordPress root.</p>
        <h4>Example</h4>
        <ul>
          <li><strong>Request:</strong> /old-page/</li>
          <li><strong>Destination:</strong> /new-page/</li>
        </ul>
        
        <h3>Wildcards</h3>
        <p>To use wildcards, put an asterisk (*) after the folder name that you want to redirect.</p>
        <h4>Example</h4>
        <ul>
          <li><strong>Request:</strong> /old-folder/*</li>
          <li><strong>Destination:</strong> /redirect-everything-here/</li>
        </ul>
    
        <p>You can also use the asterisk in the destination to replace whatever it matched in the request if you like. Something like this:</p>
        <h4>Example</h4>
        <ul>
          <li><strong>Request:</strong> /old-folder/*</li>
          <li><strong>Destination:</strong> /some/other/folder/*</li>
        </ul>
        <p>Or:</p>
        <ul>
          <li><strong>Request:</strong> /old-folder/*/content/</li>
          <li><strong>Destination:</strong> /some/other/folder/*</li>
        </ul>
      </div>
    </div>
    <?php
    } // end of function options_page
    
    /**
     * export_redirects function
     * utility function to return the current list of redirects in json
     * @access public
     * @return string <html>
     */
    function export_redirects() {
        $redirects = get_option('301_redirects');
      if(!empty($redirects)){
        $redirects = json_encode( $redirects);
      }
      echo $redirects;
    }

    /**
     * expand_redirects function
     * utility function to return the current list of redirects as form fields
     * @access public
     * @return string <html>
     */
    function expand_redirects() {
      $redirects = get_option('301_redirects');
      $output = '';
      if (!empty($redirects)) {
        foreach ($redirects as $request => $destination) {
          $output .= '
          
          <tr>
            <td><input type="text" class="table-request" name="301_redirects[request][]" value="'.$request.'" style="width:99%" /></td>
            <td>&raquo;</td>
            <td><input type="text" class="table-destination" name="301_redirects[destination][]" value="'.$destination.'" style="width:99%;" /></td>
            <td><span class="wps301-delete"></span></td>
          </tr>
          
          ';
        }
      } // end if
      return $output;
    }
    /**
     * save_redirects function
     * save the redirects from the options page to the database
     * @access public
     * @param mixed $data
     * @return void
     */
    function save_redirects($data) {
      if ( !current_user_can('manage_options') )  { wp_die( 'You do not have sufficient permissions to access this page.' ); }
      check_admin_referer( 'save_redirects', '_s301r_nonce' );
      
      if(is_array($_POST['301_redirects'])){
        $data = $_POST['301_redirects'];
      }else{
        $data = null;
      }
      

      $redirects = array();
      
      for($i = 0; $i < sizeof($data['request']); ++$i) {
        $request = trim( sanitize_text_field( $data['request'][$i] ) );
        $destination = trim( sanitize_text_field( $data['destination'][$i] ) );
      
        if ($request == '' && $destination == '') { continue; }
        else { $redirects[$request] = $destination; }
      }
      
      update_option('301_redirects', $redirects);
      
      if (isset($data['wildcard'])) {
        update_option('301_redirects_wildcard', 'true');
      }
      else {
        delete_option('301_redirects_wildcard');
      }
    }
    
    /**
     * redirect function
     * Read the list of redirects and if the current page 
     * is found in the list, send the visitor on her way
     * @access public
     * @return void
     */
    function redirect() {
      // this is what the user asked for (strip out home portion, case insensitive)
      $userrequest = str_ireplace(get_option('home'),'',$this->get_address());
      
      $get_parameters = (!empty(sanitize_text_field(http_build_query($_GET))) ? '?'.sanitize_text_field(http_build_query($_GET)) : null);

      $userrequest = strtok($userrequest, '?');
      $userrequest = rtrim($userrequest,'/');
      
      $redirects = get_option('301_redirects');
      if (!empty($redirects)) {
        
        $wildcard = get_option('301_redirects_wildcard');
        $do_redirect = '';
        
        // compare user request to each 301 stored in the db
        foreach ($redirects as $storedrequest => $destination) {
          // check if we should use regex search 
          if ($wildcard === 'true' && strpos($storedrequest,'*') !== false) {
            // wildcard redirect
            
            // don't allow people to accidentally lock themselves out of admin
            if ( strpos($userrequest, '/wp-login') !== 0 && strpos($userrequest, '/wp-admin') !== 0 ) {
              // Make sure it gets all the proper decoding and rtrim action
              $storedrequest = str_replace('*','(.*)',$storedrequest);
              $pattern = '/^' . str_replace( '/', '\/', rtrim( $storedrequest, '/' ) ) . '/';
              $destination = str_replace('*','$1',$destination);
              $output = preg_replace($pattern, $destination.$get_parameters, $userrequest);
              if ($output !== $userrequest) {
                // pattern matched, perform redirect
                $do_redirect = $output;
              }
            }
          }
          elseif(urldecode($userrequest) == rtrim($storedrequest,'/')) {
            // simple comparison redirect
            $do_redirect = $destination.$get_parameters;
          }
          
          // redirect. the second condition here prevents redirect loops as a result of wildcards.
          if ($do_redirect !== '' && trim($do_redirect,'/') !== trim($userrequest,'/')) {
            // check if destination needs the domain prepended
            if (strpos($do_redirect,'/') === 0){
              $do_redirect = home_url().$do_redirect;
            }
            header ('HTTP/1.1 301 Moved Permanently');
            header ('Location: ' . $do_redirect);
            exit();
          }
          else { unset($redirects); }
        }
      }
    } // end funcion redirect
    
    /**
     * getAddress function
     * utility function to get the full address of the current request
     * credit: http://www.phpro.org/examples/Get-Full-URL.html
     * @access public
     * @return void
     */
    function get_address() {
      // return the full address
      return $this->get_protocol().'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    } // end function get_address
    
    function get_protocol() {
      // Set the base protocol to http
      $protocol = 'http';
      // check for https
      if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) {
        $protocol .= "s";
      }
      
      return $protocol;
    } // end function get_protocol
    
  } // end class Zedna301redirects

} // end check for existance of class

// instantiate
$redirect_plugin = new Zedna301redirects();

if (isset($redirect_plugin)) {
  // add the redirect action, high priority
  add_action('init', array($redirect_plugin,'redirect'), 1);

  // create the menu
  add_action('admin_menu', array($redirect_plugin,'create_menu'));

  // if submitted, process the data
  if (isset($_POST['301_redirects']) && is_array($_POST['301_redirects'])) {
    add_action('admin_init', array($redirect_plugin,'save_redirects'));
  }
}

// this is here for php4 compatibility
if(!function_exists('str_ireplace')){
  function str_ireplace($search,$replace,$subject){
    $token = chr(1);
    $haystack = strtolower($subject);
    $needle = strtolower($search);
    while (($pos=strpos($haystack,$needle))!==FALSE){
      $subject = substr_replace($subject,$token,$pos,strlen($search));
      $haystack = substr_replace($haystack,$token,$pos,strlen($search));
    }
    $subject = str_replace($token,$replace,$subject);
    return $subject;
  }
}

add_action( 'wp_ajax_import_redirects', 'import_redirects' );

function import_redirects() {
  $string = str_replace('\\', '', sanitize_text_field($_POST['variable']));
  $string = rtrim($string, ',');
  $string = trim($string, "\"[]\"");
  $import_data = json_decode($string, true);
  $redirects = array();
  if(!empty($import_data)){
    foreach($import_data as $request => $destination) {
      $request = trim( sanitize_text_field( $request) );
      $destination = trim( sanitize_text_field( $destination ) );
    
      if ($request == '' && $destination == '') { continue; }
      else { $redirects[$request] = $destination; }
    }
    update_option('301_redirects', $redirects);
    echo count($redirects);
  }else{
    echo false;
  }
  
  exit();
}
