<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserModel
 * @author billy(contact@nurigo.net)
 * @brief  cympuserModel
 */
class cympuserModel extends cympuser 
{
	/**
	 * @brief constructor
	 */
	function init() 
	{
	}

	function triggerGetManagerMenu(&$manager_menu)
	{
		$oModuleModel = &getModel('module');

		$logged_info = Context::get('logged_info');
		$output = executeQueryArray('cympuser.getModInstList');
		if(!$output->toBool()) return $output;
		$list = $output->data;

		$menu = array();
		foreach($list as $key => $val)
		{
			$grant = $oModuleModel->getGrant($val, $logged_info);
			if($grant->manager) 
			{
				$submenu = new stdClass();
				$submenu->action = array('dispCympuserAdminMemberList');
				$submenu->mid = $val->mid;
				$submenu->title = Context::getLang('member_management');
				$submenu->module = 'cympuser';
				$manager_menu['cympusadmin']->submenu[] = $submenu;
			}
		}
	}

	function getCympuserOptionsList($group_srl=null, $is_active=false)
	{
		if(is_array($group_srl))
		{
			$args->group_srls = $group_srl;
		}
		else
		{
			$args->group_srl = $group_srl;
		}
		$args->is_active = $is_active;
		$output = executeQueryArray('cympuser.getOptionList', $args);
		if(!$output->toBool()) return $output;
		$options_list = $output->data;

		$check_duplicate = array();
		$list = array();
		foreach($options_list as &$val) 
		{
			//다중 group_srl 로 중복된 필드가 있을경우 패스
			if(in_array($val->column_name, $check_duplicate)) continue;

			if($val->default_value) $val->default_value = unserialize($val->default_value);
			$list[] = $val;
			$check_duplicate[] = $val->column_name;
		}
		return $list;
	}

	function getCympuserOptionInfo($group_srl)
	{
		$args->group_srl = $group_srl;
		$output = executeQuery('cympuser.getOptionInfo', $args);
		if(!$output->toBool()) return $output;

		$option_info = $output->data;
		return $option_info;
	}


}
