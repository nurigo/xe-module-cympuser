<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserAdminController
 * @author billy(contact@nurigo.net)
 * @brief  cympuserAdminController
 */
class cympuserAdminController extends cympuser 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}
		
	/**
	 * @brief insert module instance of cympuser
	 */
	function procCympuserAdminModInsert()
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');

		$args = Context::getRequestVars();
		$args->module = 'cympuser';

		// 모듈 정보 가져오기
		if($args->module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
			if($module_info->module_srl != $args->module_srl) 
				unset($args->module_srl);
		}

		// module_srl의 값에 따라 insert/update
		if(!$args->module_srl) {
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		} else {
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}
		if(!$output->toBool()) return $output;

		$this->add('module_srl',$output->get('module_srl'));
		$this->setMessage($msg_code);	

	}

	/**
	 * @brief delete cympuser module instance
	 */
	function procCympuserAdminModDelete()
	{
		$oModuleController = &getController('module');

		$module_srl = Context::get('module_srl');
		if(!$module_srl) 
			return new Object(-1, 'module_srl 이 비었습니다.');

		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;
	
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCympuserAdminList');
		$this->setRedirectUrl($redirectUrl);
	}

	function _addElearningProduct($item_srls)
	{
		$oNproductModel = &getModel('nproduct');

		// check class already activated.
		foreach($item_srls as $row => $item_srl)
		{
			$args->member_srl = $member_srl;
			$args->item_srl = $item_srl;
			$output = executeQuery('elearning.getMyActiveClassCount', $args);
			if(!$output->toBool()) return $output;
			if($output->data->count > 0) 
			{
				$error .= sprintf("item_srl : %s <br>", $item_srl);
				continue;
			}
			
			$itemInfo = $oNproductModel->getItemInfo($item_srl);
			$cart_srl = getNextSequence();
			$cartitem_args->cart_srl = $cart_srl;
			$cartitem_args->item_srl = $item_srl;
			$cartitem_args->item_name = $itemInfo->item_name;
			$cartitem_args->member_srl = $member_srl;
			$cartitem_args->module_srl = $itemInfo->module_srl;
			$cartitem_args->quantity = 1;
			// 유료 / 무료 처리
			$is_free == 'Y' ? $cartitem_args->price = 0 : $cartitem_args->price = $itemInfo->price;
			$cartitem_args->taxfree = 0;
			$cartitem_args->period = $oNproductModel->getItemExtraVarValue($item_srl, 'period');
			$cartitem_args->period_unit = $oNproductModel->getItemExtraVarValue($item_srl, 'period_unit');
			$cartitem_args->period_days = (strtotime($cartitem_args->period . ' ' . $cartitem_args->period_unit, time()) - time()) / (60 * 60 * 24);
			$cartitem_args->order_status = '2';
			$cartitem_args->purdate = date("YmdHis");
			$cartitem_args->startdate = date('Ymd').'000000';
			$current_time = time() - (60 * 60 * 24 * 1); // 미리 하루를 빼주지 않으면 +2일이 주어진다. 이 상태로는 +1일 보너스로 주어진다.
			$cartitem_args->enddate = date('Ymd', strtotime($cartitem_args->period_days . ' days', $current_time)) . '235959';
			//if($cartitem_args->enddate > $freepassInfo->enddate) $cartitem_args->enddate = $freepassInfo->enddate;
			if($config->extra_days) $cartitem_args->period_days += $config->extra_days;
			$output = executeQuery('elearning.insertCartItem', $cartitem_args);
			if (!$output->toBool()) return $output;

			$cartitem_args->memo = '관리자 결제처리';
			$output = executeQuery('elearning.updateEnrollment', $cartitem_args);
			if(!$output->toBool()) return $output;

			$message .= sprintf("item_srl : %s item_name : %s <br>", $item_srl, $itemInfo->item_name);
		}

		if($error) return;
	}

	function _addNstoreProduct($item_srls)
	{
		$oNproductModel = &getModel('nproduct');

		foreach($item_srls as $row => $item_srl)
		{
			$itemInfo = $oNproductModel->getItemInfo($item_srl);
			$cart_srl = getNextSequence();
			$cartitem_args->cart_srl = $cart_srl;
			$cartitem_args->item_srl = $item_srl;
			$cartitem_args->item_name = $itemInfo->item_name;
			$cartitem_args->member_srl = $member_srl;
			$cartitem_args->module_srl = $itemInfo->module_srl;
			$cartitem_args->quantity = 1;
			// 유료 / 무료 처리
			$is_free == 'Y' ? $cartitem_args->price = 0 : $cartitem_args->price = $itemInfo->price;
			$cartitem_args->taxfree = 0;
			$cartitem_args->purdate = date("YmdHis");
			$output = executeQuery('nstore.insertCartItem', $cartitem_args);
			if (!$output->toBool()) 
			{
				$error .= sprintf("item_srl : %s error : %s", $item_srl, $output->message);
				continue;
			}

			$order_srl = getNextSequence();
			$cartitem_args->order_srl = $order_srl;
			$cartitem_args->payment_method = 'admin';
			$cartitem_args->title = $itemInfo->item_name;
			$cartitem_args->item_count = '1';
			$is_free == 'Y' ? $cartitem_args->total_price = 0 : $cartitem_args->total_price = $itemInfo->price;
			$is_free == 'Y' ? $cartitem_args->sum_price = 0 : $cartitem_args->sum_price = $this->getTotalPrice($item_srls);
			$cartitem_args->delivery_fee = 0;
			$cartitem_args->total_discounted_price = 0;
			$cartitem_args->total_discount_amount = 0;
			$cartitem_args->taxation_amount = 0;
			$cartitem_args->supply_amount = 0;
			$cartitem_args->taxfree_amount = 0;
			$cartitem_args->vat = 0;

			$output = executeQuery('nstore.insertOrder', $cartitem_args);
			if (!$output->toBool())
			{
				$error .= sprintf("item_srl : %s error : %s<br>", $item_srl, $output->message);
				continue;
			}

			// update order_status to transaction_done(6)
			$cartitem_args->order_status = 6;
			$output = executeQuery('nstore.updateOrderStatus', $cartitem_args);
			if(!$output->toBool()) return $output;

			$message .= sprintf("item_srl : %s item_name : %s <br>", $item_srl, $itemInfo->item_name);
		}

		if ($error) return $error;
	}

	/**
	 * @brief add product 
	 */
	function procCympuserAdminAddProduct()
	{
		$oNproductModel = getModel('nproduct');

		$target = Context::get('target');
		$member_srl = Context::get('member_srl');
		$success_return_url = Context::get('success_return_url');
		$module = Context::get('module');
		$cart = Context::get('cart');
		$is_free = Context::get('is_free');
		$item_srls = Context::get('item_srl');

		switch ($target) 
		{
			case 'elearning':
				$output = $this->_addElearningProduct($item_srls);
				break;

			case 'nstore':
				$output = $this->_addNstoreProduct($item_srls);
				break;
		}

		if($output)
		{
			$this->setMessage($error . "에러가 발생하였습니다.", 'error');
			$this->setRedirectUrl($success_return_url);
			return;
		}

		$this->setMessage($message .'추가되었습니다.');
		$this->setRedirectUrl($success_return_url);
	}

	/**
	 * @brief get total price
	 */
	function getTotalPrice($item_srls)
	{
		$oNproductModel = getModel('nproduct');

		$total_price = 0;
		foreach($item_srls as $row => $item_srl)
		{
			$itemInfo = $oNproductModel->getItemInfo($item_srl);
			$total_price += $itemInfo->price;
		}
		return $total_price;
	}

	/**
	 * @brief add class days to elearning
	 */
	function procCympuserAdminAddClassDays()
	{
		$args->cart_srl = Context::get('cart_srl');
		if(!$args->cart_srl) return new Object(-1, 'cart_srl is required');

		$output = executeQuery('elearning.getEnrollment', $args);
		if(!$output->toBool()) return $output;

		$period_days = $output->data->period_days;
		$startdate = $output->data->startdate;
		$additional_days = Context::get('additional_days') + $period_days;

		$args->period_days = $additional_days;
		$args->startdate = $startdate;
		//$args->enddate = date(substr($startdate, 0, 8), strtotime(sprintf("+%s days", $additional_days)));
		$temp_date = strtotime(substr($startdate, 0, 8)) - (60 * 60 * 24 * 1);
		$args->enddate = date('Ymd', strtotime($args->period_days . ' days', $temp_date)) . '235959';
		$output = executeQuery('elearning.updateEnrollment', $args);
		if(!$output->toBool()) return $output;

		$this->setMessage('success_saved');
	}	

	/**
	 * @brief 수강정보의 시작일/종료일 수
	 */
	function procCympuserAdminChangeDates()
	{
		$args->cart_srl = Context::get('cart_srl');
		if(!$args->cart_srl) return new Object(-1, 'cart_srl is required');

		$output = executeQuery('elearning.getEnrollment', $args);
		if(!$output->toBool()) return $output;

		$target = Context::get('target');
		$value = Context::get('value');
		if($target == 'period_days')
		{
			$startdate = $output->data->startdate;
			$args->period_days = $value;
			$args->startdate = $startdate;
			$temp_date = strtotime(substr($startdate, 0, 8)) - (60 * 60 * 24 * 1);
			$args->enddate = date('Ymd', strtotime($args->period_days . ' days', $temp_date)) . '235959';
			$output = executeQuery('elearning.updateEnrollment', $args);
			if(!$output->toBool()) return $output;
			$this->add('period_days', $args->period_days);
			$this->add('startdate', date('Y-m-d', strtotime(substr($args->startdate, 0, 8))));
			$this->add('enddate', date('Y-m-d', strtotime(substr($args->enddate, 0, 8))));
			return;
		}

		if(!$this->validateDate($value)) return new Object(-1, 'date is not valid.');

		if($target == 'startdate')
		{
			if($value == $output->data->startdate) return;

			$startdate = strtotime($value);
			$enddate = strtotime($output->data->enddate);
			$period_days = floor((abs($startdate - $enddate)) / (60 * 60 * 24)) + 1;
			$enddate = date('Ymd', $enddate) . '235959';
			$startdate = date('Ymd', $startdate) . '000000';
		}

		if($target == 'enddate')
		{
			if($value == $output->data->enddate) return;
			$enddate = strtotime($value);
			$startdate = strtotime($output->data->startdate);
			$period_days = floor((abs($startdate - $enddate)) / (60 * 60 * 24)) + 1;
			$startdate = date('Ymd', $startdate) . '000000';
			$enddate = date('Ymd', $enddate) . '235959';
		}
		$args->period_days = $period_days;
		$args->startdate = $startdate;
		$args->enddate = $enddate;
		$output = executeQuery('elearning.updateEnrollment', $args);
		if(!$output->toBool()) return $output;

		$this->add('period_days', $args->period_days);
		$this->add('startdate', date('Y-m-d', strtotime(substr($args->startdate, 0, 8))));
		$this->add('enddate', date('Y-m-d', strtotime(substr($args->enddate, 0, 8))));
	}

	/**
	 * @brief check date if it is valid
	 */
	function validateDate(&$date)
	{
		$timestamp = strtotime($date);
		$date = date('Y-m-d', strtotime($date));
		return $timestamp ? true : false;
	}

	function procCympuserAdminAddOptions()
	{
		$group_list = Context::get('group_list');

		$args->group_srl = Context::get('group_srl');
		$args->option_srl = Context::get('option_srl');
		$args->column_type = Context::get('column_type');
		$args->column_name = strtolower(Context::get('column_name'));
		$args->column_title = Context::get('column_title');
		$args->default_value = explode("\n", str_replace("\r", '', Context::get('default_value')));
		$args->required = Context::get('required');
		$args->is_active = (isset($args->required));
		$args->description = Context::get('description');
		if(!in_array(strtoupper($args->required), array('Y','N')))
		{
			$args->required = 'N';
		}

		if($group_list && is_array($group_list))
		{
			$error = array();
			foreach($group_list as $val) 
			{
				$args->group_srl = $val;
				// check column_name
				if(!$args->option_srl && $this->checkColumnName($args->group_srl, $args->column_name)) 
				{
					$this->setMessage += "Error: group_srl $args->group_srl 에 $args->column_name 와 같은 이름 존재";
					continue;
				}	

				$output = $this->insertOptions($args);
				$args->option_srl = null;
				if(!$output->toBool()) return $error[] = $output;
			}

			if($error) debugprint($error);
		}
		else
		{
			// check column_name
			if(!$args->option_srl && $this->checkColumnName($args->group_srl, $args->column_name)) return new Object(-1, 'msg_invalid_column_name');

			$output = $this->insertOptions($args);
			if(!$output->toBool()) return $output;
		}

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) 
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCympuserAdminAddOptions');
			$this->setRedirectUrl($returnUrl);
			return;
		}
	}

	function checkColumnName($group_srl, $column_name)
	{
		// check in reserved keywords
		if(in_array($column_name, array('module','act','module_srl','category_id', 'document_srl','description', 'delivery_info', 'item_srl','proc_module','category_depth1','category_depth2','category_depth3','category_depth4','thumbnail_image','contents_file'))) return TRUE;
		// check in extra keys
		$args->column_name = $column_name;
		$args->group_srl = $group_srl;
		$output = executeQuery('cympuser.isExistsExtraKey', $args);
		if($output->data->count) return TRUE;
		return FALSE;
	}
	

	function insertOptions($args)
	{
		$oCympuserModel = &getModel('cympuser');

		// Default values
		if(in_array($args->column_type, array('checkbox','select','radio')) && count($args->default_value) ) 
		{
			$args->default_value = serialize($args->default_value);
		} 

		// Update if option_srl exists, otherwise insert.
		if(!$args->option_srl)
		{
			$args->list_order = $args->option_srl = getNextSequence();
			$output = executeQuery('cympuser.insertOption', $args);
			$this->setMessage('success_registed');
		}
		else
		{
			$output = executeQuery('cympuser.updateOption', $args);
			$this->setMessage('success_updated');
		}

		$options_list = $oCympuserModel->getCympuserOptionsList();
		$this->_createInsertMemberRuleset($options_list);

		return $output;
	}

	
	function procCympuserAdminUpdateOptionsOrder() 
	{
		$order = Context::get('order');
		parse_str($order);
		$idx = 1;
		if(!is_array($record)) return;

		foreach ($record as $option_srl) 
		{
			$args->option_srl = $option_srl;
			$args->list_order = $idx;
			$output = executeQuery('cympuser.updateOptionsOrder', $args);
			if(!$output->toBool()) return $output;
			$idx++;
		}
	}

	function procCympuserAdminUpdateOptions()
	{
		$args = Context::gets('option_srl', 'column_type', 'column_name', 'required', 'default_value', 'is_active', 'description');
		debugprint($args);
		$output = executeQuery('cympuser.updateOption', $args);
		debugprint($output);
		if(!$output->toBool()) return $output;

	}

	function procCympuserAdminDeleteOptions()
	{
		$oCympuserModel = &getModel('cympuser');
		$option_srl = Context::get('option_srl');
		if(!$option_srl) return new Object(-1, 'option_srl is null');

		$args->option_srl = $option_srl;
		$output = executeQuery('cympuser.deleteOption', $args);
		if(!$output->toBool()) return $output;

		$option_list = $oCympuserModel->getCympuserOptionsList();
		$this->_createInsertMemberRuleset($option_list);
		$this->setMessage('success_deleted');

		$success_return_url = Context::get("success_return_url");
		if($success_return_url) 
		{
			$this->setRedirectUrl($success_return_url);
			return;
		}
		$redirectUrl = getNotEncodedUrl('', 'module', Context::get('module'), 'act', 'dispCympuserAdminAddOptions');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * @brief create dynamic insert member ruleset
	 **/
	function _createInsertMemberRuleset($extra_vars)
	{
		//PHP_EOL = end of line 엔터키를 친 효과
		$xml_file = './files/ruleset/cympuser_insertMember.xml';
		$buff = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL
				.'<ruleset version="1.5.0">' . PHP_EOL
				.'  <customrules>' . PHP_EOL
				.'  </customrules>' . PHP_EOL
				.'  <fields>' . PHP_EOL . '%s' . PHP_EOL . '  </fields>' . PHP_EOL
				.'</ruleset>' . PHP_EOL;

		$fields = array();
		$fields[] = '    <field name="member_srl" required="true" />' . PHP_EOL;
		$fields[] = '    <field name="user_name" required="true" />' . PHP_EOL;
		$fields[] = '    <field name="sex" required="true" />' . PHP_EOL;
		$fields[] = '    <field name="is_lunar" required="false" />' . PHP_EOL;
		$fields[] = '    <field name="is_new" required="false" />' . PHP_EOL;
		
		if(count($extra_vars))
		{
			foreach($extra_vars as $formInfo){
				if($formInfo->required=='Y')
				{
					if($formInfo->column_type == 'tel' || $formInfo->type == 'kr_zip')
					{
						$fields[] = sprintf('    <field name="%s[]">"', $formInfo->column_name) . PHP_EOL;
						$fields[] = sprintf('      <if test="$group_srl == \'%s\'" attr="required" value="true" />', $formInfo->group_srl) . PHP_EOL;
						$fields[] = '    </field>' . PHP_EOL;
					}
					else if($formInfo->column_type == 'email_address')
					{
						$fields[] = sprintf('    <field name="%s" rule="email">', $formInfo->column_name) . PHP_EOL;
						$fields[] = sprintf('      <if test="$group_srl == \'%s\'" attr="required" value="true" />', $formInfo->group_srl) . PHP_EOL;
						$fields[] = '    </field>' . PHP_EOL;
					}
					else if($formInfo->column_type == 'user_id')
					{
						$fields[] = sprintf('    <field name="%s" rule="userid" length="3:20" >', $formInfo->column_name) . PHP_EOL;
						$fields[] = sprintf('      <if test="$group_srl == \'%s\'"  attr="required" value="true" />', $formInfo->group_srl) . PHP_EOL;
						$fields[] = '    </field>' . PHP_EOL;
					}
					else
					{
						$fields[] = sprintf('    <field name="%s" >', $formInfo->column_name) . PHP_EOL;
						$fields[] = sprintf('      <if test="$group_srl == \'%s\'" attr="required" value="true" />', $formInfo->group_srl) . PHP_EOL;
						$fields[] = '    </field>' . PHP_EOL;
					}
				}
			}
		}

		$xml_buff = sprintf($buff, implode('', $fields));
		FileHandler::writeFile($xml_file, $xml_buff);
		unset($xml_buff);

		$validator   = new Validator($xml_file);
		$validator->setCacheDir('files/cache');
		$validator->getJsPath();
	}
}
?>
