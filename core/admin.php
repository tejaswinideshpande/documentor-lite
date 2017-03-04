<?php 
if( !class_exists( 'DocumentorLiteAdmin' ) ) {
	class DocumentorLiteAdmin extends DocumentorLite {
		function __construct() {
			if ( is_admin() ) { // admin actions
				add_action('admin_menu', array(&$this, 'documentor_admin_menu'));
				add_action('admin_init', array(&$this, 'documentor_admin_resources'));
				add_action( 'admin_init', array( &$this, 'register_global_settings' ) );
				//hook for updating custom fields
				add_action( 'publish_post', array( &$this, 'update_custom_fields' ) );
				add_action( 'publish_page', array( &$this, 'update_custom_fields' ) );
				add_action( 'edit_post', array( &$this, 'update_custom_fields' ) );
				add_action( 'edit_attachment', array( &$this, 'update_custom_fields' ) ); 
				//delete section when post is deleted
				add_action( 'wp_trash_post', array( &$this, 'doc_delete_section' ) );
				add_filter( 'plugin_action_links',  array( &$this,'documentor_action_links'), 10, 2 );
				//add css in admin header
				add_action( 'admin_head', array( &$this,'admin_css') );

			}
		}
		function admin_css(){ ?>
		     <style>
			     #menu-posts-documentor-sections {
				  display: none !important;
			     }
		     </style>
		<?php
		}
		function documentor_action_links( $links, $file ) {
			if ( $file != DOCUMENTORLITE_PLUGIN_BASENAME )
				return $links;
			
			$url = DocumentorLite::documentor_admin_url(array('page'=>'documentor-admin'));

			$manage_link = '<a href="' . esc_attr( $url ) . '">'
				. esc_html( __( 'Manage','documentor-lite') ) . '</a>';

			array_unshift( $links, $manage_link );

			return $links;
		}
		// function for adding guides page to wp-admin
		function documentor_admin_menu() {
			//User Level
			$documentor_global_curr = get_option('documentor_global_options');
			$user_level= (isset($documentor_global_curr['user_level'])?$documentor_global_curr['user_level']:'publish_posts');			  
			add_menu_page( __('Documentor','documentor-lite'), __('Documentor','documentor-lite'), $user_level,'documentor-admin', array(&$this, 'documentor_guides_page'), DocumentorLite::documentor_plugin_url( 'core/images/logo.png'));
			
			add_submenu_page( 'documentor-admin', __('Global Settings','documentor-lite'), __('Global Settings','documentor-lite'), 'manage_options','documentor-global-settings', array(&$this, 'documentor_lite_global_settings'));

			if( function_exists( 'add_meta_box' ) && function_exists('icl_plugin_action_links') ) {
				$post_types = get_post_types(); 
				foreach($post_types as $post_type) {
					add_meta_box( 'documentor_box', __( 'Documentor' , 'documentor-lite'), array(&$this, 'documentor_custom_box'), $post_type, 'advanced' );
				}
			}
			
		}	
		//update custom fields
		function update_custom_fields( $post_id ) {
			//menu title
			if( isset( $_POST['_documentor_menutitle'] ) ) {
				$documentor_menutitle = get_post_meta( $post_id, '_documentor_menutitle', true );
				$post_documentor_menutitle = $_POST['_documentor_menutitle'];
				if( $documentor_menutitle != $post_documentor_menutitle ) {
					update_post_meta( $post_id, '_documentor_menutitle', $post_documentor_menutitle );	
				}
			}
			//section title
			if( isset( $_POST['_documentor_sectiontitle'] ) ) {
				$documentor_sectiontitle = get_post_meta( $post_id, '_documentor_sectiontitle', true );
				$post_documentor_sectiontitle = $_POST['_documentor_sectiontitle'];
				if( $documentor_sectiontitle != $post_documentor_sectiontitle ) {
					update_post_meta( $post_id, '_documentor_sectiontitle', $post_documentor_sectiontitle );	
				}
			}
			//attach WooCommerce product to document
			if( isset( $_POST['documentor_attachid'] ) ) {
				$documentor_attachid = get_post_meta( $post_id, '_documentor_attachid', true );
				$post_documentor_attachid = $_POST['documentor_attachid'];
				if( $documentor_attachid != $post_documentor_attachid ) {
					update_post_meta( $post_id, '_documentor_attachid', $post_documentor_attachid );	
				}
			}
		}
		//add metabox callback function
		function documentor_custom_box() {
			global $post;
			$post_id = $post->ID;
			$documentor_menutitle = get_post_meta($post_id, '_documentor_menutitle', true);
			$documentor_sectiontitle = get_post_meta($post_id, '_documentor_sectiontitle', true);
			$documentor_attachid = get_post_meta($post_id, '_documentor_attachid', true);	
			$post_type = get_post_type($post_id);			
		?>
			<table class="form-table" style="margin: 0;">
				<tr valign="top">
					<td scope="row">
						<label for="documentor_menutitle"><?php _e('Menu Title ','documentor-lite'); ?></label>
					</td>
					<td>
						<input type="text" name="_documentor_menutitle" class="documentor_menutitle" value="<?php echo esc_attr($documentor_menutitle);?>" size="50" />
					</td>
				</tr>
				<tr valign="top">
					<td scope="row">
						<label for="documentor_sectiontitle"><?php _e('Section Title ','documentor-lite'); ?></label>
					</td>
					<td>
						<input type="text" name="_documentor_sectiontitle" class="documentor_sectiontitle" value="<?php echo esc_attr($documentor_sectiontitle);?>" size="50" />
					</td>
				</tr>
			</table>
		<?php }
		function documentor_admin_resources() {
			if ( isset($_GET['page']) && ( $_GET['page'] == 'documentor-admin' || $_GET['page'] == 'documentor-global-settings' || $_GET['page'] == 'documentor-new' ) ) {
				wp_register_script('jquery', false, false, false, false);
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-ui-autocomplete' ); //autocomplete
				if ( ! did_action( 'wp_enqueue_media' ) ) wp_enqueue_media();
				wp_enqueue_script( 'jquery-nestable', DocumentorLite::documentor_plugin_url( 'core/js/jquery.nestable.js' ), array('jquery'), DOCUMENTORLITE_VER, false);
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'dataTableCSS', DocumentorLite::documentor_plugin_url( 'core/css/jquery.dataTables.min.css' ), false, DOCUMENTORLITE_VER, 'all');
				
				wp_enqueue_script( 'dataTableJS', DocumentorLite::documentor_plugin_url( 'core/js/jquery.dataTables.min.js' ),	array('jquery'), DOCUMENTORLITE_VER, false);
				
				wp_enqueue_style( 'quicksand-font', 'https://fonts.googleapis.com/css?family=Play', false, DOCUMENTORLITE_VER, 'all' );
				
				wp_enqueue_style( 'documentor-admin-css', DocumentorLite::documentor_plugin_url( 'core/css/admin.css' ), false, DOCUMENTORLITE_VER, 'all');
				wp_enqueue_style( 'documentor-bulma-css', DocumentorLite::documentor_plugin_url( 'core/css/bulma.css' ), false, DOCUMENTORLITE_VER, 'all');
				
				wp_enqueue_script( 'loadingoverlay', DocumentorLite::documentor_plugin_url( 'core/js/loadingoverlay.min.js' ),	array('jquery'), DOCUMENTORLITE_VER, false);
				
				wp_enqueue_script( 'documentor-admin-js', DocumentorLite::documentor_plugin_url( 'core/js/admin.js' ),array('jquery'), DOCUMENTORLITE_VER, true);
				wp_enqueue_script( 'documentor-modal-js', DocumentorLite::documentor_plugin_url( 'core/js/jquery.leanModal.min.js' ),array('jquery'), DOCUMENTORLITE_VER, false);
			}
		}
		function documentor_guides_page() {
			// Edit Document
			$id = 1;
			$guide=new DocumentorLiteGuide($id);
			$documentor_curr = $guide->get_settings();
			if(isset($_POST['save-settings'])) {
				$numarr = array('indexformat', 'navmenu_default', 'navmenu_fsize', 'actnavbg_default', 'sectitle_default', 'sectitle_fsize', 'seccont_default', 'seccont_fsize', 'feedback', 'feedback_frmname', 'feedback_frmemail', 'feedback_frmtext', 'feedback_frmcapcha');
				foreach( $_POST['documentor_options'] as $key=>$value ) {
					if(in_array($key,$numarr)) {
						$value = intval($value);
					} else {
						if( is_string( $value ) ) {
							$value = stripslashes($value);
							$value = sanitize_text_field($value);	
						}
					}
					$new_settings_value[$key]=$value;
				}
				if(isset($_POST['documentor_options']['skin']) && $documentor_curr['skin'] != $_POST['documentor_options']['skin'] ) { 
					/* Populate skin specific settings */	
					$skin = $_POST['documentor_options']['skin'];
					$skin_defaults_str='default_settings_'.$skin;
					require_once ( dirname( dirname(__FILE__) ). '/skins/'.$skin.'/settings.php');
					global ${$skin_defaults_str};
					if(count(${$skin_defaults_str})>0){
						foreach(${$skin_defaults_str} as $key=>$value){
							$new_settings_value[$key]=$value;	
						} 
					}
					/* END - Populate skin specific settings */ 
				}
				$newsettings = json_encode($new_settings_value);
				$newtitle = ( isset( $_POST['guidename'] ) ) ? sanitize_text_field($_POST['guidename']) : ''; 
				$guide->update_settings( $newsettings , $newtitle );
			} 	
			$guide->admin_view();
		}
		//global settings
		function documentor_lite_global_settings() { 
			$documentor_global_curr = get_option('documentor_global_options');
			
			$doc = new DocumentorLite();
			$global_options = $doc->documentor_global_options;
			$group='documentor-global-group';
			$documentor_global_options = 'documentor_global_options';
			foreach( $global_options as $key=>$value ) {
				if( !isset( $documentor_global_curr[$key] ) ) 
					$documentor_global_curr[$key]='';
			}
			
			?>
			<div class="global_settings wrap">
			<div class="columns"><div class="column is-two-thirds">
				<h2 class="title is-4"> <?php _e('Documentor Global Settings','documentor-lite'); ?> </h2>
				<form name="documentor_lite_global_settings" method="post" action="options.php">
					<?php settings_fields($group); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Enable Inline Sections','documentor-lite'); ?></th>
							<td>
								<div class="eb-switch eb-switchnone">
									<input type="hidden" name="<?php echo $documentor_global_options;?>[custom_post]" class="hidden_check" id="documentor_custom_post" value="<?php echo esc_attr($documentor_global_curr['custom_post']);?>">
									<input id="documentor_custompost" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked("1", $documentor_global_curr['custom_post']); ?> >
									<label for="documentor_custompost"></label>
								</div>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Supported Post Types for adding sections','documentor-lite'); ?></th>
							<td>
								<select name="<?php echo $documentor_global_options;?>[custom_posts][]" multiple="multiple" size="3" style="min-height:6em;">
								<?php 
								$args=array(
								  'public'   => true
								); 
								$output = 'objects'; // names or objects, note names is the default
								$post_types=get_post_types($args,$output); 
								
								$exclude_pts = array('attachment','revision','nav_menu_item');
								foreach($exclude_pts as $exclude_pt)
   									 unset($post_types[$exclude_pt]);
								
								$custom_posts_arr=$documentor_global_curr['custom_posts'];
								if(!isset($custom_posts_arr) or !is_array($custom_posts_arr) ) $custom_posts_arr=array();
										foreach($post_types as $post_type) { ?>
										  <option value="<?php echo $post_type->name;?>" <?php if(in_array($post_type->name,$custom_posts_arr)){echo 'selected';} ?>><?php echo $post_type->labels->name;?></option>
										<?php } ?>
								</select>
							</td>
						</tr>
						<?php // Documentor 1.3.3- start ?>
						<tr valign="top">
							<th scope="row"><?php _e('Minimum User Level to create and manage guides','documentor-lite'); ?></th>
							<td><select name="<?php echo $documentor_global_options;?>[user_level]" id="documentor_user_level">
							<option value="manage_options"<?php if ($documentor_global_curr['user_level'] == "manage_options"){ echo "selected";}?> ><?php _e('Administrator','documentor-lite'); ?></option>
							
							<option value="edit_others_posts" <?php if ($documentor_global_curr['user_level'] == "edit_others_posts"){ echo "selected";}?> ><?php _e('Editor and Admininstrator','documentor-lite'); ?></option>
							<option value="publish_posts" <?php if ($documentor_global_curr['user_level'] == "publish_posts"){ echo "selected";}?> ><?php _e('Author, Editor and Admininstrator','documentor-lite'); ?></option>
							<option value="edit_posts" <?php if ($documentor_global_curr['user_level'] == "edit_posts"){ echo "selected";}?> ><?php _e('Contributor, Author, Editor and Admininstrator','documentor-lite'); ?></option>
							</select>
							</td>
						</tr>
						<?php // Documentor 1.3.3- end ?>
						<tr valign="top">
							<th scope="row"><?php _e('Custom Styles','documentor-lite'); ?></th>
							<td>
								<textarea name="<?php echo $documentor_global_options;?>[custom_styles]"  rows="5" cols="40" class="code"><?php echo $documentor_global_curr['custom_styles']; ?></textarea>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="Save" class="button-primary" value="Save Changes">
					</p>
				</form>	
			</div><!-- /.column .is-two-thirds -->
			<div class="documentor-sidebar column is-one-third">
				<div class="container"><div class="card">
				  <header class="card-header">
					<p class="documentor-logo">
					  <img src="<?php echo DocumentorLite::documentor_plugin_url( 'core/images/documentor-logo.png' );?>" /><small><?php _e('Version','documentor-lite'); echo DOCUMENTORLITE_VER;?></small>
					</p>
				  </header>
				  <footer class="card-footer">
					<a class="card-footer-item" href="https://documentor.in/docs/" target="_blank">Need Help?</a>
					<a class="card-footer-item" href="https://documentor.in/contact-us/" target="_blank">Get Support</a>
				  </footer>
				</div></div>
			</div><!-- /.documentor-sidebar .column -->
			</div><!-- /.columns -->
			</div><!-- /.global_settings -->
		<?php
		}
		function register_global_settings() {
			register_setting( 'documentor-global-group', 'documentor_global_options' );
		}
		//delete post from sections table if post is deleted from posts table
		function doc_delete_section( $pid ) {
			global $wpdb,$table_prefix;
			$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$table_prefix."posts WHERE ID = %d", $pid ) ); 
			if( $post != NULL ) {
				$wpdb->delete( $table_prefix.DOCUMENTORLITE_SECTIONS, array( 'post_id' => $pid ), array( '%d' ) );		
			}
		}
			
	}//end class
}//end if
new DocumentorLiteAdmin();
?>
