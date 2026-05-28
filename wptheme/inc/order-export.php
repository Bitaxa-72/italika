<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_order_export_root_path')) {
	function italika_order_export_root_path($path = '') {
		$root = dirname(get_template_directory());

		return $path !== '' ? $root . '/' . ltrim($path, '/\\') : $root;
	}
}

if (!function_exists('italika_order_export_bootstrap')) {
	function italika_order_export_bootstrap() {
		static $ready = null;

		if ($ready !== null) {
			return $ready;
		}

		$autoload_candidates = [
			get_template_directory() . '/vendor/autoload.php',
			italika_order_export_root_path('vendor/autoload.php'),
		];
		$autoload = '';

		foreach ($autoload_candidates as $candidate) {
			if (file_exists($candidate)) {
				$autoload = $candidate;
				break;
			}
		}

		if ($autoload === '') {
			$ready = false;
			return $ready;
		}

		require_once $autoload;

		$ready = class_exists('\PhpOffice\PhpSpreadsheet\IOFactory') && class_exists('\PhpOffice\PhpSpreadsheet\Writer\Xls');

		return $ready;
	}
}

if (!function_exists('italika_order_export_template_path')) {
	function italika_order_export_template_path() {
		$candidates = [
			get_template_directory() . '/assets/order-export/order_80737.xls',
			get_template_directory() . '/assets/order-export/order_80745.xls',
			italika_order_export_root_path('order_80737.xls'),
			italika_order_export_root_path('order_80745.xls'),
		];

		foreach ($candidates as $candidate) {
			if (file_exists($candidate)) {
				return $candidate;
			}
		}

		return '';
	}
}

if (!function_exists('italika_order_export_is_admin_email')) {
	function italika_order_export_is_admin_email($email) {
		if (!$email instanceof WC_Email) {
			return false;
		}

		if (method_exists($email, 'is_customer_email')) {
			return !$email->is_customer_email();
		}

		return strpos((string) $email->id, 'customer_') !== 0;
	}
}

if (!function_exists('italika_order_export_resolve_email_order')) {
	function italika_order_export_resolve_email_order($order, $email = null) {
		if ($order instanceof WC_Order) {
			return $order;
		}

		if ($email instanceof WC_Email && $email->object instanceof WC_Order) {
			return $email->object;
		}

		return $order ? wc_get_order($order) : null;
	}
}

if (!function_exists('italika_order_export_is_target_email')) {
	function italika_order_export_is_target_email($email_id, $order, $email = null) {
		$order = italika_order_export_resolve_email_order($order, $email);

		if (!$order) {
			return false;
		}

		if ($email_id === 'new_order') {
			return true;
		}

		return italika_order_export_is_admin_email($email);
	}
}

if (!function_exists('italika_order_export_find_layout')) {
	function italika_order_export_find_layout($sheet) {
		$highest_row = (int) $sheet->getHighestRow();
		$item_rows = [];
		$total_row = 0;

		for ($row = 2; $row <= $highest_row; $row++) {
			$name = trim((string) $sheet->getCell('D' . $row)->getValue());
			$total_marker = trim((string) $sheet->getCell('G' . $row)->getValue());

			if ($name !== '') {
				$item_rows[] = $row;
			}

			if ($total_row === 0 && stripos($total_marker, 'Сумма:') === 0) {
				$total_row = $row;
			}
		}

		if (empty($item_rows) || $total_row <= 0) {
			return [];
		}

		return [
			'item_start' => (int) reset($item_rows),
			'item_end' => (int) end($item_rows),
			'item_capacity' => count($item_rows),
			'total_row' => $total_row,
			'spacer_row' => $total_row - 1,
		];
	}
}

if (!function_exists('italika_order_export_number')) {
	function italika_order_export_number($value) {
		if (is_numeric($value)) {
			$number = (float) $value;

			return floor($number) == $number ? (int) $number : $number;
		}

		return 0;
	}
}

if (!function_exists('italika_order_export_status')) {
	function italika_order_export_status($product) {
		if ($product && function_exists('italika_ecomcard_is_available') && italika_ecomcard_is_available($product)) {
			return 'товар в наличии';
		}

		return 'под заказ';
	}
}

if (!function_exists('italika_order_export_resolve_code')) {
	function italika_order_export_resolve_code($item, $product) {
		$candidate_keys = [
			'code',
			'_code',
			'item_code',
			'_item_code',
			'product_code',
			'_product_code',
			'1c_code',
			'_1c_code',
			'italika_code',
			'_italika_code',
		];

		foreach ($candidate_keys as $key) {
			$value = $item instanceof WC_Order_Item_Product ? $item->get_meta($key, true) : '';

			if ($value === '' && $product instanceof WC_Product) {
				$value = $product->get_meta($key, true);
			}

			if ($value !== '' && is_numeric($value)) {
				return italika_order_export_number($value);
			}
		}

		return 0;
	}
}

if (!function_exists('italika_order_export_resolve_article')) {
	function italika_order_export_resolve_article($item, $product) {
		$candidate_keys = [
			'article',
			'_article',
			'artikul',
			'_artikul',
			'vendor_code',
			'_vendor_code',
			'1c_article',
			'_1c_article',
		];

		foreach ($candidate_keys as $key) {
			$value = $item instanceof WC_Order_Item_Product ? $item->get_meta($key, true) : '';

			if ($value === '' && $product instanceof WC_Product) {
				$value = $product->get_meta($key, true);
			}

			if ($value !== '' && is_numeric($value)) {
				return italika_order_export_number($value);
			}
		}

		if ($product instanceof WC_Product) {
			$sku = $product->get_sku();

			if ($sku !== '' && is_numeric($sku)) {
				return italika_order_export_number($sku);
			}
		}

		return 0;
	}
}

if (!function_exists('italika_order_export_get_customer_label')) {
	function italika_order_export_get_customer_label(WC_Order $order) {
		$name = trim($order->get_formatted_billing_full_name());
		$customer_type = function_exists('italika_wc_get_order_customer_type') ? italika_wc_get_order_customer_type($order) : 'individual';
		$customer_type_label = function_exists('italika_wc_get_customer_type_label') ? italika_wc_get_customer_type_label($customer_type) : 'Физ. лицо';

		if ($name === '') {
			$name = trim((string) $order->get_billing_first_name());
		}

		if ($name === '') {
			$name = 'Клиент';
		}

		return $name . ' / ' . $customer_type_label;
	}
}

if (!function_exists('italika_order_export_money_label')) {
	function italika_order_export_money_label($amount) {
		$amount = round((float) $amount, 2);
		$formatted = number_format($amount, 2, '.', '');
		$formatted = rtrim(rtrim($formatted, '0'), '.');

		return $formatted . ' руб.';
	}
}

if (!function_exists('italika_order_export_get_items')) {
	function italika_order_export_get_items(WC_Order $order) {
		$rows = [];

		foreach ($order->get_items() as $item) {
			if (!$item instanceof WC_Order_Item_Product) {
				continue;
			}

			$product = $item->get_product();
			$quantity = (float) $item->get_quantity();
			$unit_price = $quantity > 0 ? ((float) $order->get_item_subtotal($item, false, false)) / $quantity : 0;

			$rows[] = [
				'name' => $item->get_name(),
				'code' => italika_order_export_resolve_code($item, $product),
				'article' => italika_order_export_resolve_article($item, $product),
				'price' => italika_order_export_number($unit_price),
				'quantity' => italika_order_export_number($quantity),
				'status' => italika_order_export_status($product),
			];
		}

		return $rows;
	}
}

if (!function_exists('italika_order_export_copy_row_style')) {
	function italika_order_export_copy_row_style($sheet, $source_row, $target_row) {
		foreach (range('A', 'I') as $column) {
			$sheet->duplicateStyle($sheet->getStyle($column . $source_row), $column . $target_row);
		}

		$sheet->getRowDimension($target_row)->setRowHeight($sheet->getRowDimension($source_row)->getRowHeight());
	}
}

if (!function_exists('italika_order_export_generate')) {
	function italika_order_export_generate($order) {
		if (!italika_order_export_bootstrap()) {
			return '';
		}

		if (!($order instanceof WC_Order)) {
			$order = wc_get_order($order);
		}

		if (!$order) {
			return '';
		}

		$template_path = italika_order_export_template_path();

		if ($template_path === '') {
			return '';
		}

		$items = italika_order_export_get_items($order);

		if (empty($items)) {
			return '';
		}

		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($template_path);
		$sheet = $spreadsheet->getActiveSheet();
		$layout = italika_order_export_find_layout($sheet);

		if (empty($layout)) {
			return '';
		}

		$item_count = count($items);
		$item_start = (int) $layout['item_start'];
		$item_capacity = (int) $layout['item_capacity'];
		$template_last_item_row = (int) $layout['item_end'];
		$insert_before_row = (int) $layout['spacer_row'];

		if ($item_count > $item_capacity) {
			$extra_rows = $item_count - $item_capacity;
			$sheet->insertNewRowBefore($insert_before_row, $extra_rows);

			for ($row = $insert_before_row; $row < ($insert_before_row + $extra_rows); $row++) {
				italika_order_export_copy_row_style($sheet, $template_last_item_row, $row);
			}
		} elseif ($item_count < $item_capacity) {
			$rows_to_remove = $item_capacity - $item_count;
			$sheet->removeRow($item_start + $item_count, $rows_to_remove);
		}

		$total_row = $item_start + $item_count + 1;
		$spacer_row = $total_row - 1;

		$sheet->setTitle('Заказ N ' . $order->get_order_number());

		for ($index = 0; $index < $item_count; $index++) {
			$row = $item_start + $index;
			$item = $items[$index];

			$sheet->setCellValueExplicit('A' . $row, $index === 0 ? (string) $order->get_order_number() : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('B' . $row, $index === 0 ? $order->get_date_created()->date_i18n('d.m.Y H:i') : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('C' . $row, $index === 0 ? italika_order_export_get_customer_label($order) : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('D' . $row, (string) $item['name'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValue('E' . $row, $item['code']);
			$sheet->setCellValue('F' . $row, $item['article']);
			$sheet->setCellValue('G' . $row, $item['price']);
			$sheet->setCellValue('H' . $row, $item['quantity']);
			$sheet->setCellValueExplicit('I' . $row, (string) $item['status'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		}

		foreach (range('A', 'I') as $column) {
			$sheet->setCellValueExplicit($column . $spacer_row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		}

		foreach (range('A', 'F') as $column) {
			$sheet->setCellValueExplicit($column . $total_row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		}

		$sheet->setCellValueExplicit('G' . $total_row, 'Сумма: ' . italika_order_export_money_label($order->get_total()), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->setCellValueExplicit('H' . $total_row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		$sheet->setCellValueExplicit('I' . $total_row, '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

		$uploads = wp_upload_dir();
		$directory = '';

		if (empty($uploads['error']) && !empty($uploads['basedir'])) {
			$directory = trailingslashit($uploads['basedir']) . 'italika-order-exports';

			if (!wp_mkdir_p($directory)) {
				$directory = '';
			}
		}

		if ($directory === '') {
			$directory = trailingslashit(get_temp_dir()) . 'italika-order-exports';

			if (!wp_mkdir_p($directory)) {
				return '';
			}
		}

		$file_path = trailingslashit($directory) . 'order_' . $order->get_order_number() . '.xls';
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
		$writer->save($file_path);

		return $file_path;
	}
}

add_filter('woocommerce_email_attachments', function ($attachments, $email_id, $order, $email = null) {
	if (!italika_order_export_is_target_email($email_id, $order, $email)) {
		return $attachments;
	}

	$order = italika_order_export_resolve_email_order($order, $email);
	$file_path = italika_order_export_generate($order);

	if ($file_path !== '') {
		$attachments[] = $file_path;
	}

	return array_values(array_unique($attachments));
}, 20, 4);
