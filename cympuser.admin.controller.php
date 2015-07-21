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

		if($target == 'elearning')
		{
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

			if($error)
			{
				$this->setMessage($error . "이미 수강하고 있는 강좌입니다." , 'error');
				$this->setRedirectUrl($success_return_url);
				return;
			}

		}

		if($target == 'nstore')
		{
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
				/*
				$cartitem_args->period = $oNproductModel->getItemExtraVarValue($item_srl, 'period');
				$cartitem_args->period_unit = $oNproductModel->getItemExtraVarValue($item_srl, 'period_unit');
				$cartitem_args->period_days = (strtotime($cartitem_args->period . ' ' . $cartitem_args->period_unit, time()) - time()) / (60 * 60 * 24);
				$cartitem_args->order_status = '2';
				$cartitem_args->startdate = date('Ymd').'000000';
				$current_time = time() - (60 * 60 * 24 * 1); // 미리 하루를 빼주지 않으면 +2일이 주어진다. 이 상태로는 +1일 보너스로 주어진다.
				$cartitem_args->enddate = date('Ymd', strtotime($cartitem_args->period_days . ' days', $current_time)) . '235959';
				//if($cartitem_args->enddate > $freepassInfo->enddate) $cartitem_args->enddate = $freepassInfo->enddate;
				if($config->extra_days) $cartitem_args->period_days += $config->extra_days;
				 */
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

			if ($error)
			{
				$this->setMessage($error . "에러가 발생하였습니다." , 'error');
				$this->setRedirectUrl($success_return_url);
				return;
			}
		}

		$this->setMessage($message .'추가되었습니다.');
		$this->setRedirectUrl($success_return_url);
	}

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

		debugprint($value);
		if(!$this->validateDate($value)) return new Object(-1, 'date is not valid.');
		debugprint($value);
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

	function validateDate(&$date)
	{
		$timestamp = strtotime($date);
		$date = date('Y-m-d', strtotime($date));
		debugprint($timestamp);
		debugprint($date);
		return $timestamp ? true : false;
	}
}
?>
