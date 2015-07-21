(function($) {
	jQuery(function($) {
		$('a.modalAnchor.deleteOrders').bind('before-open.mw', function(event){
			// get checked items.
			var a = [];
			var $checked_list = jQuery('input[name=cart\\[\\]]:checked');
			$checked_list.each(function() { a.push(jQuery(this).val()); });
			var order_srl = a.join(',');

			// get delete form.
			exec_xml(
					'nstore',
					'getNstoreAdminDeleteOrders',
					{order_srl:order_srl},
					function(ret){
							var tpl = ret.tpl.replace(/<enter>/g, '\n');
							$('#textbook_deleteForm').html(tpl);
					},
					['error','message','tpl']
			);
		});
	});

	jQuery(function($) {
		$('a.modalAnchor.deleteEnrollments').bind('before-open.mw', function(event){
			// get checked items.
			var a = [];
			var $checked_list = jQuery('input[name=cart\\[\\]]:checked');
			$checked_list.each(function() { a.push(jQuery(this).val()); });
			var cart_srl = a.join(',');
			console.log(cart_srl);

			// get delete form.
			exec_xml(
					'elearning',
					'getElearningAdminDeleteEnrollment',
					{cart_srl:cart_srl},
					function(ret){
							var tpl = ret.tpl.replace(/<enter>/g, '\n');
							$('#textbook_deleteForm').html(tpl);
					},
					['error','message','tpl']
			);
		});
	});

	jQuery(function($) {
		$('a.modalAnchor.addOrderInfo').bind('before-open.mw', function(event){
			
			var member_srl = $(this).attr('data-member_srl');
			var target = $(this).attr('data-target_proc');
			// get delete form.
			exec_xml(
					'cympuser',
					'getCympuserAdminItemList',
					{target: target, member_srl: member_srl},
					function(ret){
							var tpl = ret.tpl.replace(/<enter>/g, '\n');
							$('#addOrder').html(tpl);
					},
					['error','message','tpl']
			);
		});
	});

}) (jQuery);

/**
 * @brief 테이블 클릭해도 checkbox 에 클릭되도록 하는 fxn
 **/
function setCheckBox(item_srl) {
	var target = "#checkbox_" + item_srl;
	if(jQuery(target).is(":checked"))
    {
		jQuery(target).attr("checked", false);
    }
	else 
    {
		jQuery(target).attr("checked", true)
    }
}
