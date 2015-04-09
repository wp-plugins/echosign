<?php
/**
 * new table class that will extend the WP_List_Table
 */
class EchoSign_List_Table extends WP_List_Table
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
				'document_id'		=> 'Document Id',
				'document_status' 	=> 'Document Status',
				'created_at'		=> 'Sent at',
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

			$document_status = $echosign->echosign_api->getDocumentInfo($single_user_list->document_id);
			$data[] = array(
					'id' => $single_user_list->id,
					'user_name' => $user_name,
					'template_name' => $single_user_list->template_name,
					'document_id' => $single_user_list->document_id,
					'document_status' => $document_status->documentInfo->status,
					'created_at' => $single_user_list->created_at,
				); 
		} 
		return $data;
	}

	/**
         * return assessment id
         * @param string $type
         * @param integer $user_id
         * @return string $assessmentId
         */
        public function getUsersList($limit = 10, $page = 0)        {
                global $wpdb;
#	        $getUsersList = $wpdb->get_results("select * from {$wpdb->prefix}echosign group by user_id");
		$getUsersList = $wpdb->get_results("select * from {$wpdb->prefix}echosign");
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
			case 'document_id':
				return $item[$column_name];
			case 'document_status':
				return $item[$column_name];
			case 'created_at':
				return $item[$column_name];
			default:
				return print_r($item, true);

		}
	}
}
