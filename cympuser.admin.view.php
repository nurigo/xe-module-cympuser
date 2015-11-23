<?php
/**
 * vi:set sw=4 ts=4 noexpandtab fileencoding=utf8:
 * @class  cympuserAdminView
 * @author billy(contact@nurigo.net)
 * @brief  cympuserAdminView
 */ 
class cympuserAdminView extends cympuser 
{

	function init() 
	{
		// module이 cympusadmin일때 관리자 레이아웃으로
        if(Context::get('module')=='cympusadmin')
        {
            $classfile = _XE_PATH_.'modules/cympusadmin/cympusadmin.class.php';
            if(file_exists($classfile))
            {
                    require_once($classfile);
                    cympusadmin::init();
            }
        }
		
		// module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
		$module_srl = Context::get('module_srl');
		if(!$module_srl && $this->module_srl)
		{
			$module_srl = $this->module_srl;
			Context::set('module_srl', $module_srl);
		}

		$oModuleModel = &getModel('module');

		// module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
		if($module_srl) 
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if(!$module_info) 
			{
				Context::set('module_srl','');
				$this->act = 'list';
			} else {
				ModuleModel::syncModuleToSite($module_info);
				$this->module_info = $module_info;
				Context::set('module_info',$module_info);
			}
		}

		// 가져온 모듈정보가 cympuser 가 아니면 에러쳐리
		if($module_info && !in_array($module_info->module, array('cympuser')))
			return $this->stop("msg_invalid_request");

		// set template file
		$tpl_path = $this->module_path.'tpl';
		$this->setTemplatePath($tpl_path);
		$this->setTemplateFile('member_list');
		Context::set('tpl_path', $tpl_path);
	}

	/**
	 * @brief display mod instance list page
	 *
	 */
	function dispCympuserAdminModList()
	{
		$args->sort_index = "module_srl";
		$args->page = Context::get('page');
		$args->list_count = 20;
		$args->page_count = 10;
		$output = executeQueryArray('cympuser.getModuleList', $args);
		if(!$output->toBool()) return $output;


		// 템플릿에 쓰기 위해서 context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		// 템플릿 파일 지정
		$this->setTemplateFile('mod_list');
	}

	/**
	 * @brief display mod instance insert page
	 *
	 */
	function dispCympuserAdminModInsert()
	{
		// 스킨 목록을 구해옴
		$oModuleModel = &getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list',$skin_list);

		// 레이아웃 목록을 구해옴
		$oLayoutMode = &getModel('layout');
		$layout_list = $oLayoutMode->getLayoutList();
		Context::set('layout_list', $layout_list);

		$module_srl = Context::get('module_srl');
		if($module_srl)
		{
			$args->module_srl = $module_srl;
			$output = executeQuery('cympuser.getModuleInfo', $args);
			if(!$output->toBool()) return $output;
			Context::set('module_info', $output->data);
		}
		$this->setTemplateFile('mod_insert');
	}

	function dispCympuserAdminManageEnrollment()
	{
	}

	/**
	 * @brief display member list page
	 *
	 */
	function dispCympuserAdminMemberList()
	{
		$module = Context::get('module');
		if($module == "admin") {
			Context::set("module", "cympusadmin");
			$this->dispCympuserAdminMemberList();
		}

		$classfile = _XE_PATH_.'modules/cympusadmin/cympusadmin.class.php';
		if(file_exists($classfile))
		{
				require_once($classfile);
				$output = cympusadmin::init();
				if(!$output->toBool()) return $output;
		}

		$oCympuserAdminModel = getAdminModel('cympuser');
		$oMemberModel = getModel('member');
		$output = $oCympuserAdminModel->getCympuserAdminMemberList();

		$filter = Context::get('filter_type');
		global $lang;
		switch($filter)
		{
		case 'super_admin' : 
			Context::set('filter_type_title', $lang->cmd_show_super_admin_member);
			break;
		case 'site_admin' : 
			Context::set('filter_type_title', $lang->cmd_show_site_admin_member);
			break;
		default : 
			Context::set('filter_type_title', $lang->cmd_show_all_member);
			break;
		}
		// retrieve list of groups for each member
		if($output->data)
		{
			foreach($output->data as $key => $member)
			{
				$output->data[$key]->group_list = $oMemberModel->getMemberGroups($member->member_srl,0);
			}
		}

		$oMemberModel = getModel('member');
		$this->memberConfig = $oMemberModel->getMemberConfig();
		Context::set('config', $this->memberConfig);
		$oSecurity = new Security();
		$oSecurity->encodeHTML('config.signupForm..');

		// if member_srl exists, set memberInfo
		$member_srl = Context::get('member_srl');
		if($member_srl) 
		{
			$this->memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			if(!$this->memberInfo)
			{
				Context::set('member_srl','');
			}
			else
			{
				Context::set('member_info',$this->memberInfo);
			}
		}

		$config = $oMemberModel->getMemberConfig();
		$memberIdentifiers = array('user_id'=>'user_id', 'user_name'=>'user_name', 'nick_name'=>'nick_name');
		$usedIds = array();	

		if(is_array($config->signupForm))
		{
			foreach($config->signupForm as $signupItem)
			{
				if(!count($memberIdentifiers)) break;
				if(in_array($signupItem->name, $memberIdentifiers) 
					&& ($signupItem->required || $signupItem->isUse))
				{
					unset($memberIdentifiers[$signupItem->name]);
					$usedIds[$signupItem->name] = $lang->{$signupItem->name};
				}
			}
		}
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('member_list', $output->data);
		Context::set('usedIdentifiers', $usedIds);
		Context::set('page_navigation', $output->page_navigation);

		$security = new Security();
		$security->encodeHTML('member_list..user_name', 'member_list..nick_name', 'member_list..group_list..');

		$this->setTemplateFile('member_list');
	}

	/* cympuser 기본정보 페이지 */
	function dispCympuserAdminMemberInfo()
	{
		$member_srl = Context::get('member_srl');
		$input = new stdClass();
		$input->member_srl = $member_srl;
		$output = ModuleHandler::triggerCall('cympuser.getMemberInfo', 'before', $input);
		if(!$output->toBool()) return $output;
		if($input->infos['profile']->member_info) 
		{
			$profile = $input->infos['profile']->member_info;
			Context::set('profile', $profile);
		}

		$this->setTemplateFile('member_info2');
	}

	/* cympuser 계정정보 페이지 */
	function dispCympuserAdminAccountInfo()
	{
		$member_srl = Context::get('member_srl');
		$input = new stdClass();
		$input->member_srl = $member_srl;
		$output = ModuleHandler::triggerCall('cympuser.getAccountInfo', 'before', $input);
		if(!$output->toBool()) return $output;
		debugprint($input);
		if($input->infos['profile']->account_info) 
		{
			$account = $input->infos['profile']->account_info;
			Context::set('account', $account);
		}

		$this->setTemplateFile('account_info');
	}

	/* cympuser 생활관리 페이지 */
	function dispCympuserAdminGuidanceInfo()
	{
		$member_srl = Context::get('member_srl');
		$input = new stdClass();
		$input->member_srl = $member_srl;
		$output = ModuleHandler::triggerCall('cympuser.getGuidanceInfo', 'before', $input);
		if(!$output->toBool()) return $output;
		debugprint($input);
		if($input->infos['profile']->guidance_info) 
		{
			$guidance = $input->infos['profile']->guidance_info;
			Context::set('guidance', $guidance);
		}

		$this->setTemplateFile('guidance_info');
	}

	/**
	 * display member insert form
	 * @return void
	 */
	function dispCympuserAdminMemberInsert()
	{
		// retrieve extend form
		$oMemberModel = getModel('member');
		$oCympuserAdminModel = getAdminModel('cympuser');
		$oElearning = getClass('elearning');
		$oNstore = getClass('nstore');
		$oEpay = getClass('epay');
		$oMember = getClass('member');

		// if member_srl exists, set memberInfo
		$member_srl = Context::get('member_srl');
		if($member_srl) 
		{
			$this->memberInfo = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			$memberInfo = $this->memberInfo;

			$args->member_srl = $member_srl;
			// if elearning module is installed
			if($oElearning)
			{
				$page = Context::get('class_page');
				$query = 'elearning.getMyClasses';
				$output = executeQueryArray($query, $args);
				if(!$output->toBool()) return $output;

				Context::set('is_elearning_exists', 'Y');
				Context::set('class_list', $output->data);
				unset($args);
			}

			// if nstore module is installed
			if($oNstore)
			{
				$oNstorePath = ModuleHandler::getModulePath('nstore');
				$args->member_srl = $member_srl;
				$order_status = Context::get('order_status');
				$args->order_status = $order_status;
				$args->page = Context::get('textbook_page');
				$args->list_count = 5;
				$query = 'nstore.getOrderListByStatus';
				$output = executeQueryArray($query, $args);
				if(!$output->toBool()) return $output;

				$member_config = $oMemberModel->getMemberConfig();
				$memberIdentifiers = array('user_id'=>'user_id', 'user_name'=>'user_name', 'nick_name'=>'nick_name');
				$usedIdentifiers = array();	

				if(is_array($member_config->signupForm))
				{
					foreach($member_config->signupForm as $signupItem)
					{
						if(!count($memberIdentifiers)) break;
						if(in_array($signupItem->name, $memberIdentifiers) && ($signupItem->required || $signupItem->isUse))
						{
							unset($memberIdentifiers[$signupItem->name]);
							$usedIdentifiers[$signupItem->name] = $lang->{$signupItem->name};
						}
					}
				}
				Context::set('order_list', $output->data);
				Context::set('is_nstore_exists', 'Y');
				Context::set('list', $order_list);
				Context::set('total_count', $output->total_count);
				Context::set('total_page', $output->total_page);
				Context::set('textbook_page', $output->page);
				Context::set('textbook_page_navigation', $output->page_navigation);
				Context::set('delivery_companies', $oNstore->delivery_companies);
				Context::set('order_status', $oNstore->getOrderStatus());
				Context::set('delivery_inquiry_urls', $oNstore->delivery_inquiry_urls);
				Context::set('usedIdentifiers', $usedIdentifiers);

				unset($args);
			}

			// if epay module is installed
			if($oEpay)
			{
				// transactions
				$args->member_srl = $member_srl;
				$args->page = Context::get('epay_page');
				$args->list_count = 5;
				if(Context::get('search_key'))
				{
					$search_key = Context::get('search_key');
					$search_value = Context::get('search_value');
					$args->{$search_key} = $search_value;
				}
				$output = executeQueryArray('epay.getTransactionByMemberSrl',$args);
				if(!$output->toBool()) return $output;
				$epay_list = $output->data;
				Context::set('epay_list', $epay_list);
				Context::set('is_epay_exists', 'Y');
				Context::set('total_count', $output->total_count);
				Context::set('total_page', $output->total_page);
				Context::set('epay_page', $output->page);
				Context::set('epay_page_navigation', $output->page_navigation);

			}

			if(!$output->toBool()) return $output;

			if(!$this->memberInfo)
			{
				Context::set('member_srl','');
			}
			else
			{
				Context::set('member_srl',$member_srl);
				Context::set('member_info',$this->memberInfo);
			}
		}

		if(isset($memberInfo))
		{
			$member_srl = $this->memberInfo->member_srl;
			$signature = $oMemberModel->getSignature($member_srl);
			$memberInfo->signature = $signature;
		}
		Context::set('member_info', $memberInfo);

		// get an editor for the signature
		if($memberInfo->member_srl)
		{
			$oEditorModel = getModel('editor');
			$option = new stdClass();
			$option->primary_key_name = 'member_srl';
			$option->content_key_name = 'signature';
			$option->allow_fileupload = false;
			$option->enable_autosave = false;
			$option->enable_default_component = true;
			$option->enable_component = false;
			$option->resizable = false;
			$option->height = 200;
			$editor = $oEditorModel->getEditor($member_srl, $option);
			Context::set('editor', $editor);
		}

		$formTags = $this->_getMemberInputTag($memberInfo, true);
		Context::set('formTags', $formTags);
		$member_config = $this->memberConfig;

		global $lang;
		$identifierForm = new stdClass();
		$identifierForm->title = $lang->{$member_config->identifier};
		$identifierForm->name = $member_config->identifier;
		$identifierForm->value = $memberInfo->{$member_config->identifier};
		Context::set('identifierForm', $identifierForm);
		$this->setTemplateFile('member_info');
	}

	/**
	 * Get tags by the member info type 
	 *
	 * @param object $memberInfo
	 * @param boolean $isAdmin (true : admin, false : not admin)
	 *
	 * @return array
	 */
	function _getMemberInputTag($memberInfo, $isAdmin = false)
	{
		$oMemberModel = getModel('member');
		$extend_form_list = $oMemberModel->getCombineJoinForm($memberInfo);
		$security = new Security($extend_form_list);
		$security->encodeHTML('..column_title', '..description', '..default_value.');

		if ($memberInfo)
		{
			$memberInfo = get_object_vars($memberInfo);
		}

		$member_config = $this->memberConfig;
		if(!$this->memberConfig)
		{
			$member_config = $this->memberConfig = $oMemberModel->getMemberConfig();
		}

		$formTags = array();
		global $lang;

		foreach($member_config->signupForm as $no=>$formInfo)
		{
			if(!$formInfo->isUse)continue;
			if($formInfo->name == $member_config->identifier || $formInfo->name == 'password') continue;
			$formTag = new stdClass();
			$inputTag = '';
			$formTag->title = ($formInfo->isDefaultForm) ? $lang->{$formInfo->name} : $formInfo->title;
			if($isAdmin)
			{
				if($formInfo->mustRequired) $formTag->title = '<em style="color:red">*</em> '.$formTag->title;
			}
			else
			{
				if ($formInfo->required && $formInfo->name != 'password') $formTag->title = '<em style="color:red">*</em> '.$formTag->title;
			}
			$formTag->name = $formInfo->name;

			if($formInfo->isDefaultForm)
			{
				if($formInfo->imageType)
				{
					$formTag->type = 'image';
					if($formInfo->name == 'profile_image')
					{
						$target = $memberInfo['profile_image'];
						$functionName = 'doDeleteProfileImage';
					}
					else if($formInfo->name == 'image_name')
					{
						$target = $memberInfo['image_name'];
						$functionName = 'doDeleteImageName';
					}
					else if($formInfo->name == 'image_mark')
					{
						$target = $memberInfo['image_mark'];
						$functionName = 'doDeleteImageMark';
					}

					if($target->src)
					{
						$inputTag = sprintf('<input type="hidden" name="__%s_exist" value="true" /><span id="%s"><img src="%s" alt="%s" /> <button type="button" onclick="%s(%d);return false;">%s</button></span>',
							$formInfo->name,
							$formInfo->name.'tag',
							$target->src,
							$formInfo->title,
							$functionName,
							$memberInfo['member_srl'],
							$lang->cmd_delete);
					}
					else
					{
						$inputTag = sprintf('<input type="hidden" name="__%s_exist" value="false" />', $formInfo->name);
					}
					$inputTag .= sprintf('<input type="file" name="%s" id="%s" value="" accept="image/*" /><p class="help-block">%s: %dpx, %s: %dpx</p>',
						$formInfo->name,
						$formInfo->name,
						$lang->{$formInfo->name.'_max_width'},
						$member_config->{$formInfo->name.'_max_width'},
						$lang->{$formInfo->name.'_max_height'},
						$member_config->{$formInfo->name.'_max_height'});
					}//end imageType
					else if($formInfo->name == 'birthday')
					{
						$formTag->type = 'date';
						$inputTag = sprintf('<input type="hidden" name="birthday" id="date_birthday" value="%s" /><input type="text" placeholder="YYYY-MM-DD" name="birthday_ui" class="inputDate" id="birthday" value="%s" readonly="readonly" /> <input type="button" value="%s" class="btn dateRemover" />',
							$memberInfo['birthday'],
							zdate($memberInfo['birthday'], 'Y-m-d', false),
							$lang->cmd_delete);
					}
					else if($formInfo->name == 'find_account_question')
					{
						$formTag->type = 'select';
						$inputTag = '<select name="find_account_question" id="find_account_question" style="display:block;margin:0 0 8px 0">%s</select>';
						$optionTag = array();
						foreach($lang->find_account_question_items as $key=>$val)
						{
							if($key == $memberInfo['find_account_question']) $selected = 'selected="selected"';
							else $selected = '';
							$optionTag[] = sprintf('<option value="%s" %s >%s</option>',
								$key,
								$selected,
								$val);
						}
						$inputTag = sprintf($inputTag, implode('', $optionTag));
						$inputTag .= '<input type="text" name="find_account_answer" id="find_account_answer" title="'.Context::getLang('find_account_answer').'" value="'.$memberInfo['find_account_answer'].'" />';
					}
					else if($formInfo->name == 'email_address')
					{
						$formTag->type = 'email';
						$inputTag = '<input type="email" name="email_address" id="email_address" value="'.$memberInfo['email_address'].'" />';
					}
					else if($formInfo->name == 'homepage')
					{
						$formTag->type = 'url';
						$inputTag = '<input type="url" name="homepage" id="homepage" value="'.$memberInfo['homepage'].'" />';
					}
					else if($formInfo->name == 'blog')
					{
						$formTag->type = 'url';
						$inputTag = '<input type="url" name="blog" id="blog" value="'.$memberInfo['blog'].'" />';
					}
					else
					{
						$formTag->type = 'text';
						$inputTag = sprintf('<input type="text" name="%s" id="%s" value="%s" />',
							$formInfo->name,
							$formInfo->name,
							$memberInfo[$formInfo->name]);
					}
				}//end isDefaultForm
				else
				{
					$extendForm = $extend_form_list[$formInfo->member_join_form_srl];
					$replace = array('column_name' => $extendForm->column_name, 'value' => $extendForm->value);
					$extentionReplace = array();

					$formTag->type = $extendForm->column_type;
					if($extendForm->column_type == 'text')
					{
						$template = '<input type="text" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'homepage')
					{
						$template = '<input type="url" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'email_address')
					{
						$template = '<input type="email" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'tel')
					{
						$extentionReplace = array('tel_0' => $extendForm->value[0],
							'tel_1' => $extendForm->value[1],
							'tel_2' => $extendForm->value[2]);
						$template = '<input type="tel" name="%column_name%[]" id="%column_name%" value="%tel_0%" size="4" maxlength="4" style="width:30px" title="First Number" /> - <input type="tel" name="%column_name%[]" value="%tel_1%" size="4" maxlength="4" style="width:35px" title="Second Number" /> - <input type="tel" name="%column_name%[]" value="%tel_2%" size="4" maxlength="4" style="width:35px" title="Third Number" />';
					}
					else if($extendForm->column_type == 'textarea')
					{
						$template = '<textarea name="%column_name%" id="%column_name%" rows="4" cols="42">%value%</textarea>';
					}
					else if($extendForm->column_type == 'checkbox')
					{
						$template = '';
						if($extendForm->default_value)
						{
							$template = '<div style="padding-top:5px">%s</div>';
							$__i = 0;
							$optionTag = array();
							foreach($extendForm->default_value as $v)
							{
								$checked = '';
								if(is_array($extendForm->value) && in_array($v, $extendForm->value))$checked = 'checked="checked"';
								$optionTag[] = '<label for="%column_name%'.$__i.'"><input type="checkbox" id="%column_name%'.$__i.'" name="%column_name%[]" value="'.$v.'" '.$checked.' /> '.$v.'</label>';
								$__i++;
							}
							$template = sprintf($template, implode('', $optionTag));
						}
					}
					else if($extendForm->column_type == 'radio')
					{
						$template = '';
						if($extendForm->default_value)
						{
							$template = '<div style="padding-top:5px">%s</div>';
							$optionTag = array();
							foreach($extendForm->default_value as $v)
							{
								if($extendForm->value == $v)$checked = 'checked="checked"';
								else $checked = '';
								$optionTag[] = '<label><input type="radio" name="%column_name%" value="'.$v.'" '.$checked.' /> '.$v.'</label>';
							}
							$template = sprintf($template, implode('', $optionTag));
						}
					}
					else if($extendForm->column_type == 'select')
					{
						$template = '<select name="'.$formInfo->name.'" id="'.$formInfo->name.'">%s</select>';
						$optionTag = array();
						$optionTag[] = sprintf('<option value="">%s</option>', $lang->cmd_select);
						if($extendForm->default_value)
						{
							foreach($extendForm->default_value as $v)
							{
								if($v == $extendForm->value) $selected = 'selected="selected"';
								else $selected = '';
								$optionTag[] = sprintf('<option value="%s" %s >%s</option>', $v, $selected, $v);
							}
						}
						$template = sprintf($template, implode('', $optionTag));
					}
					else if($extendForm->column_type == 'kr_zip')
					{
						$krzipModel = getModel('krzip');
						if($krzipModel && method_exists($krzipModel , 'getKrzipCodeSearchHtml' ))
						{
							$template = $krzipModel->getKrzipCodeSearchHtml($extendForm->column_name, $extendForm->value);
						}
					}
					else if($extendForm->column_type == 'jp_zip')
					{
						$template = '<input type="text" name="%column_name%" id="%column_name%" value="%value%" />';
					}
					else if($extendForm->column_type == 'date')
					{
						$extentionReplace = array('date' => zdate($extendForm->value, 'Y-m-d'), 'cmd_delete' => $lang->cmd_delete);
						$template = '<input type="hidden" name="%column_name%" id="date_%column_name%" value="%value%" /><input type="text" placeholder="YYYY-MM-DD" class="inputDate" value="%date%" readonly="readonly" /> <input type="button" value="%cmd_delete%" class="btn dateRemover" />';
					}

					$replace = array_merge($extentionReplace, $replace);
					$inputTag = preg_replace('@%(\w+)%@e', '$replace[$1]', $template);

					if($extendForm->description)
						$inputTag .= '<p class="help-block">'.$extendForm->description.'</p>';
				}
				$formTag->inputTag = $inputTag;
				$formTags[] = $formTag;
		}
		return $formTags;
	}

	/**
	 * @brief display grant info managing page
	 *
	 */
	function dispCympuserAdminGrantInfo()
	{
		// get the grant infotmation from admin module
		$oModuleAdminModel = &getAdminModel('module');
		$module_srl = Context::get('module_srl');
		$grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('grantinfo');

	}
}
