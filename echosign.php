<?php
/**
 * Plugin Name: EchoSign
 * Plugin URI: http://www.smackcoders.com
 * Description: EchoSign Plugin used to send PDF documents to Users
 * Version: 1.0
 * Author: Smackcoders
 * Author URI: http://www.smackcoders.com
 * License: GPL2
 */

// Exit if accessed directly
if(!defined('ABSPATH'))
        exit;

define('WP_ECHOSIGN_NAME', 'EchoSign');
define('WP_ECHOSIGN_VERSION', '1.0');
define('WP_ECHOSIGN_DIR', plugin_dir_path(__FILE__));

global $echosign;
class wp_echosign
{
        private static $instance;

	public $echosign_api;

#	public $api_key = 'XJ82YEPX66M3876';
#	public $api_key = 'C723JJ2J4A5B5I';
	public $api_key = '';

        public static $option_name = 'wp_options_echosign_';

        /**
         * create new instance of echosign
         * @return object
         */
        public static function instance()       {
                if(!isset(self::$instance))     {
                        self::$instance = new wp_echosign;
			require_once(WP_ECHOSIGN_DIR . 'libs/echosign/Autoloader.php');
			require_once(WP_ECHOSIGN_DIR . 'inc.php');
			$loader = new SplClassLoader('EchoSign', WP_ECHOSIGN_DIR . 'libs/echosign/lib');
			$loader->register();

			$soap_client = new SoapClient(EchoSign\API::getWSDL());
			self::$instance->api_key = get_option('echosign_apikey');
			self::$instance->echosign_api = new EchoSign\API($soap_client, self::$instance->api_key);
                        add_action('admin_menu', array('wp_echosign', 'wp_echosign_menu'));
			wp_register_script('smack-echosign-js', plugins_url('js/echosign.js', __FILE__));
			wp_enqueue_script('smack-echosign-js');
			add_action('wp_ajax_deletetemplateastemporarly', array('wp_echosign', 'deletetemplateastemporarly'));
			add_action('wp_ajax_deletetemplateaspermanently', array('wp_echosign', 'deletetemplateaspermanently'));
			add_action('wp_ajax_restoretemplates', array('wp_echosign', 'restoretemplates'));
                }
                return self::$instance;
        }

	public static function echoSignLoader($class)	{
		if(file_exists(WP_ECHOSIGN_DIR . 'libs/echosign/' . str_replace('EchoSign', '', $class) . '.php'))
	        	require_once(WP_ECHOSIGN_DIR . 'libs/echosign/' . str_replace('EchoSign', '', $class) . '.php');

    	}

	public static function registration()   {
                global $wpdb;

                $table_name = $wpdb->prefix . 'echosign';
                $charset_collate = $wpdb->get_charset_collate();

                $sql = "CREATE TABLE `{$table_name}` (
                  `id` int(10) NOT NULL AUTO_INCREMENT,
                  `user_id` int(10) NOT NULL,
		  `recipient_email` varchar(100) NOT NULL,
		  `template_name` varchar(255) NOT NULL,
		  `document_id` varchar(100) NOT NULL,
		  `document_status` varchar(10),
                  `created_at` datetime DEFAULT NULL,
                  PRIMARY KEY (`id`)
                )";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
        }

        public static function wp_echosign_menu() {
                add_menu_page(WP_ECHOSIGN_NAME, WP_ECHOSIGN_NAME, 'manage_options', 'wp-echosign', 'echosign_view');
                add_submenu_page('wp-echosign', 'Echosign Templates', 'Templates', 'manage_options', 'wp-echosign-templates', array('EchoSign', 'echosign_templates'));
        }

	/**
	 * send echosign document to given email
	 * @param string $recipient_email
	 * @param string $template_name
	 * @param string $document_name
	 */
	public function sendDocument($recipient_email, $template_name, $document_name)	{
		global $echosign, $wpdb;
		$user_id = get_current_user_id();
		$recipients = new EchoSign\Info\RecipientInfo;
		$recipients->addRecipient($recipient_email);
		$upload_dir = wp_upload_dir();
		$template_dir = $upload_dir ['basedir'] . '/echosign-templates';
#		$fileInfo = EchoSign\Info\FileInfo::createFromFile(WP_ECHOSIGN_DIR . 'templates/' . $template_name . '.pdf');
		$fileInfo = EchoSign\Info\FileInfo::createFromFile($template_dir . '/' . $template_name . '.pdf');
			
		$document = new EchoSign\Info\DocumentCreationInfo($document_name, $fileInfo);
		$document->setRecipients($recipients);
			//->setMergeFields(new EchoSign\Info\MergeFieldInfo($merge_fields));

		try	{
			$result = $this->echosign_api->sendDocument($document);
			$document_id = $result->documentKeys->DocumentKey->documentKey;
			$document_status = 'created';
			$created_at = date('Y-m-d H:i:s');
			$wpdb->query("insert into {$wpdb->prefix}echosign (user_id, recipient_email, template_name, document_id, document_status, created_at) values ('{$user_id}', '{$recipient_email}', '{$template_name}', '{$document_id}', '{$document_status}', '{$created_at}')");
		}
		catch(Exception $e)	{
			print '<h3> An exception occurred: </h3>';
			var_dump($e);
		}
	}

	public function deletetemplateastemporarly() {
		global $wpdb;
		$id = sanitize_text_field($_POST['templateid']);
		$wpdb->query("update {$wpdb->prefix}echosign_available_templates set status = 1 where id = $id");
		die();
	}

        public function restoretemplates() {
                global $wpdb;
		$id = sanitize_text_field($_POST['templateid']);
                $wpdb->query("update {$wpdb->prefix}echosign_available_templates set status = 0 where id = $id");
		die();
        }

        public function deletetemplateaspermanently() {
                global $wpdb;
		$id = sanitize_text_field($_POST['templateid']);
		$get_document_name = $wpdb->get_results("select template_name from {$wpdb->prefix}echosign_available_templates where id = $id");
		$document_name = $get_document_name[0]->template_name;
		$upload_dir = wp_upload_dir();
		$template_dir = $upload_dir ['basedir'] . '/echosign-templates';
		$document = $template_dir . '/' . $document_name . '.pdf';
		if(file_exists($document))
			unlink($document);

                $wpdb->query("delete from {$wpdb->prefix}echosign_available_templates where id = $id");
		die();
        }
}

function echosign_view() {
	// if we need to show any views. Do it here
#	echo 'No views so far';
	global $wpdb, $echosign;
        require_once(WP_ECHOSIGN_DIR . 'templates/list.php');
        echo '<h3> EchoSign Lists </h3>';
	$sendDocuments = "<form name='send_documents' id='send_documents' method='post' action='#'>";
	$sendDocuments .= "<table>";
	$sendDocuments .= "<tr>";
	$sendDocuments .= "<td><b>Select Document</b></td>";
	$sendDocuments .= "<td><select name='documentid' id='documentid' style='max-width: 175px;' required >";
	$sendDocuments .= "<option value='' style='text-align:center;'>-- None --</option>";
	
	$get_results = $wpdb->get_results("select *from {$wpdb->prefix}echosign_available_templates where status != 1");
#	print_r($get_results); 
	if(!empty($get_results)) {
		foreach($get_results as $key => $val) {
			$sendDocuments .= "<option value='{$val->id}'>{$val->document_name}</option>";
		}
	}

	$sendDocuments .= "</td></select>";
	$sendDocuments .= "<td><b>Recipient e-Mail</b></td>";
	$sendDocuments .= "<td><input type='email' name='recipient_mailid' id='recipient_mailid' value='' required /></td>";
	$sendDocuments .= "<td><input type='submit' name='send_document' id='send_document' value='Send Document' class='button button-primary' /></td>";
	$sendDocuments .= "</tr>";
	$sendDocuments .= "</table>";
	$sendDocuments .= "</form>";
	echo $sendDocuments;
        $list = new EchoSign_List_Table();
        $list->prepare_items();
	echo "<div style='margin-top:-20px; width:98%;'>";
        $list->display();
	echo "</div>";
	if(isset($_POST['documentid']) && isset($_POST['recipient_mailid'])) {
		$recipient_mailid = sanitize_text_field($_POST['recipient_mailid']);
		$documentid = sanitize_text_field($_POST['documentid']);
		$get_document_details = $wpdb->get_results("select template_name, document_name from {$wpdb->prefix}echosign_available_templates where id = $documentid");
		$template_name = $get_document_details[0]->template_name;
		$document_name = $get_document_details[0]->document_name;
		$echosign->sendDocument("$recipient_mailid", "$template_name", "$document_name");
	}
	// sending echosign document to user
#	$echosign->sendDocument('mailmefredrick@gmail.com', 'template_1', 'Test Template Number One - Raj');	
}

function echosign_init()        {
        return wp_echosign::instance();
}

$echosign = echosign_init();
register_activation_hook(__FILE__, array('wp_echosign', 'registration'));
