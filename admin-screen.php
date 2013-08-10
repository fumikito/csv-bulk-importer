<?php /* @var $this Hametuha_CSV_Bulk_Importer */ ?>
<div class="wrap">
	<div id="icon-tools" class="icon32"><br></div>
	<h2><?php echo esc_html($this->title); ?></h2>
	
	<?php if(!empty($this->description)): ?>
	<p class="description">
		<?php echo esc_html($this->description); ?>
	</p>
	<?php endif; ?>
	
	<p class="description">
		<?php echo 'CSVファイルを処理します。1行目はヘッダーとして扱われ、インポートされません。'; ?>
	</p>
	
	<form class="hametuha-bulk-form" action="<?php echo admin_url('admin-ajax.php') ?>" method="post">
		<?php wp_nonce_field($this->get_action_name()); ?>
		<input type="hidden" name="action" value="<?php echo $this->get_action_name(); ?>" />
		<input type="hidden" name="prepared" value="0" />
		<table class="form-table">
			<tr>
				<th>CSVファイル</th>
				<td>
					<?php if(empty($this->csv_files)): ?>
						<?php if(function_exists('wp_enqueue_media')):?>
					
						<?php
							else:
								//バージョンが古いので、全部取得する
								$csvs = get_posts(array(
									'post_type' => 'attachment',
									'post_mime_type' => 'text/csv',
									'posts_per_page' => -1
								));
						?>
							<?php if(empty($csvs)): ?>
								<input type="hidden" name="csv" value="0" />
								<strong>CSVファイルが一つも登録されていません！</strong>
							<?php else: ?>
								<select name="csv">
									<?php foreach($csvs as $csv): ?>
										<option value="<?php echo esc_attr($csv->ID); ?>">
											<?php printf('%s（%s）', esc_html($csv->post_title), basename($csv->guid)); ?>
										</option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>
							<p class="description">
								CSVファイルは<a href="<?php echo admin_url('media-new.php'); ?>">メディアページ</a>で登録したものが表示されます。
							</p>
						<?php endif; ?>
					<?php else: ?>
					<select name="csv">
						<?php foreach($this->csv_files as $key => $csv): ?>
						<option value="<?php echo esc_attr($key); ?>">
							<?php echo esc_html(basename($csv)); ?>
						</option>
						<?php endforeach; ?>
					</select>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th>エンコード</th>
				<td>
					<select name="encoding">
						<option value="sjis-win">Shift_JIS（MS Excelから書き出したもの）</option>
						<option value="utf-8">UTF-8</option>
					</select>
				</td>
			</tr>
			<tr class="hidden">
				<th>進捗</th>
				<td>
					<p class="description">
						<input class="import" name="import" type="text" readonly="readonly" value="0" />行を
						<code><?php echo number_format($this->step); ?></code>行ずつ処理します。
					</p>
					<div class="indicator" style="width: 300px; border:1px solid #ddd; background: #f9f9f9; height: 20px;">
						<div class="graph" style="background: #06c; width: 0%; height: 20px;"></div>
					</div>
					<p>
						<input class="imported" name="imported" type="text" value="0" />行を処理しました。
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button('実行'); ?>
	</form>
</div>