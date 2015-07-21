jQuery(function($) {
	// view order info.
	$('a.modalAnchor.viewEnrollmentStatus').bind('before-open.mw', function(event){
		// get cart_srl
		var cart_srl = $(this).attr('data-cart-srl');
		var mid = $(this).attr('data-mid');

		// get enrollment form
		exec_xml(
			'elearning'
			, 'getElearningAdminEnrollmentStatus'
			, {cart_srl : cart_srl, 'mid': mid}
			, function(ret) {
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#enrollmentStatus').html(tpl); }
			, ['error','message','tpl']
		);
	});

	// view order info.
	$('a.modalAnchor.viewOrderInfo').bind('before-open.mw', function(event){
		// get cart_srl
		var order_srl = $(this).attr('data-order-srl');
		var mid = $(this).attr('data-mid');

		// get enrollment form
		exec_xml(
			'ncart'
			, 'getNcartAdminOrderDetails'
			, {order_srl : order_srl, 'mid': mid}
			, function(ret) {
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#orderInfo').html(tpl); }
			, ['error','message','tpl']
		);
	});

	// update enrollment
	$('a.modalAnchor.updateEnrollment').bind('before-open.mw', function(event){
		// get cart_srl
		var cart_srl = $(this).attr('data-cart-srl');
		var mid = $(this).attr('data-mid');

		// get enrollment form
		exec_xml(
			'elearning'
			, 'getElearningAdminEnrollment'
			, {cart_srl : cart_srl, 'mid': mid}
			, function(ret) {
				var tpl = ret.tpl.replace(/<enter>/g, '\n');
				$('#enrollmentForm').html(tpl); }
			, ['error','message','tpl']
		);
	});

	$('a.modalAnchor.deleteEnrollments').bind('before-open.mw', function(event){
		// get checked items.
		var a = [];
		var $checked_list = jQuery('input[name=cart\\[\\]]:checked');
		$checked_list.each(function() { a.push(jQuery(this).val()); });
		var cart_srl = a.join(',');
		var mid = $(this).attr('data-mid');

		// get delete form.
		exec_xml(
			'nstore',
			'getElearningAdminDeleteEnrollment',
			{cart_srl:cart_srl, 'mid': mid},
			function(ret){
					var tpl = ret.tpl.replace(/<enter>/g, '\n');
					$('#class_deleteForm').html(tpl);
			},
			['error','message','tpl']
		);
	});

	$('.add_days').change(function() {
		var target = $(this).val();
		var cart_srl = $(this).attr('data-cart_srl');
		var id = "admin_input_" + cart_srl;
		var replace_html_code = '<input type="text" name="additional_class" id="' + id + '" class="additional_class" style="width:80px;"/>';
		//replace_html_code += '<input type="button" onclick="refresh_select(this)" data-target = "' + id + '" class="x_btn" style="width:80px; margin-left:5px;" value="되돌리기" />';

		if(target == 'admin') {
			$('#admin_input_' + cart_srl).replaceWith(replace_html_code);
		} else {
			replace_html_code = '<span id="admin_input_' + cart_srl + '"></span>';
			console.log(replace_html_code);
			$('#admin_input_' + cart_srl).replaceWith(replace_html_code);
		}
	});

	$('.add_class').click(function() {
		var target = $('.add_days').val();
		var cart_srl = $(this).attr('data-cart_srl');

		//check if target is admin_input
		if(target == 'admin') {
			target = $('#admin_input_' + cart_srl).val();
		}
		//check if target is not set
		if(target == 0) {
			alert("추가할 일수를 선택하여주세요.");
			return;
		}
		exec_xml(
			'cympuser',
			'procCympuserAdminAddClassDays',
			{cart_srl:cart_srl, additional_days:target},
			function(ret){
				location.reload();
			},
			['error','message','tpl']
		);
	});

	$('#class_table').on('click', 'span', function() {
		var $e = $(this).parent();
		if($e.attr('class') === 'change_to_input') {
			var val = $(this).html();
			var width = ((val.length) * 8) + 'px';
			var html_code = '<input type="text" style="width:' + width + ';" value="' + val + '" />'; 
			$e.html(html_code);
			var $newE = $e.find('input');
			$newE.focus();
		}
		$newE.on('blur', function() {
			var cart_srl = $e.attr('data-cart_srl');
			var target = $e.attr('data-target');
			var value = $(this).val();
			var id = target + '_' + cart_srl;
			$(this).parent().html('<span id=' + id + '>' + $(this).val() + '</span>');
			
			exec_xml(
				'cympuser',
				'procCympuserAdminChangeDates',
				{cart_srl:cart_srl, target:target, value:value},
				function(ret){
					$('#period_days_' + cart_srl).html(ret['period_days']);
					$('#startdate_' + cart_srl).text(ret['startdate']);
					$('#enddate_' + cart_srl).text(ret['enddate']);
				},
				['error','message','tpl', 'period_days', 'startdate', 'enddate']
			);
		});

		$newE.bind("keypress", function(e) {
			if (e.keyCode == 13) {
				this.blur();
			}
		});
	});

	$('#fo_classlist').bind("keypress", function(e) {
	  if (e.keyCode == 13) {               
		e.preventDefault();
		return false;
	  }
	});


});
