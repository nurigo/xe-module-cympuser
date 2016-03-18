<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserAdminModel
 * @author billy(contact@nurigo.net)
 * @brief  cympuserAdminModel
 */
class cympuserAdminModel extends cympuser 
{

	/**
	 * @brief get teamplate for deleting cympuser module instance
	 */
	function getCympuserAdminDelete() 
	{
		// get configs.
		$args->module_srl = Context::get('module_srl');
		$output = executeQueryArray("cympuser.getModuleInfo", $args);
		if(!$output->toBool()) return $output;
		
		Context::set('module_info', $output->data);

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'delete');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}

	
	/**
	 * Get a member list
	 * 
	 * @return object|array (object : when member count is 1, array : when member count is more than 1)
	 */
	function getCympuserAdminMemberList()
	{
		// Search option
		$args = new stdClass();
		$args->is_admin = Context::get('is_admin')=='Y'?'Y':'';
		$args->is_denied = Context::get('is_denied')=='Y'?'Y':'';
		$args->selected_group_srl = Context::get('selected_group_srl');

		$filter = Context::get('filter_type');
		switch($filter)
		{
			case 'super_admin' : $args->is_admin = 'Y';break;
			case 'site_admin' : $args->member_srls = $this->getSiteAdminMemberSrls();break;
			case 'enable' : $args->is_denied = 'N';break;
			case 'disable' : $args->is_denied = 'Y';break;
		}

		$search_target = trim(Context::get('search_target'));
		$search_keyword = trim(Context::get('search_keyword'));

		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'user_id' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_id = $search_keyword;
					break;
				case 'user_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_user_name = $search_keyword;
					break;
				case 'nick_name' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_nick_name = $search_keyword;
					$args->html_nick_name = htmlspecialchars($search_keyword, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
					break;
				case 'email_address' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_email_address = $search_keyword;
					break;
				case 'regdate' :
					$args->s_regdate = preg_replace("/[^0-9]/","",$search_keyword);
					break;
				case 'regdate_more' :
					$args->s_regdate_more = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'regdate_less' :
					$args->s_regdate_less = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login' :
					$args->s_last_login = preg_replace("/[^0-9]/","",$search_keyword);
					//$args->s_last_login = $search_keyword;
					break;
				case 'last_login_more' :
					$args->s_last_login_more = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login_less' :
					$args->s_last_login_less = substr(preg_replace("/[^0-9]/","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'birthday' :
					$args->s_birthday = preg_replace("/[^0-9]/","",$search_keyword);
					break;
				case 'extra_vars' :
					$args->s_extra_vars = $search_keyword;
					break;
			}
		}

		// Change the query id if selected_group_srl exists (for table join)
		$sort_order = Context::get('sort_order');
		$sort_index = Context::get('sort_index');
		if(!$sort_index)
		{
			$sort_index = "list_order";
		}

		if(!$sort_order)
		{
			$sort_order = 'asc';
		}

		if($sort_order != 'asc')
		{
			$sort_order = 'desc';
		}

		if($args->selected_group_srl)
		{
			$query_id = 'member.getMemberListWithinGroup';
			$args->sort_index = "member.".$sort_index;
		}
		else
		{
			$query_id = 'member.getMemberList';
			$args->sort_index = $sort_index; 
		}

		$args->sort_order = $sort_order;
		Context::set('sort_order', $sort_order);
		// Other variables
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$output = executeQuery($query_id, $args);

		return $output;
	}

	/**
	 * @brief  get cmpuser adminlist
	 */
	function getCympuserAdminItemList()
	{
		$oNproductModel = &getModel('nproduct');

		$member_srl = Context::get('member_srl');
		$target = Context::get('target');
		if(!$target) $target = 'nstore';

		$args->proc_module = $target;
		$output = executeQueryArray('cympuser.getItemList', $args);
		if(!$output->toBool()) return $output;

		$item_list = $output->data;
		$retobj = $oNproductModel->discountItems($item_list);

		debugprint($item_list);
		$list_config = $oNproductModel->getListConfig(null);
		Context::set('list', $item_list);
		Context::set('target', $target);
		Context::set('member_srl', $member_srl);

		$path = $this->module_path."tpl/".$module_info->skin;
		$file_name = "addOrderForm.html";
		$oTemplate = &TemplateHandler::getInstance();
		$data = $oTemplate->compile($path, $file_name);
		$this->add('tpl', $data);

	}

	function getCympuserAdminInsertOptions()
	{
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups();
		Context::set('group_list', $group_list);

		$group_srl = Context::get('group_srl');
		$option_srl = Context::get('option_srl');
		if($option_srl)
		{
			$args->option_srl = $option_srl;
			$output = executeQuery('cympuser.getOptionInfo', $args);

			if($output->toBool() && $output->data)
			{
				$formInfo = $output->data;
				$default_value = $formInfo->default_value;
				if($default_value)
				{
					$default_value = unserialize($default_value);
					Context::set('default_value', $default_value);
				}
				Context::set('formInfo', $output->data);
			}
		}

		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'form_insert_item_extra');
		$this->add('tpl', str_replace("\n"," ",$tpl));
	}
}
?>
