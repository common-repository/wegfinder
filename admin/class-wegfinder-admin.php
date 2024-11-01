<?php

// The admin-specific functionality of the plugin.

class Wegfinder_Admin {

	private $plugin_name;
	
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wegfinder-admin.css', array(), $this->version, 'all' );	
	}

	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wegfinder-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/auto-complete.min.js', array( 'jquery' ), $this->version, false );
	}

	// Links on Plugins Overview Page
	public function plugin_action_links( $links ) {
	   $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wegfinder-list') ) .'">'.__('Destinations','wegfinder').'</a>';
	   $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wegfinder-new') ) .'">'.__('New','wegfinder').'</a>';
	   $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wegfinder-settings') ) .'">'.__('Design','wegfinder').'</a>';
	   return $links;
	}

	// Gutenberg Block Editor, Rich Text Toolbar Button
	public function wegfinder_editor_richtext_toolbar_script_register() {
		wp_register_script(
			'wegfinder-editor-richttext-toolbar-js',
			plugins_url( 'js/wegfinderEditorRichTextToolbar.js', __FILE__ ),
			array( 'wp-rich-text', 'wp-element', 'wp-editor' )
		);
	}

	// Gutenberg Block Editor, Rich Text Toolbar Button	
	public function wegfinder_editor_richtext_toolbar_enqueue_assets_editor() {
		
		global $wpdb;
		$scriptdata = $wpdb->get_results( "SELECT `id`, `name` FROM {$wpdb->prefix}wegfinder ORDER BY `name`");
		wp_localize_script('wegfinder-editor-richttext-toolbar-js', ' wegfinderEditorRichtextToolbarMenuData', $scriptdata);
		
		$translation = ['generic' => __('Generic, no destination set','wegfinder'), 'new' => __('New Destination','wegfinder')];
		wp_localize_script('wegfinder-editor-richttext-toolbar-js', ' wegfinderEditorRichtextToolbarTranslations', $translation);
		
		
		wp_enqueue_script( 'wegfinder-editor-richttext-toolbar-js' );	
	}

	// Quicktags Classic Editor
	public function quicktags() {
		if (wp_script_is('quicktags')){
			?>
			<script type="text/javascript">
				QTags.addButton( 'wegfinder', 'wegfinder', '[wegfinder id=""]', '', 'w', 'wegfinder', 21 );
			</script>
			<?php
		}
	}
	
	// TinyMCE Button, Classic Editor - enqueue script
	public function enqueue_mce_plugin_scripts($plugin_array)
	{
		$plugin_array["wegfinder"] =  plugin_dir_url(__FILE__) . "js/tinymce.js";   
		return $plugin_array;
	}

	// TinyMCE Button, Classic Editor - register buttons
	public function register_mce_buttons_editor($buttons)
	{
		array_push($buttons, "wegfinder");
		return $buttons;
	}

	// TinyMCE Button, Classic Editor - provide json data for ajax call
 	public function wegfinder_menudata_ajax(){
   		global $wpdb;
		$scriptdata = $wpdb->get_results( "SELECT `id` AS `value`, `name` AS `text`  FROM {$wpdb->prefix}wegfinder ORDER BY `name`");
 		echo json_encode($scriptdata);
		exit;
	}
	
	
	// Options Page: Menu & Submenu
	public function options_page() {
		
		add_menu_page(
			'wegfinder',
			'wegfinder',
			'manage_options',
			'wegfinder',
			 array( $this, 'options_page_list'),
			plugin_dir_url( __FILE__ ).'img/wegfinderLogoSW.svg',			
			20
		);
			
		add_submenu_page( 'wegfinder', 'wegfinder - '.__('Destinations','wegfinder'), __('All Destinations','wegfinder'), 'edit_posts', 'wegfinder-list', array( $this, 'options_page_list') );
		add_submenu_page( 'wegfinder', 'wegfindner - '.__('New Destination','wegfinder'), __('Add New','wegfinder'), 'edit_posts', 'wegfinder-new', array( $this, 'options_page_add') );
		add_submenu_page( 'wegfinder', 'wegfindner - '.__('Design','wegfinder'), __('Design','wegfinder'), 'manage_options', 'wegfinder-settings', array( $this, 'options_page_settings') );		
		
		// no own page for main menu item
		add_action( 'admin_head', function() {
    		remove_submenu_page( 'wegfinder', 'wegfinder' );
		});
		
	}
	
	
	// Options Page: Add New Destination
	public function options_page_add() {
		
		// check user capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}	
		
		// Render Header 
		$this->render_option_header(__('New Destination','wegfinder'));
		
		// Add new Entry command?
		if ( isset($_POST['wegfinder_entry_command']) && $_POST['wegfinder_entry_command'] == 'add' ){
			
			// Try to add new entry
			if( isset($_POST['wegfinder_entry_name']) && 
				!empty($_POST['wegfinder_entry_name']) &&
				isset($_POST['wegfinder_entry_locname']) && 
				!empty($_POST['wegfinder_entry_locname']) &&
			   	isset($_POST['wegfinder_entry_locid']) && 
				!empty($_POST['wegfinder_entry_locid'])) {
				
				// Add Entry
				if ($this->db_add($_POST['wegfinder_entry_name'], $_POST['wegfinder_entry_locname'], $_POST['wegfinder_entry_locid'], $_POST['wegfinder_entry_arrival'])) {
					$this->render_option_update(__('Destination created.','wegfinder'), false, __('OK','wegfinder'), __('Show destinations.','wegfinder'), "?page=wegfinder-list");
				} else {
					$this->render_option_update(__('The destination could not be created.','wegfinder'), true, __('ERROR','wegfinder'));
				}				
			} else {
				$this->render_option_update(__('The destination could not be created.','wegfinder'), true, __('ERROR','wegfinder'));
			}
		}
		
		// Render Form & Footer		
		$this->render_option_form_entry('add', __('Create','wegfinder'));
		$this->render_option_footer();

	}
	
	
	
	// Options Page: Modify a Destination
	public function options_page_modify($id) {
		
		// check user capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		
		// Add new Entry command?
		if ( isset($_POST['wegfinder_entry_command']) && $_POST['wegfinder_entry_command'] == 'modify' ){
			
			// Try to add new entry
			if( isset($_POST['wegfinder_entry_id']) && 
				!empty($_POST['wegfinder_entry_id']) &&
				isset($_POST['wegfinder_entry_name']) && 
				!empty($_POST['wegfinder_entry_name']) &&
				isset($_POST['wegfinder_entry_locname']) && 
				!empty($_POST['wegfinder_entry_locname']) &&
			   	isset($_POST['wegfinder_entry_locid']) && 
				!empty($_POST['wegfinder_entry_locid']) ) {
				
				// Modify Entry
				if ($this->db_modify($_POST['wegfinder_entry_id'], $_POST['wegfinder_entry_name'], $_POST['wegfinder_entry_locname'], $_POST['wegfinder_entry_locid'], $_POST['wegfinder_entry_arrival'])) {
					$this->render_option_update(sprintf(__( 'Changes of destination #%s saved.', 'wegfinder' ), $_POST['wegfinder_entry_id']), false, __('OK','wegfinder'));
				} else {
					$this->render_option_update(sprintf(__( 'Changes of destination #%s could not be saved.', 'wegfinder' ), $_POST['wegfinder_entry_id']), true, __('ERROR','wegfinder'));
				}				
			} else {
				$this->render_option_update(sprintf(__( 'Changes of destination #%s could not be saved.', 'wegfinder' ), $_POST['wegfinder_entry_id']), true, __('ERROR','wegfinder'));
			}
			
			return;
		}
		
		// Render  Header, Form & Footer		
		$this->render_option_header(__('Edit Destination','wegfinder'));
		$this->render_option_form_entry('modify', __('Save','wegfinder'), $this->db_entryById($id,true), __('Cancel','wegfinder'));
		$this->render_option_footer();
		
	}
	
	
	
	
	
	// Options Page: List all Destinations
	public function options_page_list() {
		
		// Check user capabilities
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}	
		
		// Modify: Show Form 
		if(isset($_POST['wegfinder_modify_id']) && !empty($_POST['wegfinder_modify_id'])) {
			$this->options_page_modify($_POST['wegfinder_modify_id']);
			return;
		}
		
		// Render Header
		$this->render_option_header(__('Destinations','wegfinder'),null,__('Add New','wegfinder'),"?page=wegfinder-new");
		
		// Copy: Code copied to clipboard
		$this->render_option_update(__( 'Shortcode copied to clipboard', 'wegfinder' ), false, "", null, null, "wegfinder_update_copied_to_clipboard");
		
		// Modify: Save Changes
		if( isset($_POST['wegfinder_entry_command']) &&$_POST['wegfinder_entry_command'] == 'modify') {
			$this->options_page_modify($_POST['wegfinder_entry_id']);
		}	
			
		// Delete 
		if(isset($_POST['wegfinder_delete_id']) && !empty($_POST['wegfinder_delete_id'])) {
			if ($this->db_delete($_POST['wegfinder_delete_id'])) {
				$this->render_option_update(sprintf(__( 'Destination #%s has been deleted.', 'wegfinder' ), $_POST['wegfinder_delete_id']), false);							
			} else {
				$this->render_option_update(sprintf(__( 'Destination #%s could not be deleted.', 'wegfinder' ), $_POST['wegfinder_delete_id']), true, __('ERROR','wegfinder'));
			}
		}
				
		// Get destinations and render table
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wegfinder ORDER BY `name`");		
		$this->render_option_list($results);

		// Render footer
		$this->render_option_footer();
	}
	

	
	// Options Page: Button Design
	public function options_page_settings() {

		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}	
		
		$this->render_option_header(__('Button Design','wegfinder'));
		
		if (strpos($_SERVER['REQUEST_URI'], 'settings-updated=true') !== false) {
			$this->render_option_update(__( 'Design has been saved.', 'wegfinder' ), false, __('OK','wegfinder'));
		}
		?>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><label for="wegfinder_add_locname"><?php _e('Preview','wegfinder'); ?></label></th>
				<td><div id="wegfinderDesignDemoButton"></div></td>				
			</tr>										
			</tbody>	
		</table>	
		<hr>	
		<form action='options.php' method='post' onsubmit='wegfinder_settings_submit();'>
		<?php
	
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button(__('Save','wegfinder'), 'primary', 'wegfinder_settings_bu_save', false);
		echo "&nbsp;";		
		submit_button(__('Reset','wegfinder'), 'secondary', 'wegfinder_settings_bu_reset', false, array( 'onclick' => 'wegfinder_settings_reset();' ));		
		
		?>
		<script>wegfinder_settings_select_style(false);</script>
		</form>
		<br>
		<?php

		// Render footer
		$this->render_option_footer();
		
	}
	
	

	// Settings: Button Design
	public function wegfinder_settings_init(  ) { 

		register_setting( 'pluginPage', 'wegfinder_settings' );

		add_settings_section(
			'wegfinder_pluginPage_section', 
			__( '', 'wegfinder' ), 
			array($this, 'wegfinder_settings_section_callback'), 
			'pluginPage'
		);

		add_settings_field( 
			'wegfinder_style', 
			__( 'Style', 'wegfinder' ), 
			array($this, 'wegfinder_style_render'), 
			'pluginPage', 
			'wegfinder_pluginPage_section' 
		);

		add_settings_field( 
			'wegfinder_custom_color', 
			__( 'Background (individual)', 'wegfinder' ), 
			array($this, 'wegfinder_custom_color_render'), 
			'pluginPage', 
			'wegfinder_pluginPage_section' 
		);

		add_settings_field( 
			'wegfinder_custom_text_color', 
			__( 'Text (individual)', 'wegfinder' ), 
			array($this, 'wegfinder_custom_text_color_render'), 
			'pluginPage', 
			'wegfinder_pluginPage_section' 
		);

		add_settings_field( 
			'wegfinder_size', 
			__( 'Size', 'wegfinder' ), 
			array($this, 'wegfinder_size_render'), 
			'pluginPage', 
			'wegfinder_pluginPage_section' 
		);

		add_settings_field( 
			'wegfinder_target', 
			__( 'Target Frame', 'wegfinder' ), 
			array($this, 'wegfinder_target_render'), 
			'pluginPage', 
			'wegfinder_pluginPage_section' 
		);
	}

	// Options Design - render input for setting value
	public function wegfinder_style_render(  ) { 
		$options = get_option( 'wegfinder_settings' );
		?>
		<select name='wegfinder_settings[wegfinder_style]' onchange='wegfinder_settings_select_style();'>
			<option value='pink' <?php selected( $options['wegfinder_style'], 'pink' ); ?>><?php _e('pink','wegfinder'); ?></option>
			<option value='blue' <?php selected( $options['wegfinder_style'], 'blue' ); ?>><?php _e('blue','wegfinder'); ?></option>
			<option value='red' <?php selected( $options['wegfinder_style'], 'red' ); ?>><?php _e('red','wegfinder'); ?></option>
			<option value='custom' <?php selected( $options['wegfinder_style'], 'custom' ); ?>><?php _e('individual','wegfinder'); ?></option>
		</select>
		<?php
	}

	// Options Design - render input for setting value
	public function wegfinder_custom_color_render(  ) { 
		$options = get_option( 'wegfinder_settings' );
		?>
		<input type='color' name='wegfinder_settings[wegfinder_custom_color]'  value='<?php echo $options['wegfinder_custom_color']; ?>' onchange='wegfinder_settings_demo_update();'>
		<?php
	}

	// Options Design - render input for setting value
	public function wegfinder_custom_text_color_render(  ) { 
		$options = get_option( 'wegfinder_settings' );
		?>
		<input type='radio' name='wegfinder_settings[wegfinder_custom_text_color]' <?php checked( $options['wegfinder_custom_text_color'], 'white' ); ?> value='white' onchange='wegfinder_settings_demo_update();'> <?php _e('white','wegfinder'); ?> &nbsp;&nbsp;&nbsp;
		<input type='radio' name='wegfinder_settings[wegfinder_custom_text_color]' <?php checked( $options['wegfinder_custom_text_color'], 'black' ); ?> value='black' onchange='wegfinder_settings_demo_update();'> <?php _e('black','wegfinder'); ?>
		<?php
	}

	// Options Design - render input for setting value
	public function wegfinder_size_render(  ) { 
		$options = get_option( 'wegfinder_settings' );
		?>
		<input type="range" min="1" max="7" name='wegfinder_settings[wegfinder_size]' value='<?php echo $options['wegfinder_size']; ?>'  onchange='wegfinder_settings_demo_update();'>
		<?php
	}

	// Options Design - render input for setting value
	public function wegfinder_target_render(  ) { 
		$options = get_option( 'wegfinder_settings' );
		?>
		<select name='wegfinder_settings[wegfinder_target]' onchange='wegfinder_settings_demo_update();'>
			<option value='wegfinder' <?php selected( $options['wegfinder_target'], 'wegfinder' ); ?>><?php _e('Own window (wegfinder)','wegfinder'); ?></option>
			<option value='_self' <?php selected( $options['wegfinder_target'], '_self' ); ?>><?php _e('Same window (_self)','wegfinder'); ?></option>
			<option value='_blank' <?php selected( $options['wegfinder_target'], '_blank' ); ?>><?php _e('New window (_blank)','wegfinder'); ?></option>
		</select>
		<?php
	}

	// Options Design - render input for setting value
	public function wegfinder_settings_section_callback(  ) { 
	}

	
	
	/******************* RENDER FUNCTIONS*/
	
	private function render_option_header($headline, $description=null, $buttonLabel=null, $buttonLink=null) {
		?>
		<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo $headline; ?></h1>
		<?php
		if ($buttonLabel &&  $buttonLink) {
			?>
			<a href="<?php echo $buttonLink; ?>" class="page-title-action"><?php echo $buttonLabel; ?></a>
			<?php
		}	
		?>
		<hr class="wp-header-end">
		<?php
		if ($description) {
			?>
			<p><?php echo $description; ?></p>
			<?php
		}
		?>
			<br>
		<?php
	}
	
	private function render_option_footer() {
		?>
		</div>
		<?php
	}
	
	
	private function render_option_update($message = "", $error = false, $topic = "", $linkLabel = "", $linkURL= "", $hiddenID="") {
		
		if ($error) {
			$classlist = 'updated settings-error error';
		} else {
			$classlist = 'updated settings-error notice';
		}
		
		$id = "";
		if($hiddenID !== "") {
			$classlist .= ' hidden';
			$id = 'id="'.$hiddenID.'" ';
		}
		
		
		if($linkLabel !== "" && $linkURL !== "") {
			$linkHMTL = ' <a href="'.$linkURL.'">'.$linkLabel.'</a>';
		} else {
			$linkHMTL = '';
		}
		
		if ($topic !== "") {
			$topicHTML = '<strong>'.$topic.':</strong> ';
		} else {
			$topicHTML = '';
		}
		
		echo '<div '.$id.'class="'.$classlist.'"><p>'.$topicHTML.$message.$linkHMTL.'</p></div>';
		
	}
	
	
	
	private function render_option_form_entry($command="add", $submitLabel = null, $data = null, $backLabel = null, $formAction = null) {

		if (!$submitLabel) {
			$submitLabel = __('Create','wegfinder');	
		}
		
		if (!$formAction) {
			$formAction = $_SERVER['REQUEST_URI'];	
		}
		
		// Default Data?
		if (!$data) {			
			$data = $this->dataDefault();
		}
		
		// Split Date/Time Values
		$valDate = "";
		$valTime = "";
		try {
			if (!empty($data->arrival) && $data->arrival !== "" && $data->arrival !== "0000-00-00 00:00:00") {
				$valDate = date("Y-m-d",strtotime($data->arrival));
				$valTime = date("H:i",strtotime($data->arrival));
			} 
		} catch(Exception $e) {}
		
		?>
		<form action="<?php echo $formAction; ?>" method="POST" id="wegfinder_form_entry">
		<input type="hidden" name="wegfinder_entry_arrival" id="wegfinder_entry_arrival" value="<?php echo $data->arrival; ?>">
		<input id="wegfinder_entry_locid"  type="hidden" name="wegfinder_entry_locid" value="<?php echo $data->locid; ?>">
		<input id="wegfinder_entry_id"  type="hidden" name="wegfinder_entry_id" value="<?php echo $data->id; ?>">
		<input id="wegfinder_entry_command"  type="hidden" name="wegfinder_entry_command" value="<?php echo $command; ?>">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="wegfinder_entry_locname"><?php _e('Search','wegfinder'); ?>:</label></th>
					<td><input id="wegfinder_entry_locname" autofocus type="text" name="wegfinder_entry_locname" placeholder="<?php _e('Search','wegfinder'); ?> ..." size="43" value="<?php echo $data->locname; ?>"><br><p class="description" id="tagline-description"><?php _e('Search for cities, addresses, stations or places.','wegfinder'); ?></p></td>				
				</tr>							
				<tr>
					<th scope="row"><label for="wegfinder_entry_name"><?php _e('Name','wegfinder'); ?>:</label></th>
					<td><input type="text" id="wegfinder_entry_name" name="wegfinder_entry_name" size="43" value="<?php echo $data->name; ?>"><p class="description" id="tagline-description"><?php _e('Reference name for your destination.','wegfinder'); ?></p>
					</td>
				</tr>			
				<tr>
					<th scope="row"><label for="wegfinder_entry_date"><?php _e('Arrival','wegfinder'); ?>:</label></th>
					<td><input type="date" name="wegfinder_entry_date" id="wegfinder_entry_date" onchange="wegfinder_add_ts_set();" value="<?php echo $valDate; ?>"> &nbsp;um&nbsp;<input type="time" name="wegfinder_entry_time" id="wegfinder_entry_time" onchange="wegfinder_add_ts_set();" value="<?php echo $valTime; ?>">&nbsp;Uhr<p class="description" id="tagline-description"><?php _e('Optional','wegfinder'); ?>. <?php _e('E.g. select an arrival time for events.','wegfinder'); ?></p></td>
				</tr>	
			</tbody>
		</table>
		<br>
		<?php 
		submit_button($submitLabel, 'primary', 'wegfinder_entry_submit', false); 
		if($backLabel) {
			?>
			&nbsp;
			<button type="button" class="button button-secondary" onclick="window.history.back();" ><?php echo $backLabel; ?></button>
			<?php
		}		
		?>
		&nbsp;
		<button type="button" class="button button-secondary" onclick="document.getElementById('wegfinder_form_entry').reset();" ><?php _e('Reset','wegfinder'); ?></button>				
		</form>	
		<?php
	}
	
	

	
	
		private function render_option_list($results) {
				?>	
		<form action="<?= $_SERVER['REQUEST_URI']?>" method="POST" id="wegfinder_form">	
		<?php
		
		if (count($results) > 0) {
			?>			
			<input type="hidden" name="wegfinder_delete_id" id="wegfinder_delete_id" value="">
			<input type="hidden" name="wegfinder_modify_id" id="wegfinder_modify_id" value="">
			<table class="wp-list-table widefat striped posts">
				<thead class="wegfinder_tbody">		
					<tr class="">
			    		<th class="manage-column num sortable desc"><?php _e('ID','wegfinder'); ?></th>
						<th class="manage-column column-primary sortable desc"><?php _e('Name','wegfinder'); ?></th>
						<th class="manage-column column-date sortable asc"><?php _e('Arrival','wegfinder'); ?></th>	
						<th class="manage-column"><?php _e('Shortcode','wegfinder'); ?></th>
					</tr>
				</thead>
				<tbody>		
			<?php
			
			foreach ($results as $key => $result) {
				?>
				<tr class="format-standard">
					<th style="text-align:center;"><?php echo $result->id ?></th>
					
					<td class="title column-title has-row-actions column-primary page-title"><strong><a href="javascript: document.getElementById('wegfinder_modify_id').value = '<?php echo $result->id;?>'; document.getElementById('wegfinder_form').submit();"  target="_new" title="<?php printf(__( 'Show route to %s on wegfinder.at.', 'my-plugin' ), $result->name); ?>"><?php echo $result->name ?></a></strong><?php
					if ($result->name !== $result->locname) {
						?><i><?php echo $result->locname ?></i><?php
					} 
					?><div class="row-actions">
						<span class="edit"><a href="javascript: document.getElementById('wegfinder_modify_id').value = '<?php echo $result->id;?>'; document.getElementById('wegfinder_form').submit();" ><?php  _e('Edit','wegfinder'); ?></a> | </span>
						<span class="trash"><a href="javascript: document.getElementById('wegfinder_delete_id').value = '<?php echo $result->id;?>'; document.getElementById('wegfinder_form').submit();"><?php  _e('Delete','wegfinder'); ?></a> | </span>
							<span ><a target="wegfinder" href="<?php echo Wegfinder::get_button_URL($result->id) ?>" ><?php  _e('Show route','wegfinder'); ?></a> | </span>							
						<span class="clipboard"><a href="javascript: wegfinder_clipboard(<?php echo $result->id ?>);" ><?php  _e('Copy code','wegfinder'); ?></a</span>
						<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
						
					</div></td>
					<td class="date column-date" style="white-space:nowrap;">
					<?php
					if ($result->arrival !== '0000-00-00 00:00:00') {
						echo date('d.m.Y H:i', strtotime($result->arrival));
					} else {
						?>
						<span aria-hidden="true">—</span>
						<?php
					}
					?>
					</td>					
					<td><span style="white-space:nowrap;">[wegfinder id="<?php echo $result->id ?>"]</span></td>					
				</tr>
			<?php
			}
			?>
			</tbody>
			</table>
			</form>
			<?php
		} else {
			?>
			<br><br>
			<p><?php _e('No shortcodes created, yet.','wegfinder'); ?></p>
			<button type="button" class="button button-primary" onclick="window.location.href='?page=wegfinder-new';" title="<?php _e('New Destination','wegfinder'); ?>"><?php _e('New Destination','wegfinder'); ?></button>
			<?php
		}

	}
	
	
	
	
	
	
	/******************* DATA FUNCTIONS*/
	
	
	// DB: Delete 
	private function db_delete($id) {
		try {
			global $wpdb;
			return $wpdb->delete( "{$wpdb->prefix}wegfinder", array( 'id' => $id ));
		} catch(Exception $e) {
			return false;
		}		
	}
	
	// DB: Add
	private function db_add($name=null, $locname, $locid, $arrival=null) {
		if ($locname && $locid) {
			if(!$name) {
				$name = $locname;
			}
			
			try {
				global $wpdb;
				if ($arrival) {
					return $wpdb->insert( "{$wpdb->prefix}wegfinder", array( 'name' => $name, 'locname' => $locname,'locid' => $locid,'arrival' => $arrival));
				} else {
					return $wpdb->insert( "{$wpdb->prefix}wegfinder", array( 'name' => $name, 'locname' => $locname,'locid' => $locid));
				}
					
			} catch(Exception $e) {
				return false;
			}
		} else {
			return false;		
		}
	}
	
	// DB: Get Entry by ID
	private function db_entryById($id, $returnDefaultIfEmpty=false) {
		try {
			global $wpdb;
			$result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wegfinder WHERE `id`={$id};");
			if (!$result) {
				if ($returnDefaultIfEmpty) {
					return $this->dataDefault();				
				} else {
					return null;
				}
			} else {
				return $result[0];
			}			
		} catch(Exception $e) {
			return false;
		}	
	}
	
	
	// DB: Modify
	private function db_modify($id, $name=null, $locname, $locid, $arrival=null) {
		if ($id && $locname && $locid) {
			if(!$name) {
				$name = $locname;
			}
			
			try {
				global $wpdb;
				if ($arrival) {
					return $wpdb->update( "{$wpdb->prefix}wegfinder", array( 'name' => $name, 'locname' => $locname,'locid' => $locid,'arrival' => $arrival), array( 'id' => $id ));
				} else {
					return $wpdb->update( "{$wpdb->prefix}wegfinder", array( 'name' => $name, 'locname' => $locname,'locid' => $locid), array( 'id' => $id ));
				}
					
			} catch(Exception $e) {
				return false;
			}
		} else {
			return false;		
		}
	}
	
	
	
	
	
	
	private function dataDefault() {
		$data = new stdClass();
		$data->id = "";
		$data->name = "";
		$data->locname = "";
		$data->locid = "";
		$data->arrival = "";
		return $data;
	}
	
	private function dataValueOrDefault($value=null) {
			if ($value) {
				return $value;
			} else {
				return $this->dataDefault();
			}
	}

	
	
	
	
	

}
