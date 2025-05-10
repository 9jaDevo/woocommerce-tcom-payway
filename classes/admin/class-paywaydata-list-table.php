<?php

if (! class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class PayWayData_List_Table extends WP_List_Table
{

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items()
	{
		$columns      = $this->get_columns();
		$hidden       = $this->get_hidden_columns();
		$sortable     = $this->get_sortable_columns();
		$all_data     = $this->table_data();
		$processed    = $this->sort_data($all_data);
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = count($processed);

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
		]);

		$page_data = array_slice(
			$processed,
			($current_page - 1) * $per_page,
			$per_page
		);

		$this->_column_headers = [$columns, $hidden, $sortable];
		$this->items           = $page_data;
	}
	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return Array
	 */
	public function get_columns()
	{
		return [
			'id'                 => 'ID',
			'transaction_id'     => 'Transaction ID',
			'response_code'      => 'Response Code',
			'response_code_desc' => 'Response Description',
			'amount'             => 'Amount',
			'or_date'            => 'Date',
			'status'             => 'Status',
		];
	}

	/**
	 * Define which columns are hidden
	 *
	 * @return Array
	 */
	public function get_hidden_columns()
	{
		return [];
	}

	/**
	 * Define the sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns()
	{
		return [
			'transaction_id' => ['transaction_id', false],
			'or_date'        => ['or_date', true],
		];
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data()
	{
		global $wpdb;

		$table     = esc_sql($wpdb->prefix . 'tpayway_ipg');
		$cache_key = 'tpayway_ipg_all';

		$rows = wp_cache_get($cache_key, 'payway');
		if (false === $rows) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
			$sql  = $wpdb->prepare(
				'SELECT * FROM ' . $table . ' WHERE %d = %d',
				1,
				1
			);
			$rows = $wpdb->get_results($sql, ARRAY_A);
			wp_cache_set($cache_key, $rows, 'payway', HOUR_IN_SECONDS);
		}

		return $rows;
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
		return isset($item[$column_name])
			? esc_html($item[$column_name])
			: '';
	}
	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data(array $data)
	{
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$orderby = 'transaction_id';
		if (isset($_GET['orderby'])) {
			$orderby = sanitize_key(wp_unslash($_GET['orderby']));
		}

		$order = 'desc';
		if (isset($_GET['order'])) {
			$ord = strtoupper(wp_unslash($_GET['order']));
			if (in_array($ord, ['ASC', 'DESC'], true)) {
				$order = strtolower($ord);
			}
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		usort($data, function ($a, $b) use ($orderby, $order) {
			$result = strnatcmp($a[$orderby], $b[$orderby]);
			return ('asc' === $order) ? $result : -$result;
		});

		return $data;
	}
}
