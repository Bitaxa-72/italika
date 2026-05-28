<?php
defined('ABSPATH') || exit;

if (!function_exists('italika_1c_import_parse_number')) {
	function italika_1c_import_parse_number($value) {
		$value = trim((string) $value);

		if ($value === '') {
			return 0.0;
		}

		$value = str_replace(["\xc2\xa0", ' '], '', $value);
		$value = str_replace(',', '.', $value);

		if (!is_numeric($value)) {
			return null;
		}

		return (float) $value;
	}
}

if (!function_exists('italika_1c_import_parse_price_stock')) {
	function italika_1c_import_parse_price_stock($columns) {
		$pairs = [
			[3, 4],
			[4, 5],
		];

		foreach ($pairs as $pair) {
			$price_index = $pair[0];
			$stock_index = $pair[1];

			if (!array_key_exists($price_index, $columns) || !array_key_exists($stock_index, $columns)) {
				continue;
			}

			$price = italika_1c_import_parse_number($columns[$price_index]);
			$stock = italika_1c_import_parse_number($columns[$stock_index]);

			if ($price !== null && $stock !== null) {
				return [$price, $stock];
			}
		}

		$numbers = [];
		$total = count($columns);

		for ($index = 3; $index < $total; $index++) {
			if (trim((string) ($columns[$index] ?? '')) === '') {
				continue;
			}

			$number = italika_1c_import_parse_number($columns[$index]);

			if ($number !== null) {
				$numbers[] = $number;
			}

			if (count($numbers) >= 2) {
				return [$numbers[0], $numbers[1]];
			}
		}

		return [
			italika_1c_import_parse_number($columns[3] ?? ''),
			italika_1c_import_parse_number($columns[4] ?? ''),
		];
	}
}

if (!function_exists('italika_1c_import_normalize_sku')) {
	function italika_1c_import_normalize_sku($sku) {
		$sku = str_replace("\xEF\xBB\xBF", '', (string) $sku);
		$sku = preg_replace('/^\x{FEFF}/u', '', $sku);
		$sku = preg_replace('/\s+/u', '', (string) $sku);

		return trim((string) $sku);
	}
}

if (!function_exists('italika_1c_import_is_weight_unit')) {
	function italika_1c_import_is_weight_unit($unit) {
		$unit = mb_strtolower(trim((string) $unit));

		return in_array($unit, ['кг', 'л'], true);
	}
}

if (!function_exists('italika_1c_import_decimal_from_match')) {
	function italika_1c_import_decimal_from_match($value) {
		return (float) str_replace(',', '.', (string) $value);
	}
}

if (!function_exists('italika_1c_import_extract_sell_unit_size')) {
	function italika_1c_import_extract_sell_unit_size($name, $unit) {
		$name = mb_strtolower((string) $name);
		$unit = mb_strtolower(trim((string) $unit));
		$sizes = [];

		if ($unit === 'кг') {
			if (preg_match_all('/(\d+(?:[,.]\d+)?)\s*[*xх]\s*(\d+(?:[,.]\d+)?)\s*кг/u', $name, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$sizes[] = italika_1c_import_decimal_from_match($match[1]) * italika_1c_import_decimal_from_match($match[2]);
				}
			}

			if (preg_match_all('/(\d+(?:[,.]\d+)?)\s*кг/u', $name, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$sizes[] = italika_1c_import_decimal_from_match($match[1]);
				}
			}

			if (preg_match_all('/(\d+(?:[,.]\d+)?)\s*(?:г|гр)\b/u', $name, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$sizes[] = italika_1c_import_decimal_from_match($match[1]) / 1000;
				}
			}
		} elseif ($unit === 'л') {
			if (preg_match_all('/(\d+(?:[,.]\d+)?)\s*[*xх]\s*(\d+(?:[,.]\d+)?)\s*л/u', $name, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$sizes[] = italika_1c_import_decimal_from_match($match[1]) * italika_1c_import_decimal_from_match($match[2]);
				}
			}

			if (preg_match_all('/(\d+(?:[,.]\d+)?)\s*л/u', $name, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$sizes[] = italika_1c_import_decimal_from_match($match[1]);
				}
			}

			if (preg_match_all('/(\d+(?:[,.]\d+)?)\s*мл/u', $name, $matches, PREG_SET_ORDER)) {
				foreach ($matches as $match) {
					$sizes[] = italika_1c_import_decimal_from_match($match[1]) / 1000;
				}
			}
		}

		$sizes = array_values(array_filter($sizes, static function ($size) {
			return is_numeric($size) && (float) $size > 0;
		}));

		if (!$sizes) {
			return null;
		}

		sort($sizes, SORT_NUMERIC);

		return (float) $sizes[0];
	}
}

if (!function_exists('italika_1c_import_prepare_row_calculation')) {
	function italika_1c_import_prepare_row_calculation($row) {
		$is_weight_unit = italika_1c_import_is_weight_unit($row['unit']);
		$detected_unit_size = $is_weight_unit ? italika_1c_import_extract_sell_unit_size($row['name'], $row['unit']) : 1.0;
		$unit_size = $detected_unit_size !== null ? $detected_unit_size : 1.0;
		$unit_size = $unit_size > 0 ? $unit_size : 1.0;

		$row['sell_unit_size'] = $unit_size;
		$row['sell_unit_detected'] = $detected_unit_size !== null;
		$row['sell_unit_warning'] = $is_weight_unit && $detected_unit_size === null;
		$row['import_price'] = $row['price'] !== null ? (float) $row['price'] * $unit_size : null;
		$row['import_stock_quantity'] = $row['stock'] !== null ? max(0, (int) floor(max(0, (float) $row['stock']) / $unit_size)) : null;
		$row['is_on_request'] = $row['import_price'] !== null && (float) $row['import_price'] <= 0;

		return $row;
	}
}

if (!function_exists('italika_1c_import_decode_contents')) {
	function italika_1c_import_decode_contents($contents) {
		if (!is_string($contents) || $contents === '') {
			return '';
		}

		if (strncmp($contents, "\xEF\xBB\xBF", 3) === 0) {
			return substr($contents, 3);
		}

		if (function_exists('mb_check_encoding') && mb_check_encoding($contents, 'UTF-8')) {
			return $contents;
		}

		if (function_exists('mb_convert_encoding')) {
			$converted = @mb_convert_encoding($contents, 'UTF-8', 'CP866,Windows-1251,CP1251');

			if (is_string($converted) && $converted !== '') {
				return $converted;
			}
		}

		$converted = @iconv('CP866', 'UTF-8//IGNORE', $contents);

		return is_string($converted) && $converted !== '' ? $converted : $contents;
	}
}

if (!function_exists('italika_1c_import_parse_file')) {
	function italika_1c_import_parse_file($path) {
		$contents = is_readable($path) ? file_get_contents($path) : false;

		if ($contents === false || $contents === '') {
			return new WP_Error('italika_1c_empty_file', 'Файл пустой или не читается.');
		}

		$contents = italika_1c_import_decode_contents($contents);
		$stream = fopen('php://temp', 'r+');

		if (!$stream) {
			return new WP_Error('italika_1c_temp_error', 'Не удалось подготовить файл к чтению.');
		}

		fwrite($stream, $contents);
		rewind($stream);

		$rows = [];
		$line = 0;

		while (($columns = fgetcsv($stream, 0, ';')) !== false) {
			$line++;

			if (!$columns || count(array_filter($columns, 'strlen')) === 0) {
				continue;
			}

			$sku = isset($columns[0]) ? italika_1c_import_normalize_sku((string) $columns[0]) : '';

			if ($line === 1 && !preg_match('/^\d+$/', $sku)) {
				continue;
			}

			if ($sku === '') {
				continue;
			}

			if (count($columns) >= 5) {
				$name = trim((string) $columns[1]);
				$unit = trim((string) $columns[2]);
				[$price, $stock] = italika_1c_import_parse_price_stock($columns);
			} else {
				$name = trim((string) ($columns[1] ?? ''));
				$unit = '';
				$price = italika_1c_import_parse_number($columns[2] ?? '');
				$stock = italika_1c_import_parse_number($columns[3] ?? '');
			}

			$rows[] = italika_1c_import_prepare_row_calculation([
				'line' => $line,
				'sku' => sanitize_text_field($sku),
				'name' => sanitize_text_field($name),
				'unit' => sanitize_text_field($unit),
				'price' => $price,
				'stock' => $stock,
				'product_id' => function_exists('wc_get_product_id_by_sku') ? (int) wc_get_product_id_by_sku($sku) : 0,
			]);
		}

		fclose($stream);

		if (!$rows) {
			return new WP_Error('italika_1c_no_rows', 'Не нашел строк товаров. Нужен CSV с разделителем ;');
		}

		return $rows;
	}
}

if (!function_exists('italika_1c_import_build_summary')) {
	function italika_1c_import_build_summary($rows) {
		$summary = [
			'total' => count($rows),
			'matched' => 0,
			'unmatched' => 0,
			'invalid_price' => 0,
			'invalid_stock' => 0,
			'weight' => 0,
			'unknown_sell_unit' => 0,
			'on_request' => 0,
		];

		foreach ($rows as $row) {
			if (!empty($row['product_id'])) {
				$summary['matched']++;
			} else {
				$summary['unmatched']++;
			}

			if ($row['price'] === null || $row['price'] < 0) {
				$summary['invalid_price']++;
			}

			if ($row['stock'] === null) {
				$summary['invalid_stock']++;
			}

			if (italika_1c_import_is_weight_unit($row['unit'])) {
				$summary['weight']++;
			}

			if (!empty($row['sell_unit_warning'])) {
				$summary['unknown_sell_unit']++;
			}

			if (!empty($row['is_on_request'])) {
				$summary['on_request']++;
			}
		}

		return $summary;
	}
}

if (!function_exists('italika_1c_import_build_mismatch_report')) {
	function italika_1c_import_build_mismatch_report($rows, $limit = 500) {
		$file_skus = [];
		$file_sku_rows = [];
		$file_missing_on_site = [];
		$file_missing_count = 0;
		$limit = max(1, (int) $limit);

		foreach ($rows as $row) {
			$sku = italika_1c_import_normalize_sku($row['sku'] ?? '');

			if ($sku !== '') {
				$file_skus[$sku] = true;
				if (!isset($file_sku_rows[$sku])) {
					$file_sku_rows[$sku] = [];
				}
				$file_sku_rows[$sku][] = $row;
			}

			if (empty($row['product_id'])) {
				$file_missing_count++;

				if (count($file_missing_on_site) < $limit) {
					$file_missing_on_site[] = $row;
				}
			}
		}

		$site_missing_in_file = [];
		$site_without_sku = [];
		$site_sku_items = [];
		$site_missing_count = 0;
		$site_without_sku_count = 0;

		$query = new WP_Query([
			'fields' => 'ids',
			'no_found_rows' => true,
			'orderby' => 'ID',
			'order' => 'ASC',
			'post_type' => ['product', 'product_variation'],
			'post_status' => ['publish', 'private', 'draft', 'pending'],
			'posts_per_page' => -1,
		]);

		foreach ($query->posts as $product_id) {
			$product = wc_get_product((int) $product_id);

			if (!$product) {
				continue;
			}

			$sku = italika_1c_import_normalize_sku($product->get_sku());
			$item = [
				'id' => (int) $product_id,
				'name' => $product->get_name(),
				'sku' => $sku,
				'type' => $product->get_type(),
				'status' => get_post_status((int) $product_id),
				'edit_url' => get_edit_post_link((int) $product_id, ''),
			];

			if ($sku === '') {
				$site_without_sku_count++;

				if (count($site_without_sku) < $limit) {
					$site_without_sku[] = $item;
				}

				continue;
			}

			if (!isset($site_sku_items[$sku])) {
				$site_sku_items[$sku] = [];
			}
			$site_sku_items[$sku][] = $item;

			if (!isset($file_skus[$sku])) {
				$site_missing_count++;

				if (count($site_missing_in_file) < $limit) {
					$site_missing_in_file[] = $item;
				}
			}
		}

		wp_reset_postdata();

		$file_duplicate_skus = italika_1c_import_build_duplicate_report($file_sku_rows, $limit);
		$site_duplicate_skus = italika_1c_import_build_duplicate_report($site_sku_items, $limit);

		return [
			'limit' => $limit,
			'file_missing_on_site_count' => $file_missing_count,
			'file_missing_on_site' => $file_missing_on_site,
			'site_missing_in_file_count' => $site_missing_count,
			'site_missing_in_file' => $site_missing_in_file,
			'site_without_sku_count' => $site_without_sku_count,
			'site_without_sku' => $site_without_sku,
			'file_duplicate_skus_count' => $file_duplicate_skus['total'],
			'file_duplicate_skus' => $file_duplicate_skus['items'],
			'site_duplicate_skus_count' => $site_duplicate_skus['total'],
			'site_duplicate_skus' => $site_duplicate_skus['items'],
		];
	}
}

if (!function_exists('italika_1c_import_build_duplicate_report')) {
	function italika_1c_import_build_duplicate_report($items_by_sku, $limit = 500) {
		$duplicates = [];
		$total = 0;
		$limit = max(1, (int) $limit);

		foreach ($items_by_sku as $sku => $items) {
			if (count($items) < 2) {
				continue;
			}

			$total++;

			if (count($duplicates) >= $limit) {
				continue;
			}

			$duplicates[] = [
				'sku' => (string) $sku,
				'count' => count($items),
				'items' => $items,
			];
		}

		return [
			'total' => $total,
			'items' => $duplicates,
		];
	}
}

if (!function_exists('italika_1c_import_apply_rows')) {
	function italika_1c_import_apply_rows($rows) {
		$result = [
			'updated' => 0,
			'skipped' => 0,
			'unmatched' => 0,
			'errors' => [],
		];

		foreach ($rows as $row) {
			$product_id = !empty($row['product_id']) ? (int) $row['product_id'] : 0;

			if ($product_id <= 0) {
				$result['unmatched']++;
				continue;
			}

			$price = $row['import_price'];
			$stock = $row['stock'];

			if ($price === null || $price < 0 || $stock === null || $row['import_stock_quantity'] === null) {
				$result['skipped']++;
				continue;
			}

			$product = wc_get_product($product_id);

			if (!$product) {
				$result['skipped']++;
				continue;
			}

			$price_value = wc_format_decimal($price, wc_get_price_decimals());
			$stock_value = (float) $stock;
			$is_weight = italika_1c_import_is_weight_unit($row['unit']);
			$sell_unit_size = isset($row['sell_unit_size']) ? (float) $row['sell_unit_size'] : 1.0;
			$is_on_request = (float) $price <= 0;

			$product->set_regular_price($price_value);

			$sale_price = $product->get_sale_price();
			if ($is_on_request || $sale_price === '' || (float) $sale_price <= 0 || (float) $sale_price >= (float) $price_value) {
				$product->set_sale_price('');
				$product->set_price($price_value);
			}

			$quantity = max(0, (int) $row['import_stock_quantity']);
			if ($is_on_request) {
				$product->set_manage_stock(false);
				$product->set_stock_quantity(null);
				$product->set_stock_status('instock');
			} else {
				$product->set_manage_stock(true);
				$product->set_stock_quantity($quantity);
				$product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
			}

			$product->update_meta_data('_italika_1c_name', $row['name']);
			$product->update_meta_data('_italika_1c_unit', $row['unit']);
			$product->update_meta_data('_italika_1c_price_raw', $row['price'] !== null ? wc_format_decimal((float) $row['price'], 2) : '');
			$product->update_meta_data('_italika_1c_sell_unit_size', wc_format_decimal($sell_unit_size, 3));
			$product->update_meta_data('_italika_1c_stock_raw', wc_format_decimal($stock_value, 3));
			$product->update_meta_data('_italika_1c_is_weight', $is_weight ? 'yes' : 'no');
			$product->update_meta_data('_italika_on_request', $is_on_request ? 'yes' : 'no');
			$product->update_meta_data('_italika_1c_imported_at', current_time('mysql'));
			$product->save();

			if (function_exists('wc_delete_product_transients')) {
				wc_delete_product_transients($product_id);
			}

			$result['updated']++;
		}

		return $result;
	}
}

if (!function_exists('italika_1c_import_notice')) {
	function italika_1c_import_notice($message, $type = 'success') {
		printf(
			'<div class="notice notice-%s"><p>%s</p></div>',
			esc_attr($type),
			wp_kses_post($message)
		);
	}
}

if (!function_exists('italika_1c_import_render_product_link')) {
	function italika_1c_import_render_product_link($item) {
		$name = isset($item['name']) && $item['name'] !== '' ? (string) $item['name'] : 'Без названия';
		$id = isset($item['id']) ? (int) $item['id'] : 0;
		$url = isset($item['edit_url']) ? (string) $item['edit_url'] : '';

		if ($url !== '') {
			return '<a href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
		}

		return esc_html($name . ($id > 0 ? ' #' . $id : ''));
	}
}

if (!function_exists('italika_1c_import_render_mismatch_note')) {
	function italika_1c_import_render_mismatch_note($shown, $total) {
		$shown = (int) $shown;
		$total = (int) $total;

		if ($total <= $shown) {
			return;
		}

		printf(
			'<p style="margin:8px 0;color:#646970;">Показаны первые %d из %d.</p>',
			$shown,
			$total
		);
	}
}

if (!function_exists('italika_1c_import_render_report_export_button')) {
	function italika_1c_import_render_report_export_button($token, $report_type, $label) {
		$token = (string) $token;

		if ($token === '') {
			return;
		}
		?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin:0 0 12px;">
			<?php wp_nonce_field('italika_1c_import_export_report', 'italika_1c_import_export_nonce'); ?>
			<input type="hidden" name="action" value="italika_1c_import_export_report">
			<input type="hidden" name="italika_1c_import_token" value="<?php echo esc_attr($token); ?>">
			<input type="hidden" name="italika_1c_import_report_type" value="<?php echo esc_attr($report_type); ?>">
			<button type="submit" class="button"><?php echo esc_html($label); ?></button>
		</form>
		<?php
	}
}

if (!function_exists('italika_1c_import_render_mismatch_report')) {
	function italika_1c_import_render_mismatch_report($report, $token = '') {
		if (!is_array($report)) {
			return;
		}

		$file_missing = $report['file_missing_on_site'] ?? [];
		$site_missing = $report['site_missing_in_file'] ?? [];
		$site_without_sku = $report['site_without_sku'] ?? [];
		$file_missing_count = (int) ($report['file_missing_on_site_count'] ?? 0);
		$site_missing_count = (int) ($report['site_missing_in_file_count'] ?? 0);
		$site_without_sku_count = (int) ($report['site_without_sku_count'] ?? 0);
		$total_mismatch_count = $file_missing_count + $site_missing_count + $site_without_sku_count;
		$file_duplicates = $report['file_duplicate_skus'] ?? [];
		$site_duplicates = $report['site_duplicate_skus'] ?? [];
		$file_duplicates_count = (int) ($report['file_duplicate_skus_count'] ?? 0);
		$site_duplicates_count = (int) ($report['site_duplicate_skus_count'] ?? 0);
		?>
		<div style="max-width:1200px;margin:18px 0;">
			<h2>Отчеты по сверке</h2>
			<?php italika_1c_import_render_report_export_button($token, 'all', 'Выгрузить все отчеты CSV'); ?>

			<details style="margin:0 0 12px;padding:0;background:#fff;border:1px solid #ccd0d4;">
				<summary style="padding:12px 16px;cursor:pointer;font-size:16px;font-weight:600;">
					Есть в файле 1С, но нет на сайте: <?php echo esc_html((string) $file_missing_count); ?>
				</summary>
				<div style="padding:0 16px 16px;">
					<?php italika_1c_import_render_report_export_button($token, 'file_missing_on_site', 'Выгрузить этот отчет CSV'); ?>
					<?php italika_1c_import_render_mismatch_note(count($file_missing), $file_missing_count); ?>
					<?php if ($file_missing_count > 0) : ?>
						<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
							<thead>
								<tr>
									<th>Строка</th>
									<th>Артикул 1С</th>
									<th>Наименование из 1С</th>
									<th>Ед.</th>
									<th>Цена</th>
									<th>Остаток</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($file_missing as $row) : ?>
									<tr>
										<td><?php echo esc_html((string) ($row['line'] ?? '')); ?></td>
										<td><code><?php echo esc_html((string) ($row['sku'] ?? '')); ?></code></td>
										<td><?php echo esc_html((string) ($row['name'] ?? '')); ?></td>
										<td><?php echo esc_html((string) ($row['unit'] ?? '')); ?></td>
										<td><?php echo esc_html(($row['price'] ?? null) === null ? 'ошибка' : wc_format_localized_price($row['price'])); ?></td>
										<td><?php echo esc_html(($row['stock'] ?? null) === null ? 'ошибка' : wc_format_localized_decimal($row['stock'])); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="margin:0;color:#646970;">Таких строк нет.</p>
					<?php endif; ?>
				</div>
			</details>

			<details style="margin:0 0 12px;padding:0;background:#fff;border:1px solid #ccd0d4;">
				<summary style="padding:12px 16px;cursor:pointer;font-size:16px;font-weight:600;">
					Есть на сайте, но нет в файле 1С: <?php echo esc_html((string) $site_missing_count); ?>
				</summary>
				<div style="padding:0 16px 16px;">
					<?php italika_1c_import_render_report_export_button($token, 'site_missing_in_file', 'Выгрузить этот отчет CSV'); ?>
					<?php italika_1c_import_render_mismatch_note(count($site_missing), $site_missing_count); ?>
					<?php if ($site_missing_count > 0) : ?>
						<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
							<thead>
								<tr>
									<th>ID</th>
									<th>Артикул сайта</th>
									<th>Товар на сайте</th>
									<th>Тип</th>
									<th>Статус</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($site_missing as $item) : ?>
									<tr>
										<td><?php echo esc_html((string) ($item['id'] ?? '')); ?></td>
										<td><code><?php echo esc_html((string) ($item['sku'] ?? '')); ?></code></td>
										<td><?php echo wp_kses_post(italika_1c_import_render_product_link($item)); ?></td>
										<td><?php echo esc_html((string) ($item['type'] ?? '')); ?></td>
										<td><?php echo esc_html((string) ($item['status'] ?? '')); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="margin:0;color:#646970;">Таких товаров нет.</p>
					<?php endif; ?>
				</div>
			</details>

			<details style="margin:0 0 12px;padding:0;background:#fff;border:1px solid #ccd0d4;">
				<summary style="padding:12px 16px;cursor:pointer;font-size:16px;font-weight:600;">
					Товары на сайте без артикула: <?php echo esc_html((string) $site_without_sku_count); ?>
				</summary>
				<div style="padding:0 16px 16px;">
					<?php italika_1c_import_render_report_export_button($token, 'site_without_sku', 'Выгрузить этот отчет CSV'); ?>
					<?php italika_1c_import_render_mismatch_note(count($site_without_sku), $site_without_sku_count); ?>
					<?php if ($site_without_sku_count > 0) : ?>
						<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
							<thead>
								<tr>
									<th>ID</th>
									<th>Товар на сайте</th>
									<th>Тип</th>
									<th>Статус</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($site_without_sku as $item) : ?>
									<tr>
										<td><?php echo esc_html((string) ($item['id'] ?? '')); ?></td>
										<td><?php echo wp_kses_post(italika_1c_import_render_product_link($item)); ?></td>
										<td><?php echo esc_html((string) ($item['type'] ?? '')); ?></td>
										<td><?php echo esc_html((string) ($item['status'] ?? '')); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="margin:0;color:#646970;">Таких товаров нет.</p>
					<?php endif; ?>
				</div>
			</details>

			<details style="margin:0;padding:0;background:#fff;border:1px solid #ccd0d4;">
				<summary style="padding:12px 16px;cursor:pointer;font-size:16px;font-weight:600;">
					Проверка дублей: в файле <?php echo esc_html((string) $file_duplicates_count); ?>, на сайте <?php echo esc_html((string) $site_duplicates_count); ?>
				</summary>
				<div style="padding:0 16px 16px;">
					<p style="margin:0 0 12px;color:#646970;">Проверка идет по артикулу. Если один и тот же артикул есть несколько раз, товар может одновременно попадать в разные списки сверки.</p>
					<?php italika_1c_import_render_report_export_button($token, 'duplicate_file_skus', 'Выгрузить дубли в файле CSV'); ?>
					<?php italika_1c_import_render_report_export_button($token, 'duplicate_site_skus', 'Выгрузить дубли на сайте CSV'); ?>

					<h3>Дубли в файле 1С</h3>
					<?php italika_1c_import_render_mismatch_note(count($file_duplicates), $file_duplicates_count); ?>
					<?php if ($file_duplicates_count > 0) : ?>
						<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
							<thead>
								<tr>
									<th>Артикул</th>
									<th>Повторов</th>
									<th>Строка</th>
									<th>Наименование из 1С</th>
									<th>Цена</th>
									<th>Остаток</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($file_duplicates as $duplicate) : ?>
									<?php foreach (($duplicate['items'] ?? []) as $row) : ?>
										<tr>
											<td><code><?php echo esc_html((string) ($duplicate['sku'] ?? '')); ?></code></td>
											<td><?php echo esc_html((string) ($duplicate['count'] ?? '')); ?></td>
											<td><?php echo esc_html((string) ($row['line'] ?? '')); ?></td>
											<td><?php echo esc_html((string) ($row['name'] ?? '')); ?></td>
											<td><?php echo esc_html(($row['price'] ?? null) === null ? 'ошибка' : wc_format_localized_price($row['price'])); ?></td>
											<td><?php echo esc_html(($row['stock'] ?? null) === null ? 'ошибка' : wc_format_localized_decimal($row['stock'])); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="margin:0 0 18px;color:#646970;">Дублей в файле нет.</p>
					<?php endif; ?>

					<h3>Дубли на сайте</h3>
					<?php italika_1c_import_render_mismatch_note(count($site_duplicates), $site_duplicates_count); ?>
					<?php if ($site_duplicates_count > 0) : ?>
						<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
							<thead>
								<tr>
									<th>Артикул</th>
									<th>Повторов</th>
									<th>ID</th>
									<th>Товар на сайте</th>
									<th>Тип</th>
									<th>Статус</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($site_duplicates as $duplicate) : ?>
									<?php foreach (($duplicate['items'] ?? []) as $item) : ?>
										<tr>
											<td><code><?php echo esc_html((string) ($duplicate['sku'] ?? '')); ?></code></td>
											<td><?php echo esc_html((string) ($duplicate['count'] ?? '')); ?></td>
											<td><?php echo esc_html((string) ($item['id'] ?? '')); ?></td>
											<td><?php echo wp_kses_post(italika_1c_import_render_product_link($item)); ?></td>
											<td><?php echo esc_html((string) ($item['type'] ?? '')); ?></td>
											<td><?php echo esc_html((string) ($item['status'] ?? '')); ?></td>
										</tr>
									<?php endforeach; ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<p style="margin:0;color:#646970;">Дублей на сайте нет.</p>
					<?php endif; ?>
				</div>
			</details>
		</div>
		<?php
		return;
		?>
		<details style="max-width:1200px;margin:18px 0;padding:0;background:#fff;border:1px solid #ccd0d4;">
			<summary style="padding:12px 16px;cursor:pointer;font-size:16px;font-weight:600;">
				Подробный отчет по несовпадениям: <?php echo esc_html((string) $total_mismatch_count); ?>
			</summary>
			<div style="padding:0 16px 16px;">
		<ul>
			<li>Строки из файла 1С, для которых нет товара на сайте по артикулу: <strong><?php echo esc_html((string) $file_missing_count); ?></strong></li>
			<li>Товары сайта с артикулом, которых нет в файле 1С: <strong><?php echo esc_html((string) $site_missing_count); ?></strong></li>
			<li>Товары сайта без артикула, которые не могут матчиться с файлом 1С: <strong><?php echo esc_html((string) $site_without_sku_count); ?></strong></li>
		</ul>

		<?php if ($file_missing_count > 0) : ?>
			<h3>Есть в файле 1С, но нет на сайте</h3>
			<?php italika_1c_import_render_mismatch_note(count($file_missing), $file_missing_count); ?>
			<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
				<thead>
					<tr>
						<th>Строка</th>
						<th>Артикул 1С</th>
						<th>Наименование из 1С</th>
						<th>Ед.</th>
						<th>Цена</th>
						<th>Остаток</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($file_missing as $row) : ?>
						<tr>
							<td><?php echo esc_html((string) ($row['line'] ?? '')); ?></td>
							<td><code><?php echo esc_html((string) ($row['sku'] ?? '')); ?></code></td>
							<td><?php echo esc_html((string) ($row['name'] ?? '')); ?></td>
							<td><?php echo esc_html((string) ($row['unit'] ?? '')); ?></td>
							<td><?php echo esc_html(($row['price'] ?? null) === null ? 'ошибка' : wc_format_localized_price($row['price'])); ?></td>
							<td><?php echo esc_html(($row['stock'] ?? null) === null ? 'ошибка' : wc_format_localized_decimal($row['stock'])); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ($site_missing_count > 0) : ?>
			<h3>Есть на сайте, но нет в файле 1С</h3>
			<?php italika_1c_import_render_mismatch_note(count($site_missing), $site_missing_count); ?>
			<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
				<thead>
					<tr>
						<th>ID</th>
						<th>Артикул сайта</th>
						<th>Товар на сайте</th>
						<th>Тип</th>
						<th>Статус</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($site_missing as $item) : ?>
						<tr>
							<td><?php echo esc_html((string) ($item['id'] ?? '')); ?></td>
							<td><code><?php echo esc_html((string) ($item['sku'] ?? '')); ?></code></td>
							<td><?php echo wp_kses_post(italika_1c_import_render_product_link($item)); ?></td>
							<td><?php echo esc_html((string) ($item['type'] ?? '')); ?></td>
							<td><?php echo esc_html((string) ($item['status'] ?? '')); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if ($site_without_sku_count > 0) : ?>
			<h3>Есть на сайте, но без артикула</h3>
			<?php italika_1c_import_render_mismatch_note(count($site_without_sku), $site_without_sku_count); ?>
			<table class="widefat striped" style="max-width:1200px;margin-bottom:18px;">
				<thead>
					<tr>
						<th>ID</th>
						<th>Товар на сайте</th>
						<th>Тип</th>
						<th>Статус</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($site_without_sku as $item) : ?>
						<tr>
							<td><?php echo esc_html((string) ($item['id'] ?? '')); ?></td>
							<td><?php echo wp_kses_post(italika_1c_import_render_product_link($item)); ?></td>
							<td><?php echo esc_html((string) ($item['type'] ?? '')); ?></td>
							<td><?php echo esc_html((string) ($item['status'] ?? '')); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
			</div>
		</details>
		<?php
	}
}

if (!function_exists('italika_1c_import_current_price')) {
	function italika_1c_import_current_price($product) {
		if (!$product || !is_a($product, 'WC_Product')) {
			return null;
		}

		$price = $product->get_regular_price();

		if ($price === '') {
			$price = $product->get_price();
		}

		return $price === '' ? null : (float) $price;
	}
}

if (!function_exists('italika_1c_import_current_stock')) {
	function italika_1c_import_current_stock($product) {
		if (!$product || !is_a($product, 'WC_Product')) {
			return null;
		}

		if (!$product->managing_stock()) {
			return null;
		}

		$quantity = $product->get_stock_quantity();

		return $quantity === null ? null : (float) $quantity;
	}
}

if (!function_exists('italika_1c_import_format_delta')) {
	function italika_1c_import_format_delta($current, $next, $decimals = 2) {
		if ($current === null || $next === null) {
			return '<span style="color:#646970;">—</span>';
		}

		$delta = (float) $next - (float) $current;

		if (abs($delta) < 0.00001) {
			return '<span style="color:#646970;">0</span>';
		}

		$color = $delta > 0 ? '#008a20' : '#b32d2e';
		$prefix = $delta > 0 ? '+' : '';
		$value = number_format($delta, $decimals, ',', ' ');

		return '<strong style="color:' . esc_attr($color) . ';">' . esc_html($prefix . $value) . '</strong>';
	}
}

if (!function_exists('italika_1c_import_preview_key')) {
	function italika_1c_import_preview_key($token) {
		return 'italika_1c_import_' . sanitize_key((string) $token);
	}
}

if (!function_exists('italika_1c_import_save_preview')) {
	function italika_1c_import_save_preview($token, $rows) {
		$key = italika_1c_import_preview_key($token);
		$expires = time() + DAY_IN_SECONDS;

		set_transient($key, $rows, DAY_IN_SECONDS);
		update_option($key, $rows, false);
		update_option($key . '_expires', $expires, false);
	}
}

if (!function_exists('italika_1c_import_get_preview')) {
	function italika_1c_import_get_preview($token) {
		$key = italika_1c_import_preview_key($token);
		$rows = get_transient($key);

		if (is_array($rows)) {
			return $rows;
		}

		$expires = (int) get_option($key . '_expires', 0);
		if ($expires > 0 && $expires < time()) {
			delete_option($key);
			delete_option($key . '_expires');
			return false;
		}

		$rows = get_option($key, false);
		return is_array($rows) ? $rows : false;
	}
}

if (!function_exists('italika_1c_import_delete_preview')) {
	function italika_1c_import_delete_preview($token) {
		$key = italika_1c_import_preview_key($token);

		delete_transient($key);
		delete_option($key);
		delete_option($key . '_expires');
	}
}

if (!function_exists('italika_1c_import_report_filename')) {
	function italika_1c_import_report_filename($type) {
		$names = [
			'all' => 'italika-1c-reports.csv',
			'file_missing_on_site' => 'italika-1c-file-missing-on-site.csv',
			'site_missing_in_file' => 'italika-1c-site-missing-in-file.csv',
			'site_without_sku' => 'italika-1c-site-without-sku.csv',
			'duplicate_file_skus' => 'italika-1c-duplicate-file-skus.csv',
			'duplicate_site_skus' => 'italika-1c-duplicate-site-skus.csv',
		];

		return $names[$type] ?? $names['all'];
	}
}

if (!function_exists('italika_1c_import_report_rows_for_export')) {
	function italika_1c_import_report_rows_for_export($type, $report) {
		$rows = [];

		if ($type === 'all') {
			foreach (['file_missing_on_site', 'site_missing_in_file', 'site_without_sku', 'duplicate_file_skus', 'duplicate_site_skus'] as $report_type) {
				foreach (italika_1c_import_report_rows_for_export($report_type, $report) as $row) {
					$rows[] = array_merge(['report' => $report_type], $row);
				}
			}

			return $rows;
		}

		if ($type === 'file_missing_on_site') {
			foreach (($report['file_missing_on_site'] ?? []) as $item) {
				$rows[] = [
					'line' => (string) ($item['line'] ?? ''),
					'sku' => (string) ($item['sku'] ?? ''),
					'name' => (string) ($item['name'] ?? ''),
					'unit' => (string) ($item['unit'] ?? ''),
					'price' => ($item['price'] ?? null) === null ? '' : (string) $item['price'],
					'stock' => ($item['stock'] ?? null) === null ? '' : (string) $item['stock'],
				];
			}

			return $rows;
		}

		if ($type === 'site_missing_in_file') {
			foreach (($report['site_missing_in_file'] ?? []) as $item) {
				$rows[] = [
					'id' => (string) ($item['id'] ?? ''),
					'sku' => (string) ($item['sku'] ?? ''),
					'name' => (string) ($item['name'] ?? ''),
					'type' => (string) ($item['type'] ?? ''),
					'status' => (string) ($item['status'] ?? ''),
					'edit_url' => (string) ($item['edit_url'] ?? ''),
				];
			}

			return $rows;
		}

		if ($type === 'site_without_sku') {
			foreach (($report['site_without_sku'] ?? []) as $item) {
				$rows[] = [
					'id' => (string) ($item['id'] ?? ''),
					'name' => (string) ($item['name'] ?? ''),
					'type' => (string) ($item['type'] ?? ''),
					'status' => (string) ($item['status'] ?? ''),
					'edit_url' => (string) ($item['edit_url'] ?? ''),
				];
			}

			return $rows;
		}

		if ($type === 'duplicate_file_skus') {
			foreach (($report['file_duplicate_skus'] ?? []) as $duplicate) {
				foreach (($duplicate['items'] ?? []) as $item) {
					$rows[] = [
						'duplicate_sku' => (string) ($duplicate['sku'] ?? ''),
						'duplicate_count' => (string) ($duplicate['count'] ?? ''),
						'line' => (string) ($item['line'] ?? ''),
						'name' => (string) ($item['name'] ?? ''),
						'unit' => (string) ($item['unit'] ?? ''),
						'price' => ($item['price'] ?? null) === null ? '' : (string) $item['price'],
						'stock' => ($item['stock'] ?? null) === null ? '' : (string) $item['stock'],
					];
				}
			}

			return $rows;
		}

		if ($type === 'duplicate_site_skus') {
			foreach (($report['site_duplicate_skus'] ?? []) as $duplicate) {
				foreach (($duplicate['items'] ?? []) as $item) {
					$rows[] = [
						'duplicate_sku' => (string) ($duplicate['sku'] ?? ''),
						'duplicate_count' => (string) ($duplicate['count'] ?? ''),
						'id' => (string) ($item['id'] ?? ''),
						'name' => (string) ($item['name'] ?? ''),
						'type' => (string) ($item['type'] ?? ''),
						'status' => (string) ($item['status'] ?? ''),
						'edit_url' => (string) ($item['edit_url'] ?? ''),
					];
				}
			}
		}

		return $rows;
	}
}

if (!function_exists('italika_1c_import_send_csv_report')) {
	function italika_1c_import_send_csv_report($filename, $rows) {
		nocache_headers();
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');

		$output = fopen('php://output', 'w');
		fwrite($output, "\xEF\xBB\xBF");

		$headers = [];
		foreach ($rows as $row) {
			foreach (array_keys($row) as $key) {
				if (!in_array($key, $headers, true)) {
					$headers[] = $key;
				}
			}
		}

		if (!$headers) {
			$headers = ['empty'];
			$rows = [['empty' => '']];
		}

		fputcsv($output, $headers, ';');

		foreach ($rows as $row) {
			$line = [];

			foreach ($headers as $header) {
				$line[] = (string) ($row[$header] ?? '');
			}

			fputcsv($output, $line, ';');
		}

		fclose($output);
		exit;
	}
}

add_action('admin_post_italika_1c_import_export_report', function () {
	if (!current_user_can('manage_woocommerce')) {
		wp_die(esc_html__('Недостаточно прав.', 'italika'));
	}

	check_admin_referer('italika_1c_import_export_report', 'italika_1c_import_export_nonce');

	$token = isset($_POST['italika_1c_import_token']) ? sanitize_text_field(wp_unslash($_POST['italika_1c_import_token'])) : '';
	$type = isset($_POST['italika_1c_import_report_type']) ? sanitize_key(wp_unslash($_POST['italika_1c_import_report_type'])) : 'all';
	$allowed_types = ['all', 'file_missing_on_site', 'site_missing_in_file', 'site_without_sku', 'duplicate_file_skus', 'duplicate_site_skus'];

	if (!in_array($type, $allowed_types, true)) {
		$type = 'all';
	}

	$rows = $token !== '' ? italika_1c_import_get_preview($token) : false;

	if (!$rows || !is_array($rows)) {
		wp_die(esc_html__('Предпросмотр устарел. Загрузите файл еще раз.', 'italika'));
	}

	$report = italika_1c_import_build_mismatch_report($rows, PHP_INT_MAX);
	$csv_rows = italika_1c_import_report_rows_for_export($type, $report);

	italika_1c_import_send_csv_report(italika_1c_import_report_filename($type), $csv_rows);
});

add_action('wp_ajax_italika_1c_import_batch', function () {
	if (!current_user_can('manage_woocommerce')) {
		wp_send_json_error(['message' => 'Недостаточно прав.'], 403);
	}

	check_ajax_referer('italika_1c_import_batch', 'nonce');

	$token = isset($_POST['token']) ? sanitize_text_field(wp_unslash($_POST['token'])) : '';
	$offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
	$limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 25;
	$limit = max(1, min(100, $limit));
	$rows = $token !== '' ? italika_1c_import_get_preview($token) : false;

	if (!$rows || !is_array($rows)) {
		wp_send_json_error(['message' => 'Предпросмотр не найден. Загрузи файл еще раз.'], 400);
	}

	$total = count($rows);
	if ($offset >= $total) {
		italika_1c_import_delete_preview($token);
		wp_send_json_success([
			'done' => true,
			'total' => $total,
			'offset' => $total,
			'processed' => 0,
			'updated' => 0,
			'skipped' => 0,
			'unmatched' => 0,
		]);
	}

	$chunk = array_slice($rows, $offset, $limit);
	$result = italika_1c_import_apply_rows($chunk);
	$processed = count($chunk);
	$next_offset = min($total, $offset + $processed);
	$done = $next_offset >= $total;

	if ($done) {
		italika_1c_import_delete_preview($token);
	}

	wp_send_json_success([
		'done' => $done,
		'total' => $total,
		'offset' => $next_offset,
		'processed' => $processed,
		'updated' => (int) $result['updated'],
		'skipped' => (int) $result['skipped'],
		'unmatched' => (int) $result['unmatched'],
	]);
});

if (!function_exists('italika_1c_import_render_admin_page')) {
	function italika_1c_import_render_admin_page() {
		if (!current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('Недостаточно прав.', 'italika'));
		}

		$preview_rows = [];
		$summary = null;
		$mismatch_report = null;
		$token = '';

		if (isset($_POST['italika_1c_import_action']) && $_POST['italika_1c_import_action'] === 'preview') {
			check_admin_referer('italika_1c_import_preview', 'italika_1c_import_nonce');

			$file = $_FILES['italika_1c_import_file'] ?? null;
			if (!$file || empty($file['tmp_name'])) {
				italika_1c_import_notice('Выбери CSV-файл выгрузки.', 'error');
			} else {
				$rows = italika_1c_import_parse_file($file['tmp_name']);

				if (is_wp_error($rows)) {
					italika_1c_import_notice($rows->get_error_message(), 'error');
				} else {
					$summary = italika_1c_import_build_summary($rows);
					$mismatch_report = italika_1c_import_build_mismatch_report($rows);
					$token = wp_generate_password(20, false, false);
					italika_1c_import_save_preview($token, $rows);
					$preview_rows = array_slice($rows, 0, 30);
				}
			}
		}

		if (isset($_POST['italika_1c_import_action']) && $_POST['italika_1c_import_action'] === 'import') {
			check_admin_referer('italika_1c_import_apply', 'italika_1c_import_nonce');

			$token = isset($_POST['italika_1c_import_token']) ? sanitize_text_field(wp_unslash($_POST['italika_1c_import_token'])) : '';
			$rows = $token !== '' ? italika_1c_import_get_preview($token) : false;

			if (!$rows || !is_array($rows)) {
				italika_1c_import_notice('Предпросмотр устарел. Загрузи файл еще раз.', 'error');
			} else {
				$result = italika_1c_import_apply_rows($rows);
				$mismatch_report = italika_1c_import_build_mismatch_report($rows);
				italika_1c_import_delete_preview($token);
				italika_1c_import_notice(sprintf(
					'Импорт завершен. Обновлено: <strong>%d</strong>. Пропущено: <strong>%d</strong>. Есть в файле 1С, но нет на сайте: <strong>%d</strong>. Есть на сайте, но нет в файле 1С: <strong>%d</strong>.',
					(int) $result['updated'],
					(int) $result['skipped'],
					(int) $result['unmatched'],
					(int) ($mismatch_report['site_missing_in_file_count'] ?? 0)
				));
			}
		}
		?>
		<div class="wrap">
			<h1>Импорт цен и остатков из 1С</h1>
			<p>Загрузи CSV с разделителем <code>;</code>. Формат: <code>артикул;наименование;единица;цена;остаток</code>. Совпадение идет по артикулу товара WooCommerce.</p>

			<form method="post" enctype="multipart/form-data" style="margin:20px 0;padding:16px;background:#fff;border:1px solid #ccd0d4;max-width:900px;">
				<?php wp_nonce_field('italika_1c_import_preview', 'italika_1c_import_nonce'); ?>
				<input type="hidden" name="italika_1c_import_action" value="preview">
				<p>
					<input type="file" name="italika_1c_import_file" accept=".csv,.txt" required>
					<button type="submit" class="button button-primary">Проверить файл</button>
				</p>
			</form>

			<?php if (!$summary && $mismatch_report) : ?>
				<?php italika_1c_import_render_mismatch_report($mismatch_report); ?>
			<?php endif; ?>

			<?php if ($summary) : ?>
				<h2>Проверка</h2>
				<ul>
					<li>Строк в файле: <strong><?php echo esc_html((string) $summary['total']); ?></strong></li>
					<li>Найдено товаров по артикулу: <strong><?php echo esc_html((string) $summary['matched']); ?></strong></li>
					<li>Есть в файле 1С, но нет на сайте по артикулу: <strong><?php echo esc_html((string) $summary['unmatched']); ?></strong></li>
					<li>Весовых строк: <strong><?php echo esc_html((string) $summary['weight']); ?></strong></li>
					<li>Фасовка не распознана, будет принято 1 кг/1 л: <strong><?php echo esc_html((string) $summary['unknown_sell_unit']); ?></strong></li>
					<li>Под заказ, цена 0 и остаток не учитывается: <strong><?php echo esc_html((string) $summary['on_request']); ?></strong></li>
					<li>Строк с ошибкой цены: <strong><?php echo esc_html((string) $summary['invalid_price']); ?></strong> <span style="color:#646970;">(битая или отрицательная цена; пусто и 0 — это «под заказ»)</span></li>
					<li>Строк с ошибкой остатка: <strong><?php echo esc_html((string) $summary['invalid_stock']); ?></strong></li>
				</ul>

				<?php italika_1c_import_render_mismatch_report($mismatch_report, $token); ?>

				<table class="widefat striped" style="max-width:1200px;">
					<thead>
						<tr>
							<th>Строка</th>
							<th>Артикул</th>
							<th>Наименование из 1С</th>
							<th>Ед.</th>
							<th>Цена</th>
							<th>Остаток</th>
							<th>Продаваемая ед.</th>
							<th>Цена сейчас</th>
							<th>Цена на сайте</th>
							<th>Изм. цены</th>
							<th>Остаток сейчас</th>
							<th>Остаток на сайте</th>
							<th>Изм. остатка</th>
							<th>Товар на сайте</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($preview_rows as $row) : ?>
							<?php
							$product = !empty($row['product_id']) ? wc_get_product((int) $row['product_id']) : null;
							$current_price = italika_1c_import_current_price($product);
							$current_stock = italika_1c_import_current_stock($product);
							$next_price = !empty($row['is_on_request']) ? 0.0 : $row['import_price'];
							$next_stock = !empty($row['is_on_request']) ? null : $row['import_stock_quantity'];
							?>
							<tr>
								<td><?php echo esc_html((string) $row['line']); ?></td>
								<td><code><?php echo esc_html($row['sku']); ?></code></td>
								<td><?php echo esc_html($row['name']); ?></td>
								<td><?php echo esc_html($row['unit']); ?></td>
								<td><?php echo esc_html($row['price'] === null ? 'ошибка' : wc_format_localized_price($row['price'])); ?></td>
								<td><?php echo esc_html($row['stock'] === null ? 'ошибка' : wc_format_localized_decimal($row['stock'])); ?></td>
								<td>
									<?php echo esc_html(wc_format_localized_decimal($row['sell_unit_size']) . ' ' . $row['unit']); ?>
									<?php if (!empty($row['sell_unit_warning'])) : ?>
										<br><strong style="color:#b32d2e;">проверь фасовку</strong>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html($current_price === null ? '—' : wc_format_localized_price($current_price)); ?></td>
								<td><?php echo esc_html(!empty($row['is_on_request']) ? 'Под заказ' : ($row['import_price'] === null ? 'ошибка' : wc_format_localized_price($row['import_price']))); ?></td>
								<td><?php echo wp_kses_post(italika_1c_import_format_delta($current_price, $next_price, 2)); ?></td>
								<td><?php echo esc_html($current_stock === null ? 'не учитывается' : wc_format_localized_decimal($current_stock)); ?></td>
								<td><?php echo esc_html(!empty($row['is_on_request']) ? 'не учитывается' : ($row['import_stock_quantity'] === null ? 'ошибка' : (string) $row['import_stock_quantity'])); ?></td>
								<td><?php echo wp_kses_post(italika_1c_import_format_delta($current_stock, $next_stock, 0)); ?></td>
								<td>
									<?php if ($product) : ?>
										<a href="<?php echo esc_url(get_edit_post_link($product->get_id())); ?>"><?php echo esc_html($product->get_name()); ?></a>
									<?php else : ?>
										<strong style="color:#b32d2e;">не найден</strong>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div
					id="italika-1c-batch-import"
					data-token="<?php echo esc_attr($token); ?>"
					data-nonce="<?php echo esc_attr(wp_create_nonce('italika_1c_import_batch')); ?>"
					data-total="<?php echo esc_attr((string) $summary['total']); ?>"
					data-site-missing="<?php echo esc_attr((string) ($mismatch_report['site_missing_in_file_count'] ?? 0)); ?>"
					style="margin-top:18px;max-width:900px;padding:16px;background:#fff;border:1px solid #ccd0d4;"
				>
					<button type="button" class="button button-primary" id="italika-1c-batch-start">Импортировать цены и остатки батчами</button>
					<div id="italika-1c-batch-progress-wrap" style="display:none;margin-top:14px;">
						<div style="height:22px;background:#f0f0f1;border:1px solid #c3c4c7;max-width:700px;">
							<div id="italika-1c-batch-progress-bar" style="height:100%;width:0;background:#2271b1;"></div>
						</div>
						<p id="italika-1c-batch-status" style="margin:8px 0 0;">Ожидание запуска.</p>
						<p style="margin:6px 0 0;">
							Обновлено: <strong id="italika-1c-batch-updated">0</strong>.
							Пропущено: <strong id="italika-1c-batch-skipped">0</strong>.
							Есть в файле 1С, но нет на сайте: <strong id="italika-1c-batch-unmatched">0</strong>.
							Есть на сайте, но нет в файле 1С: <strong><?php echo esc_html((string) ($mismatch_report['site_missing_in_file_count'] ?? 0)); ?></strong>.
						</p>
					</div>
				</div>
				<script>
					(function () {
						var box = document.getElementById('italika-1c-batch-import');
						if (!box) {
							return;
						}

						var startButton = document.getElementById('italika-1c-batch-start');
						var wrap = document.getElementById('italika-1c-batch-progress-wrap');
						var bar = document.getElementById('italika-1c-batch-progress-bar');
						var status = document.getElementById('italika-1c-batch-status');
						var updatedNode = document.getElementById('italika-1c-batch-updated');
						var skippedNode = document.getElementById('italika-1c-batch-skipped');
						var unmatchedNode = document.getElementById('italika-1c-batch-unmatched');
						var token = box.getAttribute('data-token');
						var nonce = box.getAttribute('data-nonce');
						var total = parseInt(box.getAttribute('data-total'), 10) || 0;
						var siteMissing = parseInt(box.getAttribute('data-site-missing'), 10) || 0;
						var offset = 0;
						var limit = 25;
						var updated = 0;
						var skipped = 0;
						var unmatched = 0;
						var running = false;

						function setProgress() {
							var percent = total > 0 ? Math.min(100, Math.round((offset / total) * 100)) : 0;
							bar.style.width = percent + '%';
							status.textContent = 'Обработано ' + offset + ' из ' + total + ' (' + percent + '%).';
							updatedNode.textContent = updated;
							skippedNode.textContent = skipped;
							unmatchedNode.textContent = unmatched;
						}

						function runBatch() {
							var data = new FormData();
							data.append('action', 'italika_1c_import_batch');
							data.append('nonce', nonce);
							data.append('token', token);
							data.append('offset', String(offset));
							data.append('limit', String(limit));

							fetch(ajaxurl, {
								method: 'POST',
								credentials: 'same-origin',
								body: data
							})
								.then(function (response) {
									return response.json();
								})
								.then(function (response) {
									if (!response || !response.success) {
										throw new Error(response && response.data && response.data.message ? response.data.message : 'Ошибка импорта.');
									}

									offset = response.data.offset || offset;
									updated += response.data.updated || 0;
									skipped += response.data.skipped || 0;
									unmatched += response.data.unmatched || 0;
									setProgress();

									if (response.data.done) {
										running = false;
										startButton.disabled = true;
										status.textContent = 'Импорт завершен. Обновлено: ' + updated + '. Пропущено: ' + skipped + '. Есть в файле 1С, но нет на сайте: ' + unmatched + '. Есть на сайте, но нет в файле 1С: ' + siteMissing + '. Подробности в отчете выше.';
										bar.style.width = '100%';
										return;
									}

									window.setTimeout(runBatch, 150);
								})
								.catch(function (error) {
									running = false;
									startButton.disabled = false;
									status.textContent = 'Остановка: ' + error.message + ' Можно нажать кнопку еще раз, импорт продолжится с текущей пачки.';
								});
						}

						startButton.addEventListener('click', function () {
							if (running) {
								return;
							}

							running = true;
							startButton.disabled = true;
							wrap.style.display = 'block';
							setProgress();
							runBatch();
						});
					})();
				</script>
			<?php endif; ?>
		</div>
		<?php
	}
}

add_action('admin_menu', function () {
	if (!class_exists('WooCommerce')) {
		return;
	}

	add_submenu_page(
		'woocommerce',
		'Импорт 1С',
		'Импорт 1С',
		'manage_woocommerce',
		'italika-1c-import',
		'italika_1c_import_render_admin_page'
	);
});
