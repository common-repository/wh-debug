<?php
//Our class extends the WP_List_Table class, so we need to make sure that it's there
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * A WP List Table for displaying the options.
 * use http://wpengineer.com/2426/wp_list_table-a-step-by-step-guide/
 */
class WH_Debug_Options_Table extends WP_List_Table {

	/**
	 * Constructor, we override the parent to pass our own arguments
	 * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	 */
	 function __construct() {
		 parent::__construct( array(
		'singular'=> 'wp_list_text_link', //Singular label
		'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
		'ajax'	=> false //We won't support Ajax for this table
		) );
	 }

	/**
	 * Add extra markup in the toolbars before or after the list
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	function extra_tablenav( $which ) {
		if ( $which == "top" ) {
			$this->search_box('Search', 'wh-debug-options');
		}
		if ( $which == "bottom" ) {
		}
	}

	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
	function get_columns() {
		return $columns= array(
		    'cb'                 => '<input type="checkbox" />',
			'option_id'          =>__('ID'),
			'option_name'        =>__('Name'),
			'option_value'       =>__('Value'),
			'autoload'           =>__('Autoload')
		);
	}
	/**
	 * Decide which columns to activate the sorting functionality on
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
		return $sortable = array(
			'option_id'      => array('option_id', false),
			'option_name'    => array('option_name', false),
			'option_value'   => array('option_value', false)
		);
	}

	/**
	 * Handle any bulk actions using current_action.
	 */
	function handle_actions() {
		global $wpdb;
		switch ($this->current_action()) {
			case 'delete':
				if (!empty($_POST['option'])) {
					//delete the option(s)
					$options = $_POST['option'];
					foreach ($options as $option_id) {
						$option_id = intval($option_id);
						if (!empty($option_id)) {
							$query = "delete from $wpdb->options where option_id = %d";
							$wpdb->query($wpdb->prepare($query, $option_id));
						}
					}
				}
			break;
			case 'delete_all':
				if ( empty( $_POST['option'] ) ) { //nothing checked
					$query = "delete from $wpdb->options where option_name like '" . WH_DEBUG_PREFIX . "%'";
					$wpdb->query( $query );
				}
			break;			
		}
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items() {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();


		/* -- Handle any Actions */
		$this->handle_actions();

		/* -- Preparing your query -- */
        $query = "SELECT * FROM $wpdb->options where option_name like %s";
        $search = WH_DEBUG_PREFIX;
        if (!empty($_POST['s'])) {
        	$search = $_POST['s'];
        }

		/* -- Ordering parameters -- */
	    //Parameters that are going to be used to order the result
	    $orderby = !empty($_GET["orderby"]) ? $wpdb->prepare( "%s", $_GET["orderby"] ) : 'option_id';
	    $order = !empty($_GET["order"]) ? $wpdb->prepare(  "%s", $_GET["order"] ) : 'ASC';
	    if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

		/* -- Pagination parameters -- */
        //Number of elements in your table?        
        $totalitems = $wpdb->query($wpdb->prepare($query, $search . '%')); //return the total number of affected rows
        //How many to display per page?
        $perpage = 20;
        //Which page is this?
        $paged = !empty($_GET["paged"]) ? $wpdb->prepare( "%d", $_GET["paged"] ) : '';
        //Page Number
        if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
        //How many pages do we have in total?
        $totalpages = ceil($totalitems/$perpage);
        //adjust the query to take pagination into account
	    if(!empty($paged) && !empty($perpage)){
		    $offset=($paged-1)*$perpage;
    		$query.=' LIMIT '.(int)$offset.','.(int)$perpage;
	    }

		/* -- Register the pagination -- */
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page" => $perpage,
		) );
		//The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		/* -- Fetch the items -- */
		$this->items = $wpdb->get_results($wpdb->prepare($query, $search . '%'));
	}

	/**
	 * How to display each column
	 */
	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
	    case 'option_id':
	    case 'option_name':
	    case 'autoload':
	        return $item->$column_name;
	    case 'option_value':
	        $val = maybe_unserialize( $item->$column_name );
	        if ( is_array( $val ) ) {
	        	return var_export( $val );
	        }
	        return $val;
	    default:
	        return '';
	  }
	}

	/**
	 * Define some bulk actions
	 */
	function get_bulk_actions() {
		$actions = array(
	    	'delete'    => 'Delete',
	    	'delete_all' => 'Delete All'
	  	);
	  	return $actions;
	}

	/**
	 * The checkbox column
	 */
	function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="option[]" value="%s" />', $item->option_id
        );    
    }
}	


