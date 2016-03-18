jQuery(function($) {
	$("#extraList").sortable({ handle:'.iconMoveTo', opacity: 0.6, cursor: 'move',
		update: function(event,ui) {
			var order = jQuery(this).sortable("serialize");
			var params = new Array();
			params['order'] = order;
			var response_tags = new Array('error','message');
			exec_xml('cympuser', 'procCympuserAdminUpdateOptionsOrder', params, function(ret_obj) { }, response_tags);
		}
	});

	$('a.modalAnchor.extendFormEdit').bind('before-open.mw', function(event){
		var group_srl = $('#group_srl').val();

		exec_xml(
			'cympuser', 'getCympuserAdminInsertOptions',
			{group_srl: group_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#extendForm').html(tpl);
			}, ['error','message','tpl']
		);
	});

	$('a.modalAnchor._edit').bind('before-open.mw', function(event){
		var option_srl = $(this).data('option-srl'),
		group_srl = $('#group_srl').val();
		exec_xml(
			'cympuser', 'getCympuserAdminInsertOptions',
			{option_srl:option_srl, group_srl: group_srl},
			function(ret){
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#extendForm').html(tpl);
				$(this).attr('data-extra-srl', '');
			}, ['error','message','tpl']
		);

	});

	$('a.modalAnchor.extendFormDelete').bind('before-open.mw', function(event){
		var option_srl = $(this).data('option-srl'),
		column_title = $(this).data('column_title');
		$('#item_to_delete').text(column_title);
		$("#option_srl").val(option_srl);
	});

	$('.is_active').on('change', function() {
		var is_active = $(this).data('is_active'),
		option_srl = $(this).data('option_srl');

		if($(this).attr('checked') == 'checked')
		{
			$(this).data('is_active', '1');
		}
		else
		{
			$(this).data('is_active', '0');
		}

		is_active = $(this).data('is_active');
		params = {
			option_srl : option_srl,
			is_active : is_active
		};
		updateOptions(params);
	});

	$('.required').on('change', function() {
		var required = $(this).data('required'),
		option_srl = $(this).data('option_srl');
		if($(this).attr('checked') == 'checked')
		{
			$(this).data('required', 'Y');
		}
		else
		{
			$(this).data('required', 'N');
		}

		required = $(this).data('required');
		params = {
			option_srl : option_srl,
			required: required
		}
		updateOptions(params);
	});

	function updateOptions(params)
	{
		exec_xml('cympuser', 'procCympuserAdminUpdateOptions', 
			params, function(ret) { }, ['error', 'message', 'tpl']
		);
	}

});
