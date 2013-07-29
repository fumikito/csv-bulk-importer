jQuery(document).ready(function($){
	$('.hametuha-bulk-form').submit(function(e){
		var form = $(this),
			option = {
				dataType: 'json',
				success: function(response){
					// 値を保存
					form.find('input[name=imported]').val(response.imported);
					
					// 進捗を更新
					var max = parseInt(form.find('.import').val(), 10),
						current = parseInt(form.find('.imported').val(), 10);
				
					form.find('.graph').css('width', Math.min(100, Math.floor(current / max * 100)) + '%');
					if(current < max){
						form.ajaxSubmit(option);
					}else{
						alert('完了しました！');
						form.remove();
					}
					return false;
				}
			};
		form.ajaxSubmit(option);
		e.preventDefault();
	});
});