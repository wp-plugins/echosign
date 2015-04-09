<?php
/**
 * new table class that will extend the WP_List_Table
 */
class EchoSign_Templates_Table extends WP_List_Table
{
	public $limit = 10;

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items()
	{
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();
		usort( $data, array( &$this, 'sort_data' ) );

		$perPage = $this->limit;
        	$currentPage = $this->get_pagenum();
        	$totalItems = count($data);

		$this->set_pagination_args( array(
            		'total_items' => $totalItems,
            		'per_page'    => $perPage
        	));

		$data = array_slice($data, (($currentPage-1) * $perPage), $perPage);

		$this->_column_headers = array($columns, $hidden, $sortable);
		$this->items = $data;
	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns()
	{
		$columns = array(
				'id'          		=> 'ID',
				'user_name'       	=> 'User Name',
				'template_name' 	=> 'Template Name',
				'document_name'		=> 'Document Name',
				'status'	 	=> 'Document Status',
				'uploaded_on'		=> 'Uploaded on',
				'actions'		=> 'Actions',
				);

		return $columns;
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data( $a, $b )
	{
		// Set defaults
		$orderby = 'user_name';
		$order = 'asc';

		// If orderby is set, use this as the sort column
		if(!empty($_GET['orderby']))
		{
			$orderby = $_GET['orderby'];
		}

		// If order is set use this as the order
		if(!empty($_GET['order']))
		{
			$order = $_GET['order'];
		}

		$result = strnatcmp( $a[$orderby], $b[$orderby] );

		if($order === 'asc')
		{
			return $result;
		}

		return -$result;
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns()
	{
		return array('user_name' => array('user_name', false));
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns()
	{
		return array();
	}


	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data()
	{
		global $echosign;
		$data = array();
		$page = isset($_REQUEST['page_count']) ? $_REQUEST['page_count'] : 0;
		$users_list = $this->getUsersList($this->limit, $page);
		foreach($users_list as $single_user_list)	{
			$user_info = array(); $total_test = $user_name = null;
			$user_info = get_userdata($single_user_list->user_id);
			$user_name = $user_info->user_login;
/*			$template_status = "<label style='color:red;'>Deleted</label>";
			if($single_user_list->status == 0)
				$template_status = "<label style='color:green;'>Available</label>";
			if($single_user_list->status == 1)
                                $template_status = "<label style='color:red;'>Trashed</label>"; */
//			$document_status = $echosign->echosign_api->getDocumentInfo($single_user_list->document_id);
			$data[] = array(
					'id' => $single_user_list->id,
					'user_name' => $user_name,
					'template_name' => $single_user_list->template_name,
					'document_name' => $single_user_list->document_name,
//					'status' => $template_status,
					'status' => $single_user_list->status,
					'uploaded_on' => $single_user_list->uploaded_on,
					'actions' => $single_user_list->id,
				); 
		} 
		return $data;
	}

        /**
         * return list of tests
         * @parma integer $user_id
         * @return array $getTests;
         */
        public function getListOfTest($id) {
                global $wpdb, $traitify;
                $user_tests = $wpdb->get_results("update table {$wpdb->prefix}echosign_available_templates set status = 1 where id = '$id'");
                return $user_tests;
        }

	/**
         * return assessment id
         * @param string $type
         * @param integer $user_id
         * @return string $assessmentId
         */
        public function getUsersList($limit = 10, $page = 0)        {
                global $wpdb;
	        $getUsersList = $wpdb->get_results("select * from {$wpdb->prefix}echosign_available_templates");
                return $getUsersList;
        }

	// Used to display the value of the id column
	public function column_id($item)
	{
		return $item['id'];
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  Array $item        Data
	 * @param  String $column_name - Current column name
	 *
	 * @return Mixed
	 */
	public function column_default($item, $column_name)
	{
		switch($column_name)	{
			case 'id':
				return $item[$column_name];
			case 'user_name':
				return $item[$column_name];
			case 'template_name':
				return $item[$column_name];
			case 'document_name':
				return $item[$column_name];
			case 'status':
				$template_status = "<label style='color:red;'>Deleted</label>";
				if($item[$column_name] == 0)
					$template_status = "<label style='color:green;'>Available</label>";
				if($item[$column_name] == 1)
					$template_status = "<label style='color:red;'>Trashed</label>";

				return $template_status;
			case 'uploaded_on':
				return $item[$column_name];
                        case 'actions':
				$id = $item['id'];
				$html = '<span class="trash">';
				if($item['status'] != 1) {
					$assign_url = admin_url() . 'admin.php?page=wp-echosign-templates&templateid=' .$id. '&doaction=trash';
#					$html .= '<a class="submitdelete" title="Move this item to the Trash" onclick="deletetemporarly(' . $id . ');">Trash</a> | ';
					$html .= '<a class="submitdelete" title="Move this item to the Trash" href="' .$assign_url. '">Trash</a> | ';
				} else {
					$assign_url = admin_url() . 'admin.php?page=wp-echosign-templates&templateid=' .$id. '&doaction=restore';
#					$html .= '<a class="submitdelete" title="Move this item to the Trash" onclick="restoretemplate(' . $id . ');">Restore</a> | ';
					$html .= '<a class="submitdelete" title="Move this item to the Trash" href="' .$assign_url. '">Restore</a> | ';
				}
				$assign_url = admin_url() . 'admin.php?page=wp-echosign-templates&templateid=' .$id. '&doaction=delete';
#				$html .= '<a class="submitdelete" title="Delete this item as permanently" onclick="deletepermanently(' . $id . ');">Delete Permanently</a></span>';
				$html .= '<a class="submitdelete" title="Delete this item as permanently" href="' .$assign_url. '">Delete Permanently</a></span>';
				return $html;
			default:
				return print_r($item, true);

		}
	}
}
