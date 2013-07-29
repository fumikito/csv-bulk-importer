<?php /* @var $this Hametuha_CSV_Bulk_Importer */ ?>
<div class="wrap">
	<div id="icon-tools" class="icon32"><br></div>
	<h2><?php echo esc_html($this->title); ?></h2>
	
	<p class="description">
		<?php printf('CSVファイル <code>%s</code> を処理します。1行目はヘッダーにしてください。', basename($this->csv)); ?>
	</p>
	
	<form class="hametuha-bulk-form" action="<?php echo admin_url('admin-ajax.php') ?>" method="post">
		<?php wp_nonce_field($this->get_action_name()); ?>
		<input type="hidden" name="action" value="<?php echo $this->get_action_name(); ?>" />
		<table class="form-table">
			<tr>
				<th>行数</th>
				<td>
					<input class="import" name="import" type="text" readonly="readonly" value="<?php echo $this->get_total(); ?>" />行
				</td>
			</tr>
			<tr>
				<th>処理済み</th>
				<td>
					<input class="imported" name="imported" type="text" value="0" />行
				</td>
			</tr>
			<tr>
				<th>進捗</th>
				<td>
					<div class="indicator" style="width: 300px; border:1px solid #ddd; background: #f9f9f9; height: 20px;">
						<div class="graph" style="background: #06c; width: 0%; height: 20px;"></div>
					</div>
				</td>
			</tr>
		</table>
		<?php submit_button('実行'); ?>
	</form>
</div>