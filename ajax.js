jQuery(document).ready(function($){
	
	// CSVをインポートする
	$('.hametuha-bulk-form').submit(function(e){
		e.preventDefault();
		var form = $(this),
			initOption = {
				dataType: 'json',
				success: function(response){
					if(response.success){
						if(confirm(response.message)){
							form.find('tr.hidden').removeClass('hidden').effect('highlight');
							form.find('input[name=import]').val(response.total);
							form.find('input[name=prepared]').val('1');
							form.ajaxSubmit(processOption);
						}
					}else{
						alert(response.message);
					}
				}
			},
			processOption = {
				dataType: 'json',
				success: function(response){
					if(response.success){
						// 値を保存
						form.find('input[name=imported]').val(response.imported);
						// 進捗を更新
						var max = parseInt(form.find('.import').val(), 10),
							current = parseInt(form.find('.imported').val(), 10);
						form.find('.graph').css('width', Math.min(100, Math.floor(current / max * 100)) + '%');
						// まだ残りがあったら継続、なければ終了
						if(current < max){
							form.ajaxSubmit(processOption);
						}else{
							alert('完了しました！');
							form.remove();
							window.location.reload();
						}
					}else{
						alert(reponse.message);
						form.find('.hametuha-bulk-form tr:last-child').addClass('hidden');
						form.find('input[name=import]').val('0');
						form.find('input[name=prepared]').val('0');
					}
				}
			};
		form.ajaxSubmit(initOption);
	});
	
	// CSVを選択する
	if($('.csv-selector').length){
		// MediaFrameを初期化
		var csvMediaFrame;
		$('#csv-select-button').click(function(e){
			e.preventDefault();
			// すでに初期化済みなら再利用
			if(csvMediaFrame){
				csvMediaFrame.open();
				return;
			}
			// メディアフレームを作成
			csvMediaFrame = wp.media.frames.csvMediaFrame = wp.media({
				className: 'media-frame csv-media-frame',
				frame: 'select',
				multiple: false,
				title: 'インポートするCSVを選択してください',
				library:{
					type: 'text/csv'
				},
				button:{
					text: '指定したCSVを選択'
				}
			});
			// 選択イベントにバインド
			csvMediaFrame.on('select', function(){
				var csv = csvMediaFrame.state().get('selection').first().toJSON();
				$('#csv-name code').empty().text(csv.filename).
						prepend('<img src="' + csv.icon + '" alt="' + csv.filename + '" width="32" height="32" /><br />');
				$('#csv').val(csv.id);
				$('p.csv-selector').addClass('active').effect('highlight');
			});
			// Media Frameをオープン
			csvMediaFrame.open();
		});
	}
});