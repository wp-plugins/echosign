<?php
class EchoSign {

	public function echosign_templates() {
		global $wpdb;
		$get_api_key = get_option('echosign_apikey');
		// API Settings
		$settings = "<div style='magin-top:20px;margin-bottom:30px;'>
		<h3>EcoSign Settings</h3>
		<form name='echosign_settings' method='post' action='#'>
		<label>API Key</label>
		<input type='text' name='api_key' id='api_key' required placeholder='Enter your API key' value='" . $get_api_key . "' />
		<input type='submit' name='save_echosign_settings' id='save_echosign_settings' class='button button-primary' value='Save Settings' />
		</form>
		</div>";
		if(isset($_POST['api_key']) && $_POST['api_key'] != '') {
			update_option('echosign_apikey', sanitize_text_field($_POST['api_key']));
		}
		echo $settings;

		// Add new template
		$upload_dir = wp_upload_dir();
		echo "<input type='button' name='show_upload_template' id='show_upload_template' value='Add New Template' class='button button-primary' onclick='show_upload_template();' />";
		if (!is_dir($upload_dir ['basedir'])) {
			$uploadtemplate = "<div style='font-size:16px;margin-left:20px;margin-top:25px;color:red;'>" . __("Uploads Directory Not Found!") . "</div><br/>";
		} else {
			$uploadtemplate = "<div id='upload_echosign_template' style='magin-top:20px; width:98%; display:none;'>";
			$uploadtemplate .= "<h3>Upload your custom templates:</h3>";
			$uploadtemplate .= "<form name='echosign_uploadtemplate' method='post' action='' enctype='multipart/form-data'>";
			$uploadtemplate .= "<table class='table'>";
			$uploadtemplate .= "<tr><td>Upload Template</td><td><input type='file' name='attachment' id='attachment' required style='width: 100%;'/></td></tr>";
			$uploadtemplate .= "<tr><td>Template Name</td><td><input type='text' name='templatename' id='templatename' pattern='[a-zA-Z]+' required style='width: 100%;'/></td></tr>";
			$uploadtemplate .= "<tr><td>Document Name</td><td><input type='text' name='documentname' id='documentname' required style='width: 100%;'/></td></tr>";
			$uploadtemplate .= "<tr><td colspan='2'><input type='button' name='hide_upload_template' id='hide_upload_template' value='Cancel' class='button button-warning' style='float:right; margin:5px;' onclick='hide_new_template();' /> <input type='submit' name='uploadnewtemplate' id='uploadnewtemplate' value='Upload' style='float:right; margin:5px;' class='button button-primary'/></td></tr>";
			$uploadtemplate .= "</table>";
			$uploadtemplate .= "</form>";
			$uploadtemplate .= "</div>";
		}
		if(isset($_POST['uploadnewtemplate']) && isset($_FILES) && !empty($_FILES)) {
#print_r('<pre>'); print_r($_FILES); print_r($_POST); print('</pre>');
			$tmp_name = $_FILES["attachment"]["tmp_name"];
			$uploaded_extention = $_FILES["attachment"]["type"];
			$templatename = preg_replace('/\s/', '_', sanitize_text_field($_POST['templatename']));
#			$templatename = $templatename . '.pdf';
			$new_template_name = $templatename . '.pdf';
			$documentname = sanitize_text_field($_POST['documentname']);
			$upload_dir = wp_upload_dir();
			$template_dir = $upload_dir ['basedir'] . '/echosign-templates';
			if (!is_dir($template_dir)) {
				wp_mkdir_p($template_dir);
			}
			$get_results = $wpdb->get_results("select *from {$wpdb->prefix}echosign_available_templates where template_name = '{$templatename}' and document_name= '{$documentname}'");
			if(empty($get_results)) {
				if($uploaded_extention == 'application/pdf') {
					move_uploaded_file($tmp_name, $template_dir . "/$new_template_name");
					$table = $wpdb->prefix . 'echosign_available_templates';
					$time = date('Y-m-d H:i:s');
					$user_id = get_current_user_id();
					$wpdb->insert($table, array('user_id' => $user_id, 'template_name' => $templatename, 'document_name' => $documentname, 'uploaded_on' => $time));
				} else {
					echo "<div style='font-size:16px;margin-left:20px;margin-top:25px;color:red;'>" . __("Templates Directory Not Found!") . "</div><br/>";
				}
			} else {
				echo "<div style='font-size:16px;margin-left:20px;margin-top:25px;color:red;'>" . __("Try with different Template & Document name!") . "</div><br/>";
			}
		}
		echo $uploadtemplate;
#		$assign_url = admin_url() . '/admin.php?page=wp-echosign-templates&msg=2';
#		echo '<script type="text/javascript">window.location.href="' .$assign_url. '";</script>';

		// Do actions based on the requests ( Trash, Restore, Delete )
		if(isset($_REQUEST['templateid']) && isset($_REQUEST['doaction'])) {
			if($_REQUEST['doaction'] == 'trash') {
				$id = $_REQUEST['templateid'];
				$wpdb->query("update {$wpdb->prefix}echosign_available_templates set status = 1 where id = $id");
			} else if($_REQUEST['doaction'] == 'restore') {
				$id = $_REQUEST['templateid'];
				$wpdb->query("update {$wpdb->prefix}echosign_available_templates set status = 0 where id = $id");
			} else if($_REQUEST['doaction'] == 'delete') {
				$id = $_REQUEST['templateid'];
				$get_document_name = $wpdb->get_results("select template_name from {$wpdb->prefix}echosign_available_templates where id = $id");
				$document_name = $get_document_name[0]->template_name;
				$upload_dir = wp_upload_dir();
				$template_dir = $upload_dir ['basedir'] . '/echosign-templates';
				$document = $template_dir . '/' . $document_name . '.pdf';
				if(file_exists($document))
					unlink($document);

				$wpdb->query("delete from {$wpdb->prefix}echosign_available_templates where id = $id");
			}
			$assign_url = admin_url() . 'admin.php?page=wp-echosign-templates';
			wp_redirect( $assign_url );
		}
		// List of all available templates
		global $echosign;
		require_once(WP_ECHOSIGN_DIR . 'templates/template_list.php');
		echo '<h3> EchoSign Templates </h3>';
		$list = new EchoSign_Templates_Table();
		$list->prepare_items();
		echo "<div style='width:98%; margin-top:-20px;'>";
		$list->display();
		echo "</div>";
	}

	public function echosign_settings() {
		$get_api_key = get_option('echosign_apikey');
		$settings = "<div style='magin-top:20px;'>
		<h3>EcoSign Settings</h3>
		<form name='echosign_settings' method='post' action='#'>
		<label>API Key</label>
		<input type='text' name='api_key' id='api_key' required placeholder='Enter your API key' value='" . $get_api_key . "' />
		<input type='submit' name='save_echosign_settings' id='save_echosign_settings' class='button button-primary' value='Save Settings' />
		</form>
		</div>";
		if(isset($_POST['api_key']) && $_POST['api_key'] != '') {
			update_option('echosign_apikey', sanitize_text_field($_POST['api_key']));
		}
		echo $settings;
		$uploadtemplate = "<div style='magin-top:20px;'>";
		$uploadtemplate .= "<h3>Upload your custom templates:</h3>";
		$uploadtemplate .= "<form name='echosign_uploadtemplate method='post' action='#'>";
		$uploadtemplate .= "<table class='table'>";
		$uploadtemplate .= "<tr><td>Upload Template</td><td><input type='file' name='attachment' id='attachment' required style='width: 100%;'/></td></tr>";
		$uploadtemplate .= "<tr><td>Custom Template Name</td><td><input type='text' name='templatename' id='templatename' required style='width: 100%;'/></td></tr>";
		$uploadtemplate .= "<tr><td>Custom Document Name</td><td><input type='text' name='documentname' id='documentname' required style='width: 100%;'/></td></tr>";
		$uploadtemplate .= "<tr><td colspan='2'><input type='submit' name='upload' id='upload' value='Upload' style='float:right;' class='button button-primary'/></td></tr>";
		$uploadtemplate .= "</table>";
		$uploadtemplate .= "</form>";
		$uploadtemplate .= "</div>";
		echo $uploadtemplate;
	}
}
