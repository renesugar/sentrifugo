<?php
/********************************************************************************* 
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2014 Sapplica
 *   
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_EmployeeController extends Zend_Controller_Action
{

	private $options;
	public function preDispatch()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('makeactiveinactive', 'json')->initContext();
		$ajaxContext->addActionContext('getemprequi', 'json')->initContext();
		$ajaxContext->addActionContext('getempreportingmanagers', 'html')->initContext();
	}

	public function init()
	{
		$employeeModel = new Default_Model_Employee();
		$this->_options= $this->getInvokeArg('bootstrap')->getOptions();
	}
	public function getemprequiAction()
	{
		$cand_id = $this->_getParam('cand_id',null);
		$cand_model = new Default_Model_Candidatedetails();
		$cand_data = $cand_model->getCandidateById($cand_id);
		$this->_helper->json(array('requi_code'=>$cand_data['requisition_code']));
	}
	public function indexAction()
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		//$this->view->message = 'This is bgscreening page';
		$employeeModel = new Default_Model_Employee();

		$call = $this->_getParam('call');
		if($call == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$view = Zend_Layout::getMvcInstance()->getView();
		$objname = $this->_getParam('objname');
		$refresh = $this->_getParam('refresh');
		$dashboardcall = $this->_getParam('dashboardcall',null);
		$data = array();$id='';
		$searchQuery = '';
		$searchArray = array();
		$tablecontent = '';
		if($refresh == 'refresh')
		{
			if($dashboardcall == 'Yes')
			$perPage = DASHBOARD_PERPAGE;
			else
			$perPage = PERPAGE;

			$sort = 'DESC';$by = 'e.modifieddate';$pageNo = 1;$searchData = '';
			$searchQuery = '';$searchArray='';
		}
		else
		{
			$sort = ($this->_getParam('sort') !='')? $this->_getParam('sort'):'DESC';
			$by = ($this->_getParam('by')!='')? $this->_getParam('by'):'e.modifieddate';

			if($dashboardcall == 'Yes')
			$perPage = $this->_getParam('per_page',DASHBOARD_PERPAGE);
			else
			$perPage = $this->_getParam('per_page',PERPAGE);

			$pageNo = $this->_getParam('page', 1);
			$searchData = $this->_getParam('searchData');
			$searchData = rtrim($searchData,',');

		}
		$dataTmp = $employeeModel->getGrid($sort, $by, $perPage, $pageNo, $searchData,$call,$dashboardcall,$loginUserId);

		array_push($data,$dataTmp);
		$this->view->dataArray = $data;
		$this->view->call = $call;
		$this->view->messages = $this->_helper->flashMessenger->getMessages();
	}

	public function addAction()
	{

		$emptyFlag=0;
		$report_opt = array();
		$popConfigPermission = array();
		$auth = Zend_Auth::getInstance();

		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		if(sapp_Global::_checkprivileges(PREFIX,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'prefix');
		}
		if(sapp_Global::_checkprivileges(EMPLOYMENTSTATUS,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'empstatus');
		}
		if(sapp_Global::_checkprivileges(JOBTITLES,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'jobtitles');
		}
		if(sapp_Global::_checkprivileges(POSITIONS,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'position');
		}
		$this->view->popConfigPermission = $popConfigPermission;
		
		$employeeform = new Default_Form_employee();
		$employeeModal = new Default_Model_Employee();
		$usersModel = new Default_Model_Users();
		$employmentstatusModel = new Default_Model_Employmentstatus();
		$busineesUnitModel = new Default_Model_Businessunits();
		$user_model = new Default_Model_Usermanagement();
		$candidate_model = new Default_Model_Candidatedetails();
		$role_model = new Default_Model_Roles();
		$deptModel = new Default_Model_Departments();
		$jobtitlesModel = new Default_Model_Jobtitles();
		$prefixModel = new Default_Model_Prefix();
		$msgarray = array();
		$identity_code_model = new Default_Model_Identitycodes();
		$identity_codes = $identity_code_model->getIdentitycodesRecord();
		$employeeData = $usersModel->getUsers();

		$emp_identity_code = isset($identity_codes[0])?$identity_codes[0]['employee_code']:"";
		if($emp_identity_code!='')
		{
			$emp_id = $emp_identity_code."-".str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
		}
		else
		{
			$emp_id = '';
			$msgarray['employeeId'] = 'Identity codes are not configured yet.';
		}

		$employeeform->employeeId->setValue($emp_id);
		$employeeform->modeofentry->setValue('Direct');

		$roles_arr = $role_model->getRolesList_EMP();

		if(sizeof($roles_arr) > 0)
		{
			$employeeform->emprole->addMultiOptions(array(''=>'Select Role')+$roles_arr);
		}
		else
		{
			$msgarray['emprole'] = 'Roles are not added yet.';
		}
		$candidate_options = $candidate_model->getCandidatesNamesForUsers();
		if(sizeof($candidate_options) > 0)
		{
			$employeeform->rccandidatename->addMultiOptions(array('' => 'Select Candidate')+$candidate_options);
		}
		else
		{
			$msgarray['rccandidatename'] = 'No candidates.';
		}
		$referedby_options = $user_model->getRefferedByForUsers();

		if(sizeof($referedby_options) > 0)
		{
			$employeeform->candidatereferredby->addMultiOptions(array(''=>'Select referred by')+$referedby_options);
		}
		else
		{
			$msgarray['candidatereferredby'] = 'Employees are not added yet.';
		}
			
		/*
		 Purpose:	While adding an employee employment status should not be left,resigned,suspended..
		 Modified Date:	11/06/2013
		 Modified By:	Yamini.
			*/
		//$employmentStatusData = $employmentstatusModel->getempstatuslist();
		$employmentStatusData = $employmentstatusModel->getempstatusActivelist();
		$employeeform->emp_status_id->addMultiOption('','Select a Employment Status');
		if(!empty($employmentStatusData))
		{
			 
			foreach ($employmentStatusData as $employmentStatusres){
				$employeeform->emp_status_id->addMultiOption($employmentStatusres['workcodename'],$employmentStatusres['statusname']);
			}
		}
		else
		{
			//$systempreferenceform->dateformatid->addMultiOption('','First create a dateformat in Dateformat settings');
			$msgarray['emp_status_id'] = 'Employment status is not configured yet.';
			$emptyFlag++;
		}

		$businessunitData = $busineesUnitModel->getDeparmentList();
		//print_r($businessunitData);
		if(!empty($businessunitData))
		{
			$employeeform->businessunit_id->addMultiOption('0','No Business Unit');
			foreach ($businessunitData as $businessunitres){
				$employeeform->businessunit_id->addMultiOption($businessunitres['id'],$businessunitres['unitname']);
			}

			$departmentsmodel = new Default_Model_Departments();
			$departmentlistArr = $departmentsmodel->getDepartmentList(0);
			$employeeform->department_id->clearMultiOptions();
			$employeeform->department_id->addMultiOption('','Select Department');
			if(count($departmentlistArr) > 0)
			{
				foreach($departmentlistArr as $departmentlistresult)
				{
					$employeeform->department_id->addMultiOption($departmentlistresult['id'],utf8_encode($departmentlistresult['deptname']));
				}
			}
			else
			{
				$msgarray['department_id'] = 'Departments are not added yet.';
			}
			if(isset($department_id) && $department_id != 0 && $department_id != '')
			$employeeform->setDefault('department_id',$department_id);
		}
		else
		{
			$msgarray['businessunit_id'] = 'Business units are not added yet.';
			$emptyFlag++;
		}

		$jobtitleData = $jobtitlesModel->getJobTitleList();
		$employeeform->jobtitle_id->addMultiOption('','Select a Job Title');
		if(!empty($jobtitleData))
		{
			foreach ($jobtitleData as $jobtitleres)
			{
				$employeeform->jobtitle_id->addMultiOption($jobtitleres['id'],$jobtitleres['jobtitlename']);
			}
		}
		else
		{
			$msgarray['jobtitle_id'] = 'Job titles are not configured yet.';
			$msgarray['position_id'] = 'Positions are not configured yet.';
			$emptyFlag++;
		}
			
		$prefixData = $prefixModel->getPrefixList();
		//echo "<pre>";print_r($prefixData);die;
		$employeeform->prefix_id->addMultiOption('','Select Prefix');
		if(!empty($prefixData))
		{
			foreach ($prefixData as $prefixres){
				$employeeform->prefix_id->addMultiOption($prefixres['id'],$prefixres['prefix']);
			}
		}
		else
		{
			$msgarray['prefix_id'] = 'Prefix is not configured yet.';
			$emptyFlag++;
		}
		if(isset($_POST['emprole']) && isset($_POST['department_id']) && $_POST['department_id']!='' && $_POST['emprole'] != '')
		{
			$role_split = preg_split('/_/', $_POST['emprole']);
			$reportingManagerData = $usersModel->getReportingManagerList($_POST['department_id'],'',$role_split[1]);

			if(!empty($reportingManagerData))
			{
				$report_opt = $reportingManagerData;
				 
				if(isset($_POST['reporting_manager']) && $_POST['reporting_manager']!='')
				$employeeform->setDefault('reporting_manager',$_POST['reporting_manager']);
			}

		}/*
		else if(isset($_POST['emprole']) && isset($_POST['department_id'])  && $_POST['emprole'] != '')
		{
		$role_datap = $role_model->getRoleDataById($_POST['emprole']);
		if($role_datap['group_id'] == MANAGEMENT_GROUP)
		{
		$report_opt = array(array('id' => SUPERADMIN,'name' => 'Super Admin','profileimg' => ''));
		$employeeform->setDefault('reporting_manager',SUPERADMIN);
		}
		}*/

		$employeeform->setAttrib('action',DOMAIN.'employee/add');
		$this->view->form = $employeeform;
		$this->view->msgarray = $msgarray;
		$this->view->report_opt = $report_opt;
		$this->view->emptyFlag = $emptyFlag++;
		if($this->getRequest()->getPost())
		{
			$result = $this->save($employeeform);
			$this->view->msgarray = $result;
			$this->view->messages = $result;
		}
	}



	public function editAction()
	{
		$popConfigPermission = array();
		$auth = Zend_Auth::getInstance();
		$report_opt = array();$role_datap=array();$empGroup="";
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
			$loginuserRole = $auth->getStorage()->read()->emprole;
			$loginuserGroup = $auth->getStorage()->read()->group_id;
		}
		if(sapp_Global::_checkprivileges(PREFIX,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'prefix');
		}
		if(sapp_Global::_checkprivileges(EMPLOYMENTSTATUS,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'empstatus');
		}
		if(sapp_Global::_checkprivileges(JOBTITLES,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'jobtitles');
		}
		if(sapp_Global::_checkprivileges(POSITIONS,$loginuserGroup,$loginuserRole,'add') == 'Yes'){
			array_push($popConfigPermission,'position');
		}
		$this->view->popConfigPermission = $popConfigPermission;

		$id = (int)$this->getRequest()->getParam('id');
		$id = abs($id);
		if($id == '')
		$id = $loginUserId;
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();

		$employeeform = new Default_Form_employee();

		try
		{
			if($id!='' && is_numeric($id) && $id>0 && $id!=$loginUserId)
			{
				$employeeModal = new Default_Model_Employee();
				$usersModel = new Default_Model_Users();
				$employmentstatusModel = new Default_Model_Employmentstatus();
				$busineesUnitModel = new Default_Model_Businessunits();
				$deptModel = new Default_Model_Departments();
				$role_model = new Default_Model_Roles();
				$user_model = new Default_Model_Usermanagement();
				$candidate_model = new Default_Model_Candidatedetails();
				$jobtitlesModel = new Default_Model_Jobtitles();
				$positionsmodel = new Default_Model_Positions();
				$prefixModel = new Default_Model_Prefix();
				$data = array();
				$empDeptId="";
				$empRoleId="";
				$data = $employeeModal->getsingleEmployeeData($id);
				//echo "Data <pre>";print_r($data);exit;
				if($data == 'norows')
				{
					$this->view->rowexist = "norows";
				}
				else if(!empty($data))
				{
					$this->view->rowexist = "rows";
					$employeeform->submit->setLabel('Update');
					$data = $data[0];
					
					/* Earlier code to fetch employee details */
					//$employeeData = $usersModel->getUserDetailsByIDandFlag($data['user_id']);
					$employeeData = $employeeModal->getsingleEmployeeData($id);
						
						
					//echo "<pre>";print_r($employeeData);die;
					/*if(sizeof($employeeData) > 0)
					 {
						$employeeform->user_id->addMultiOption($employeeData[0]['id'],$employeeData[0]['userfullname']);
						} */
					// $roles_arr = $role_model->getRolesList_UM();
					$roles_arr = $role_model->getRolesList_EMP();
					if(sizeof($roles_arr) > 0)
					{
						$employeeform->emprole->addMultiOptions(array(''=>'Select Role')+$roles_arr);
					}
					$referedby_options = $user_model->getRefferedByForUsers();

						

					$employmentStatusData = $employmentstatusModel->getempstatuslist();
					//echo "<pre>";print_r($employmentStatusData);exit;
					if(sizeof($employmentStatusData) > 0)
					{
						$employeeform->emp_status_id->addMultiOption('','Select a Employment Status');
						foreach ($employmentStatusData as $employmentStatusres){
							$employeeform->emp_status_id->addMultiOption($employmentStatusres['workcodename'],$employmentStatusres['statusname']);
						}
					}
						
					$businessunitData = $busineesUnitModel->getDeparmentList();
					//echo "<pre>";print_r($businessunitData);exit;
					if(sizeof($businessunitData) > 0)
					{
						//$employeeform->businessunit_id->addMultiOption('','Select a Business Unit');
						foreach ($businessunitData as $businessunitres){
							$employeeform->businessunit_id->addMultiOption($businessunitres['id'],$businessunitres['unitname']);
						}
					}
						
					$departmentsData = $deptModel->getDepartmentList($data['businessunit_id']);
					//echo "<pre>";print_r($departmentsData);exit;
					if(sizeof($departmentsData) > 0)
					{
						$employeeform->department_id->addMultiOption('','Select a Department');
						foreach ($departmentsData as $departmentsres){
							$employeeform->department_id->addMultiOption($departmentsres['id'],$departmentsres['deptname']);
						}
					}
						
						
					$jobtitleData = $jobtitlesModel->getJobTitleList();
					if(sizeof($jobtitleData) > 0)
					{
						$employeeform->jobtitle_id->addMultiOption('','Select a Job Title');
						foreach ($jobtitleData as $jobtitleres){
							$employeeform->jobtitle_id->addMultiOption($jobtitleres['id'],$jobtitleres['jobtitlename']);
						}
					}

					$positionlistArr = $positionsmodel->getPositionList($data['jobtitle_id']);
					if(sizeof($positionlistArr) > 0)
					{
						$employeeform->position_id->addMultiOption('','Select a Position');
						foreach ($positionlistArr as $positionlistres)
						{
							$employeeform->position_id->addMultiOption($positionlistres['id'],$positionlistres['positionname']);
						}
					}

					$prefixData = $prefixModel->getPrefixList();
					//echo"<pre>";print_r($prefixData);exit;
					if(!empty($prefixData))
					{
						foreach ($prefixData as $prefixres){
							$employeeform->prefix_id->addMultiOption($prefixres['id'],$prefixres['prefix']);
						}
					}
					$employeeform->populate($data);
					$employeeform->setDefault('user_id',$data['user_id']);
					$employeeform->setDefault('emp_status_id',$data['emp_status_id']);
					$employeeform->setDefault('businessunit_id',$data['businessunit_id']);
					$employeeform->setDefault('jobtitle_id',$data['jobtitle_id']);
					$employeeform->setDefault('department_id',$data['department_id']);
					$employeeform->setDefault('position_id',$data['position_id']);
					$employeeform->setDefault('prefix_id',$data['prefix_id']);
					$date_of_joining = sapp_Global::change_date($data['date_of_joining'],'view');
					$employeeform->date_of_joining->setValue($date_of_joining);
						
					if($data['date_of_leaving'] !='' && $data['date_of_leaving'] !='0000-00-00')
					{
						$date_of_leaving = sapp_Global::change_date($data['date_of_leaving'],'view');
						$employeeform->date_of_leaving->setValue($date_of_leaving);
					}
					if($data['modeofentry'] != 'Direct')
					{
						$employeeform->rccandidatename->setValue($data['userfullname']);
					}
					if(sizeof($referedby_options) > 0 && $data['candidatereferredby'] != '' && $data['candidatereferredby'] != 0)
					{
						$employeeform->candidatereferredby->setValue($referedby_options[$data['candidatereferredby']]);
					}

					if($data['rccandidatename'] != '' && $data['rccandidatename']!=0)
					{
						$cand_data = $candidate_model->getCandidateById($data['rccandidatename']);
						$data['requisition_code'] = $cand_data['requisition_code'];
						//v$employeeform->rccandidatename->setValue($cand_data['candidate_name']);
					}
					$role_data = $role_model->getRoleDataById($data['emprole']);
					$employeeform->emprole->setValue($data['emprole']."_".$role_data['group_id']);
						
					$employeeform->setAttrib('action',DOMAIN.'employee/edit/id/'.$id);
						
					$reportingManagerData = $usersModel->getReportingManagerList($data['department_id'],$data['id'],$role_data['group_id']);
						
					$empDeptId = (isset($_POST['department_id']))?$_POST['department_id']:$data['department_id'];
					if(isset($_POST['emprole']) && $_POST['emprole'] != '' )
					{
						$role_split = preg_split('/_/', $_POST['emprole']);
						$empRoleId = $role_split[0];
					}
					else
					{
						$empRoleId = $data['emprole'];
					}
					if(isset($empDeptId) && $empDeptId!='' && isset($empRoleId) && $empRoleId != '')
					{
						$role_datap = $role_model->getRoleDataById($empRoleId);
						$reportingManagerData = $usersModel->getReportingManagerList($empDeptId,$data['id'],$role_datap['group_id']);
					}
						
					if(!empty($reportingManagerData))
					{
						$report_opt = $reportingManagerData;
						/*$empGroup = (!empty($role_datap))?$role_datap['group_id']:"";
						 if($empGroup == MANAGEMENT_GROUP)
						 {
							array_push($report_opt,array('id'=>SUPERADMIN,'name' => 'Super Admin','profileimg'=>''));
							}*/
						//echo "<pre>";print_r($report_opt);echo "</pre>";die;
					}
					$employeeform->setDefault('reporting_manager',$data['reporting_manager']);
						
					$this->view->id = $id;
					$this->view->form = $employeeform;
					$this->view->employeedata = (!empty($employeeData))?$employeeData[0]:"";
					$this->view->messages = $this->_helper->flashMessenger->getMessages();
					$this->view->data = $data;
                                        
                                        if($data['is_orghead'] == 1)
                                        {
                                            $employeeform->removeElement('businessunit_id');
                                            $employeeform->removeElement('reporting_manager');
                                            $employeeform->removeElement('department_id');
                                            $employeeform->removeElement('emp_status_id');
                                            $employeeform->removeElement('date_of_leaving');
                                        }
				}
				$this->view->report_opt = $report_opt;

			}
			else
			{
				$this->view->rowexist = "norows";
			}

			if($this->getRequest()->getPost()){
				$result = $this->save($employeeform);
				$this->view->msgarray = $result;
				$employeeform->modeofentry->setValue($data['modeofentry']);
				$employeeform->other_modeofentry->setValue($data['other_modeofentry']);
				if($data['modeofentry'] != 'Direct')
				{
					$employeeform->rccandidatename->setValue($data['userfullname']);
				}
				if(sizeof($referedby_options) > 0 && $data['candidatereferredby'] != '' && $data['candidatereferredby'] != 0)
				{
					$employeeform->candidatereferredby->setValue($referedby_options[$data['candidatereferredby']]);
				}
                                //print_r($result);exit;
			}

		}
		catch(Exception $e)
		{
			$this->view->rowexist = "norows";
                        //echo $e->getTraceAsString();
		}
	}
	public function viewAction()
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->getRequest()->getParam('id');
		$callval = $this->getRequest()->getParam('call');
		if($callval == 'ajaxcall')
		$this->_helper->layout->disableLayout();
		$objName = 'employee';
		$employeeform = new Default_Form_employee();
		$employeeform->removeElement("submit");
		$elements = $employeeform->getElements();
		if(count($elements)>0)
		{
			foreach($elements as $key=>$element)
			{
				if(($key!="Cancel")&&($key!="Edit")&&($key!="Delete")&&($key!="Attachments")){
					$element->setAttrib("disabled", "disabled");
				}
			}
		}
		try
		{
			if($id && is_numeric($id) && $id>0 && $id!=$loginUserId)
			{
				$employeeModal = new Default_Model_Employee();
				$usersModel = new Default_Model_Users();
				$employmentstatusModel = new Default_Model_Employmentstatus();
				$busineesUnitModel = new Default_Model_Businessunits();
				$deptModel = new Default_Model_Departments();
				$role_model = new Default_Model_Roles();
				$user_model = new Default_Model_Usermanagement();
				$candidate_model = new Default_Model_Candidatedetails();
				$jobtitlesModel = new Default_Model_Jobtitles();
				$positionsmodel = new Default_Model_Positions();
				$prefixModel = new Default_Model_Prefix();
				$data = array();
				//$data = $employeeModal->getsingleEmployeeData($id);
				$data = $employeeModal->getsingleEmployeeData($id);
				//echo "<pre>";print_r($data);exit;
				if($data == 'norows')
				{
					$this->view->rowexist = "norows";
				}
				else if(!empty($data))
				{
					$this->view->rowexist = "rows";
						
					//$data = $employeeModal->getActiveEmployeeData($id);
					$data = $data[0];
					$employeeData = $usersModel->getUserDetailsByIDandFlag($data['user_id']);
					//echo "<pre>";print_r($employeeData);die;
					/*if(sizeof($employeeData) > 0)
					 {
						$employeeform->user_id->addMultiOption($employeeData[0]['id'],$employeeData[0]['userfullname']);
						} */
					//$roles_arr = $role_model->getRolesList_UM();
					/*$roles_arr = $role_model->getRolesList_EMP();
					 echo "<pre>";print_r($roles_arr);exit;
					 if(sizeof($roles_arr) > 0)
					 {
						$employeeform->emprole->addMultiOptions(array(''=>'Select Role')+$roles_arr);
						}*/
					$roles_arr = $role_model->getRolesDataByID($data['emprole']);
					if(sizeof($roles_arr) > 0)
					{
						$employeeform->emprole->addMultiOption($roles_arr[0]['id'].'_'.$roles_arr[0]['group_id'],utf8_encode($roles_arr[0]['rolename']));

					}
						
					$referedby_options = $user_model->getRefferedByForUsers();
					//$reportingManagerData = $usersModel->getReportingManagerList($data['department_id'],$data['id']);
					//echo "<pre>";print_r($reportingManagerData);exit;
					/*if(!empty($reportingManagerData))
					 {
						$employeeform->reporting_manager->addMultiOption('','Select a Reporting Manager');
						foreach ($reportingManagerData as $reportingManagerres){
						$employeeform->reporting_manager->addMultiOption($reportingManagerres['id'],$reportingManagerres['userfullname']);
						}
						}*/
					$reportingManagerData = $usersModel->getReportingManagerList($data['department_id'],$data['id'],$roles_arr[0]['group_id']);
					//echo "<pre>";print_r($reportingManagerData);exit;
					if(!empty($reportingManagerData))
					{
						$employeeform->reporting_manager->addMultiOption('','Select a Reporting Manager');
						/*if($roles_arr[0]['group_id'] == MANAGEMENT_GROUP)
							$employeeform->reporting_manager->addMultiOption(SUPERADMIN,'Super Admin');*/

						foreach ($reportingManagerData as $reportingManagerres){
							$employeeform->reporting_manager->addMultiOption($reportingManagerres['id'],$reportingManagerres['name']);
						}
					}
					$employeeform->setDefault('reporting_manager',$data['reporting_manager']);
					/*else
					 {
						$reportingManagerData = $usersModel->getUserDetailsByIDandFlag($data['reporting_manager']);
						echo "<pre>";print_r($reportingManagerData);exit;
						$employeeform->reporting_manager->addMultiOption($reportingManagerData[0]['id'],$reportingManagerData[0]['userfullname']);
						}*/
						
					$employmentStatusData = $employmentstatusModel->getempstatuslist();
					//echo "<pre>";print_r($employmentStatusData);exit;
					if(sizeof($employmentStatusData) > 0)
					{
						$employeeform->emp_status_id->addMultiOption('','Select a Employment Status');
						foreach ($employmentStatusData as $employmentStatusres){
							$employeeform->emp_status_id->addMultiOption($employmentStatusres['workcodename'],$employmentStatusres['statusname']);
						}
					}
						
					$businessunitData = $busineesUnitModel->getDeparmentList();
					//echo "<pre>";print_r($businessunitData);exit;
					if(sizeof($businessunitData) > 0)
					{
						$employeeform->businessunit_id->addMultiOption('','Select a Business Unit');
						foreach ($businessunitData as $businessunitres){
							$employeeform->businessunit_id->addMultiOption($businessunitres['id'],$businessunitres['unitname']);
						}
					}
						
					$departmentsData = $deptModel->getDepartmentList($data['businessunit_id']);
					//echo "<pre>";print_r($departmentsData);exit;
					if(sizeof($departmentsData) > 0)
					{
						$employeeform->department_id->addMultiOption('','Select a Department');
						foreach ($departmentsData as $departmentsres){
							$employeeform->department_id->addMultiOption($departmentsres['id'],$departmentsres['deptname']);
						}
					}
						
						
					$jobtitleData = $jobtitlesModel->getJobTitleList();
					if(sizeof($jobtitleData) > 0)
					{
						$employeeform->jobtitle_id->addMultiOption('','Select a Job Title');
						foreach ($jobtitleData as $jobtitleres){
							$employeeform->jobtitle_id->addMultiOption($jobtitleres['id'],$jobtitleres['jobtitlename']);
						}
					}

					$positionlistArr = $positionsmodel->getPositionList($data['jobtitle_id']);
					if(sizeof($positionlistArr) > 0)
					{
						$employeeform->position_id->addMultiOption('','Select a Position');
						foreach ($positionlistArr as $positionlistres){
							$employeeform->position_id->addMultiOption($positionlistres['id'],$positionlistres['positionname']);
						}
					}

					if(isset($data['prefix_id']) && $data['prefix_id'] !='')
					{
						$singlePrefixArr = $prefixModel->getsinglePrefixData($data['prefix_id']);
						if($singlePrefixArr !='norows')
						$employeeform->prefix_id->addMultiOption($singlePrefixArr[0]['id'],$singlePrefixArr[0]['prefix']);
					}
						
					$employeeform->populate($data);
					$employeeform->setDefault('user_id',$data['user_id']);
					//$employeeform->setDefault('reporting_manager',$data['reporting_manager']);
					/*
						Purpose:	If the Employee's Reporting manager is Super Admin... Leave the reporting manager field as blank.
						Modified Date:	29/10/2013.
						Modified By:	Yamini.
							
						if($data['reporting_manager'] == SUPERADMIN)
						{
						//$employeeform->setDefault('reporting_manager',$data['reporting_manager']);
						}
						else
						{
						$employeeform->setDefault('reporting_manager',$data['reporting_manager']);
						}*/
					$employeeform->setDefault('emp_status_id',$data['emp_status_id']);
					$employeeform->setDefault('businessunit_id',$data['businessunit_id']);
					$employeeform->setDefault('jobtitle_id',$data['jobtitle_id']);
					$employeeform->setDefault('department_id',$data['department_id']);
					$employeeform->setDefault('position_id',$data['position_id']);
					$date_of_joining = sapp_Global::change_date($data['date_of_joining'],'view');
					$employeeform->date_of_joining->setValue($date_of_joining);
						
					if($data['date_of_leaving'] !='' && $data['date_of_leaving'] !='0000-00-00')
					{
							
						$date_of_leaving = sapp_Global::change_date($data['date_of_leaving'], 'view');
						$employeeform->date_of_leaving->setValue($date_of_leaving);
					}
					if($data['modeofentry'] != 'Direct')
					{
						$employeeform->rccandidatename->setValue($data['userfullname']);
					}
					if(sizeof($referedby_options) > 0 && $data['candidatereferredby'] != '' && $data['candidatereferredby'] != 0)
					{
						$employeeform->candidatereferredby->setValue($referedby_options[$data['candidatereferredby']]);
					}
					if($data['rccandidatename'] != '' && $data['rccandidatename']!=0)
					{
						$cand_data = $candidate_model->getCandidateById($data['rccandidatename']);
						$data['requisition_code'] = $cand_data['requisition_code'];
					}
					/*if($data['backgroundchk_status'] == 'Yet to start')
					 $employeeform->setDefault('backgroundchk_status',4);
					 else
					 $employeeform->setDefault('backgroundchk_status',3);*/
					$employeeform->setAttrib('action',DOMAIN.'employee/edit/id/'.$id);
					$this->view->id = $id;
					$this->view->form = $employeeform;
					$this->view->employeedata = (!empty($employeeData))?$employeeData[0]:"";
					$this->view->messages = $this->_helper->flashMessenger->getMessages();
					$this->view->data = $data;
					$this->view->controllername = $objName;
					$this->view->id = $id;
				}
			}else
			{
				$this->view->rowexist = "norows";
			}
		}
		catch(Exception $e)
		{
			$this->view->rowexist = "norows";
		}
	}


	public function save($employeeform)
	{
		$emproleStr='';$roleArr=array();$empgroupStr='';
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
		{
			$loginUserId = $auth->getStorage()->read()->id;
		}
		//echo"<pre>";print_r($this->_request->getPost());echo "</pre>";exit;
		//return true;
		$usersModel = new Default_Model_Usermanagement();
		$employeeModal = new Default_Model_Employee();
		$requimodel = new Default_Model_Requisition();
		$candidate_model = new Default_Model_Candidatedetails();
		$businessunit_id = $this->_request->getParam('businessunit_id',null);
		$department_id = $this->_request->getParam('department_id',null);
		$reporting_manager = $this->_request->getParam('reporting_manager',null);
		$jobtitle_id = $this->_request->getParam('jobtitle_id',null);
		$position_id = $this->_request->getParam('position_id',null);
		$user_id = $this->_getParam('user_id',null);
		$prefix_id = $this->_getParam('prefix_id',null);
		$extension_number = $this->_getParam('extension_number',null);
		$office_number = $this->_request->getParam('office_number',null);
		$office_faxnumber = $this->_request->getParam('office_faxnumber',null);
		if($employeeform->isValid($this->_request->getPost()))
		{
			$id = $this->_request->getParam('id');
			$emp_status_id = $this->_request->getParam('emp_status_id',null);
			$date_of_joining = $this->_request->getParam('date_of_joining',null);
			$date_of_joining = sapp_Global::change_date($date_of_joining,'database');
			$date_of_leaving = $this->_request->getParam('date_of_leaving',null);
			$date_of_leaving = sapp_Global::change_date($date_of_leaving,'database');
			$years_exp = $this->_request->getParam('years_exp');
			//FOR USER table
			$employeeId = $this->_getParam('employeeId',null);
			$modeofentry = $this->_getParam('modeofentry',null);
			$hid_modeofentry = $this->_getParam('hid_modeofentry',null);
			$other_modeofentry = $this->_getParam('other_modeofentry',null);
			$userfullname = $this->_getParam('userfullname',null);
			$candidatereferredby = $this->_getParam('candidatereferredby',null);
			$rccandidatename = $this->_getParam('rccandidatename',null);
			$emprole = $this->_getParam('emprole',null);	//roleid_group_id
			if($emprole != "")
			{
				$roleArr = explode('_',$emprole);
				if(!empty($roleArr))
				{
					$emproleStr = $roleArr[0];
					$empgroupStr = $roleArr[0];
				}
			}
			//echo  "Post val role > ". $emprole;die;
			$emailaddress = $this->_getParam('emailaddress',null);
			$tmp_name = $this->_request->getParam('tmp_emp_name',null);
			$act_inact = $this->_request->getParam("act_inact",null);
			//end of user table
			$date = new Zend_Date();
			$menumodel = new Default_Model_Menu();
			$empstatusarray = array(8,9,10);
			$actionflag = '';
			$tableid  = '';

			if($modeofentry == 'Direct' || $hid_modeofentry == 'Direct')
			{
				$candidate_key = 'userfullname';
				$candidate_value = $userfullname;
				$emp_name = $userfullname;
				$candidate_flag = 'no';
			}
			else
			{
				$candidate_key = 'rccandidatename';
				//$candidate_value = ($id=='')?$rccandidatename:$hid_rccandidatename;
				$candidate_value = $rccandidatename;
				$emp_name = $tmp_name;
				$candidate_flag = 'yes';

			}
			$trDb = Zend_Db_Table::getDefaultAdapter();
			// starting transaction
			$trDb->beginTransaction();
			try
			{
				$emppassword = sapp_Global::generatePassword();
				$user_data = array(
				// 'emprole' => $emprole,
                                    'emprole' =>$emproleStr,
				$candidate_key => $candidate_value,
                                    'emailaddress' => $emailaddress,
									'jobtitle_id'=> $jobtitle_id,
									'modifiedby'=> $loginUserId,
                                    'modifieddate'=> gmdate("Y-m-d H:i:s"),                                                                      
                                    'emppassword' => md5($emppassword),
                                    'employeeId' => $employeeId,
                                    'modeofentry' => ($id =='')?$modeofentry:"",                                                              
                                    'selecteddate' => $date_of_joining,
                                    'candidatereferredby' => $candidatereferredby,
                                    'userstatus' => 'old',
                                    'other_modeofentry' => $other_modeofentry,
				);
				//echo "<pre> userdata ";print_r($user_data);die;
				if($id!='')
				{
					$where = array('user_id=?'=>$user_id);
					$actionflag = 2;
					$user_where = "id = ".$user_id;
					unset($user_data['candidatereferredby']);
					unset($user_data['userstatus']);
					unset($user_data['emppassword']);
					unset($user_data['employeeId']);
					unset($user_data['modeofentry']);
					unset($user_data['other_modeofentry']);
					//unset($user_data[$candidate_key]);
					/*if($act_inact == 1)
					 {
					 $udata = $usersModel->getUserDataById($user_id);

					 $user_data['isactive'] = ($udata['isactive']==0?"1":"0");
					 $user_data['emptemplock'] = ($udata['emptemplock']==0?"1":"0");

					 }*/
				}
				else
				{
					 

					$user_data['createdby'] = $loginUserId;
					$user_data['createddate'] = gmdate("Y-m-d H:i:s");
					$user_data['isactive'] = 1;
					if($modeofentry != 'Direct')
					{
						$user_data['userfullname'] = $emp_name;
					}
					//echo"<pre>";print_r($user_data);echo "</pre>";
					//return true;
					$where = '';
					$actionflag = 1;
					$user_where = '';

					$identity_code_model = new Default_Model_Identitycodes();
					$identity_codes = $identity_code_model->getIdentitycodesRecord();
					$emp_identity_code = isset($identity_codes[0])?$identity_codes[0]['employee_code']:"";
					if($emp_identity_code!='')
					$emp_id = $emp_identity_code."-".str_pad($usersModel->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
					else
					$emp_id = '';

					$user_data['employeeId'] = $emp_id;
				}

				$user_status = $usersModel->SaveorUpdateUserData($user_data, $user_where);
				if($id == '')
				$user_id = $user_status;
				$data = array(  'user_id'=>$user_id,
                                    'reporting_manager'=>$reporting_manager,
                                    'emp_status_id'=>$emp_status_id,
                                    'businessunit_id'=>$businessunit_id,
                                    'department_id'=>$department_id,
                                    'jobtitle_id'=>$jobtitle_id, 
                                    'position_id'=>$position_id, 
                                    'prefix_id'=>$prefix_id,
                                    'extension_number'=>($extension_number!=''?$extension_number:NULL),
                                    'office_number'=>($office_number!=''?$office_number:NULL),
                                    'office_faxnumber'=>($office_faxnumber!=''?$office_faxnumber:NULL),  									
                                    'date_of_joining'=>$date_of_joining,
                                    'date_of_leaving'=>($date_of_leaving!=''?$date_of_leaving:NULL),
                                    'years_exp'=>($years_exp=='')?null:$years_exp,
                                    'modifiedby'=>$loginUserId,				
                                    'modifieddate'=>gmdate("Y-m-d H:i:s")
				);
				if($id == '')
				{
					$data['createdby'] = $loginUserId;
					$data['createddate'] = gmdate("Y-m-d H:i:s");;
					$data['isactive'] = 1;
				}
				$Id = $employeeModal->SaveorUpdateEmployeeData($data, $where);

				$statuswhere = array('id=?'=>$user_id);
				if (in_array($emp_status_id, $empstatusarray))
				{
					$isactivestatus = '';
					if($emp_status_id == 8)
					$isactivestatus = 2;
					else if($emp_status_id == 9)
					$isactivestatus = 3;
					else if($emp_status_id == 10)
					$isactivestatus = 4;

					$statusdata = array('isactive'=>$isactivestatus);

					$empstatusId = $usersModel->SaveorUpdateUserData($statusdata, $statuswhere);
				}
				else
				{
					//if($act_inact != 1)
					//{
					$statusdata = array('isactive'=> 1);
					$empstatusId = $usersModel->SaveorUpdateUserData($statusdata, $statuswhere);
					//}
				}
				if($Id == 'update')
				{
					$tableid = $id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Employee details updated successfully."));
				}
				else
				{
					//start of mailing
					$base_url = 'http://'.$this->getRequest()->getHttpHost() . $this->getRequest()->getBaseUrl();
					$view = $this->getHelper('ViewRenderer')->view;
					$this->view->emp_name = $emp_name;
					$this->view->password = $emppassword;
					$this->view->emp_id = $employeeId;
					$this->view->base_url=$base_url;
					$text = $view->render('mailtemplates/newpassword.phtml');
					$options['subject'] = APPLICATION_NAME.': Login credentials';
					$options['header'] = 'Greetings from Sentrifugo';
					$options['toEmail'] = $emailaddress;
					$options['toName'] = $this->view->emp_name;
					$options['message'] = $text;
					$result = sapp_Global::_sendEmail($options);
					//end of mailing
					$tableid = $Id;
					$this->_helper->getHelper("FlashMessenger")->addMessage(array("success"=>"Employee details added successfully."));

					//incrementing requisition id
					if($candidate_flag == 'yes')
					{
						$cand_data = $candidate_model->getCandidateById($rccandidatename);
						$candidate_model->SaveorUpdateCandidateData(array('cand_status' => 'Recruited','modifieddate' => gmdate("Y-m-d H:i:s")), " id = ".$rccandidatename);
						$reqData = $requimodel->incrementfilledpositions($cand_data['requisition_id']);
						if($reqData['req_no_positions'] == $reqData['filled_positions'])
						{
							$req_status = '6';
							$data = array(
                                        'req_status' 		=>	$req_status,
                                        'modifiedby' 		=> 	trim($loginUserId),
                                        'modifiedon' 		=> 	gmdate("Y-m-d H:i:s")
							);
							$where = "id = ".$cand_data['requisition_id'];
							$result = $requimodel->SaveorUpdateRequisitionData($data, $where);
							$requimodel->change_to_requisition_closed($cand_data['requisition_id']);
						}
					}
				}
				$menuidArr = $menumodel->getMenuObjID('/employee');
				$menuID = $menuidArr[0]['id'];
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);

				$menuidArr = $menumodel->getMenuObjID('/usermanagement');
				$menuID_user = $menuidArr[0]['id'];
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$user_id);
				if($act_inact == 1)
				{
					if($user_data['isactive'] == 1)
					{
						$act_str = array("Activated" => gmdate("Y-m-d H:i:s"));
					}
					else
					{
						$act_str = array("Inactivated"=> gmdate("Y-m-d H:i:s"));
					}

					$result = sapp_Global::logManager($menuID_user,4,$loginUserId,$user_id,'',$act_str);
				}
				$trDb->commit();
				$this->_redirect('employee/edit/id/'.$user_id);
			}
			catch (Exception $e)
			{
				//echo $e->getMessage();
				//echo $e->getTraceAsString();
				$trDb->rollBack();
				//exit;
				$msgarray['employeeId'] = "Something went wrong,please try again later.";
				return $msgarray;
			}
		}
		else
		{
			$messages = $employeeform->getMessages();
			foreach ($messages as $key => $val)
			{
				foreach($val as $key2 => $val2)
				{
					$msgarray[$key] = $val2;
					break;
				}
			}
			//echo "<pre> Messages";print_r($messages);die;
			if(isset($businessunit_id)  && $businessunit_id != '')
			{
				$departmentsmodel = new Default_Model_Departments();
				$departmentlistArr = $departmentsmodel->getDepartmentList($businessunit_id);
				$employeeform->department_id->clearMultiOptions();
				$employeeform->reporting_manager->clearMultiOptions();
				$employeeform->department_id->addMultiOption('','Select Department');
				foreach($departmentlistArr as $departmentlistresult)
				{
					$employeeform->department_id->addMultiOption($departmentlistresult['id'],utf8_encode($departmentlistresult['deptname']));
				}
				 
				if(isset($department_id) && $department_id != 0 && $department_id != '')
				$employeeform->setDefault('department_id',$department_id);
			}


			if(isset($jobtitle_id) && $jobtitle_id != 0 && $jobtitle_id != '')
			{
				$positionsmodel = new Default_Model_Positions();
				$positionlistArr = $positionsmodel->getPositionList($jobtitle_id);
				$employeeform->position_id->clearMultiOptions();
				$employeeform->position_id->addMultiOption('','Select Position');
				foreach($positionlistArr as $positionlistRes)
				{
					$employeeform->position_id->addMultiOption($positionlistRes['id'],utf8_encode($positionlistRes['positionname']));
				}
				if(isset($position_id) && $position_id != 0 && $position_id != '')
				$employeeform->setDefault('position_id',$position_id);
			}
			return $msgarray;
		}
	}

	public function getdepartmentsAction()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('getdepartments', 'html')->initContext();


		$businessunit_id = $this->_request->getParam('business_id');
		$con = $this->_request->getParam('con');
		$employeeform = new Default_Form_employee();
		$leavemanagementform = new Default_Form_leavemanagement();
		$flag = '';
		$departmentsmodel = new Default_Model_Departments();
		if($con == 'leavemanagement')
		{
			$leavemanagementmodel = new Default_Model_Leavemanagement();
			$departmentidsArr = $leavemanagementmodel->getActiveDepartmentIds();
			//echo "<pre>";print_r($departmentidsArr);exit;
			$depatrmentidstr = '';
			$newarr = array();
			if(!empty($departmentidsArr))
			{
				$where = '';
				for($i=0;$i<sizeof($departmentidsArr);$i++)
				{
					$newarr1[] = array_push($newarr, $departmentidsArr[$i]['deptid']);

				}
				$depatrmentidstr = implode(",",$newarr);
				foreach($newarr as $deparr)
				{
					$where.= " id!= $deparr AND ";
				}
				$where = trim($where," AND");
				$querystring = "Select d.id,d.deptname from main_departments as d where d.unitid=$businessunit_id and d.isactive=1 and $where  ";

				$uniquedepartmentids = $departmentsmodel->getUniqueDepartments($querystring);
				//echo "<pre>";print_r($uniquedepartmentids);exit;
				if(empty($uniquedepartmentids))
				$flag = 'true';
					
				$this->view->uniquedepartmentids=$uniquedepartmentids;
			}
			else
			{
				$departmentlistArr = $departmentsmodel->getDepartmentList($businessunit_id);
				if(empty($departmentlistArr))
				$flag = 'true';
				$this->view->departmentlistArr=$departmentlistArr;
			}
		}
		else
		{
			$departmentlistArr = $departmentsmodel->getDepartmentList($businessunit_id);
			if(empty($departmentlistArr))
			$flag = 'true';
			$this->view->departmentlistArr=$departmentlistArr;
		}
	  
		$this->view->employeeform=$employeeform;
		$this->view->leavemanagementform=$leavemanagementform;
		$this->view->flag=$flag;
		if($con !='')
		$this->view->con=$con;

	}

	public function getpositionsAction()
	{
		$ajaxContext = $this->_helper->getHelper('AjaxContext');
		$ajaxContext->addActionContext('getpositions', 'html')->initContext();


		$jobtitle_id = $this->_request->getParam('jobtitle_id');
		$con = $this->_request->getParam('con');
		$employeeform = new Default_Form_employee();
		$positionsmodel = new Default_Model_Positions();
		$flag = '';
		$positionlistArr = $positionsmodel->getPositionList($jobtitle_id);
		if(empty($positionlistArr))
		$flag = 'true';
			
		$this->view->positionlistArr=$positionlistArr;
		$this->view->employeeform=$employeeform;
		$this->view->flag=$flag;
		if($con !='')
		$this->view->con=$con;

	}

	public function deleteAction()
	{
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}
		$id = $this->_request->getParam('objid');
		$messages['message'] = '';
		$messages['msgtype'] = '';
		$actionflag = 3;
		if($id)
		{
			$employeemodel = new Default_Model_Employee();
			$usersModel = new Default_Model_Users();
			/*$creditcarddetailsmodel = new Default_Model_Creditcarddetails();
			 $dependencydetailsmodel = new Default_Model_Dependencydetails();
			 $disabilitydetailsmodel = new Default_Model_Disabilitydetails();
			 $educationdetailsmodel = new Default_Model_Educationdetails();
			 $empcommdetailsmodel = new Default_Model_Empcommunicationdetails();
			 $empholidaysmodel = new Default_Model_Empholidays();
			 $employeeleavesmodel = new Default_Model_Employeeleaves();
			 $emppersdetailsmodel = new Default_Model_Emppersonaldetails();
			 $empskillsmodel = new Default_Model_Empskills();
			 $experiencedetailsmodel = new Default_Model_Experiencedetails();
			 $medicalclaimsmodel = new Default_Model_Medicalclaims();
			 $trainingdetailsmodel = new Default_Model_Trainingandcertificationdetails();
			 $visadetailsmodel = new Default_Model_Visaandimmigrationdetails();
			 $workeligibilitydetailsmodel = new Default_Model_Workeligibilitydetails();*/
			$menumodel = new Default_Model_Menu();
				
			$empdata = array('isactive'=>5);
			$empwhere = array('user_id=?'=>$id);
				
			$userdata = array('isactive'=>5);
			$userwhere = array('id=?'=>$id);
				
				
			$empId = $employeemodel->SaveorUpdateEmployeeData($empdata, $empwhere);
			$userId = $usersModel->addOrUpdateUserModel($userdata, $userwhere);
				
			if($empId == 'update' && $userId == 'update')
			{
				$menuidArr = $menumodel->getMenuObjID('/employee');
				$menuID = $menuidArr[0]['id'];
				$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$id);
				$messages['message'] = 'Employee deleted successfully.';
				$messages['msgtype'] = 'success';
			}
			else
			{
				$messages['message'] = 'Employee cannot be deleted.';
				$messages['msgtype'] = 'error';
			}
		}
		else
		{
			$messages['message'] = 'Employee cannot be deleted.';
			$messages['msgtype'] = 'error';
		}
		$this->_helper->json($messages);
	}

	/**
	 * This function is used for ajax call to get reporting managers based on  department
	 * @param $department_id  = department id.
	 * @return Array Array of managers in json format.
	 */
	public function getempreportingmanagersAction()
	{

		$employeeform = new Default_Form_employee();

		$dept_id = $this->_getParam('id',null);
		$employee_id = $this->_getParam('empId',null);
		$employee_group = $this->_getParam('empgroup',null);
		$usersModel = new Default_Model_Users();
		$reportingManagerData = array();
		$flag = '';
		if($dept_id != '')
		{
			$reportingManagerData = $usersModel->getReportingManagerList($dept_id,$employee_id,$employee_group);
				
			if(empty($reportingManagerData))
			{
				$flag = 'true';
			}
			else
			{
				$flag = 'false';
			}
		}

		$this->view->RMdata=$reportingManagerData;
		$this->view->employeeform=$employeeform;
		$this->view->flag=$flag;
	}
	/**
	 * This function is used to active/inactive employees.
	 */
	public function makeactiveinactiveAction()
	{
		$emp_id = $this->_getParam('emp_id',null);
		$status = trim($this->_getParam('status',null));

		$user_model =new Default_Model_Usermanagement();
		if($status == 'active')
		{
			$data = array(
                'isactive' => 1,
                'emptemplock' => 0,
			);
		}
		else if($status == 'inactive')
		{
			$data = array(
                'isactive' => 0,
                'emptemplock' => 1,
			);
		}
		$where = "id = ".$emp_id;

		$result = $user_model->SaveorUpdateUserData($data, $where);
		$this->_helper->json(array('result' =>($result == 'update')?"yes":"no"));
	}
	
	public function addemppopupAction()
	{
		$flag = 'true'; $controllername = 'employee';$msgarray = array();
		Zend_Layout::getMvcInstance()->setLayoutPath(APPLICATION_PATH."/layouts/scripts/popup/");
		$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity()){
			$loginUserId = $auth->getStorage()->read()->id;
		}	
		
		$emp_form = new Default_Form_employee();	
		
		$user_model = new Default_Model_Usermanagement();
		$role_model = new Default_Model_Roles();
		$prefixModel = new Default_Model_Prefix();
		$identity_code_model = new Default_Model_Identitycodes();
		$jobtitlesModel = new Default_Model_Jobtitles();
		$deptModel = new Default_Model_Departments();
		$positionsmodel = new Default_Model_Positions();
		$employeeModal = new Default_Model_Employee();
		
		
		$emp_form->setAction(DOMAIN.'employee/addemppopup');
		$emp_form->removeElement('emp_status_id');
		$emp_form->removeElement('department_id');
		$emp_form->removeElement('modeofentry');
		$emp_form->removeElement('reporting_manager');
		
		$identity_codes = $identity_code_model->getIdentitycodesRecord();
		$emp_identity_code = isset($identity_codes[0])?$identity_codes[0]['employee_code']:"";
		if($emp_identity_code!='')
		{
					$emp_id = $emp_identity_code."-".str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
		}	
		else 
		{
					$emp_id = '';
					$msgarray['employeeId'] = 'Identity codes are not configured yet.';
					$flag = 'false';
		}			
		$emp_form->employeeId->setValue($emp_id);

		$role_data = $role_model->getRolesList_orginfo();   
		$emp_form->emprole->addMultiOptions(array('' => 'Select Role')+$role_data);
		if(empty($role_data))
		{
			$msgarray['emprole'] = 'Roles are not configured yet.';
			$flag = 'false';
		}
		
		$prefixData = $prefixModel->getPrefixList(); 
	
		$emp_form->prefix_id->addMultiOption('','Select Prefix');
		if(!empty($prefixData))
		{ 			
			foreach ($prefixData as $prefixres)
			{
				$emp_form->prefix_id->addMultiOption($prefixres['id'],$prefixres['prefix']);
			}
		}
		else
		{
			$msgarray['prefix_id'] = 'Prefix is not configured yet.';
			$flag = 'false';
		}
		
		$jobtitleData = $jobtitlesModel->getJobTitleList();                 
		if(!empty($jobtitleData))
		{ 						        
			foreach ($jobtitleData as $jobtitleres)
			{
				$emp_form->jobtitle_id->addMultiOption($jobtitleres['id'],$jobtitleres['jobtitlename']);
			}
		}
		else
		{			    
			$msgarray['jobtitle_id'] = 'Job titles are not configured yet.';
			$msgarray['position_id'] = 'Positions are not configured yet.';
			$flag = 'false';
		}
		
		if(isset($_POST['jobtitle_id']) && $_POST['jobtitle_id']!='')
		{
			$positionlistArr = $positionsmodel->getPositionList($_POST['jobtitle_id']);
			if(sizeof($positionlistArr) > 0)
			{
				$emp_form->position_id->addMultiOption('','Select a Position');
				foreach ($positionlistArr as $positionlistres)
				{
					$emp_form->position_id->addMultiOption($positionlistres['id'],$positionlistres['positionname']);
				}
			}
		}
		if($this->getRequest()->getPost())
		{
			if($emp_form->isValid($this->_request->getPost()) && $flag == 'true')
			{
				$jobtitle_id = $this->_request->getParam('jobtitle_id',null);
				$position_id = $this->_request->getParam('position_id',null);                        
				$date_of_joining = sapp_Global::change_date($this->_request->getParam('date_of_joining',null),'database');
				$employeeId = $this->_getParam('employeeId',null);
				$emprole = $this->_getParam('emprole',null);
				$emailaddress = $this->_getParam('emailaddress',null);
				$emppassword = sapp_Global::generatePassword();
				$userfullname = trim($this->_request->getParam('userfullname',null));
				$prefix_id = $this->_getParam('prefix_id',null);
				$user_id = $this->_getParam('user_id',null);
				
				$user_data = array(
							'emprole' => $emprole,                                
							'userfullname' => $userfullname,
							'emailaddress' => $emailaddress,
							'jobtitle_id'=> $jobtitle_id,
							'modifiedby'=> $loginUserId,
							'modifieddate'=> gmdate("Y-m-d H:i:s"),                                                                      
							'emppassword' => md5($emppassword),
							'employeeId' => $employeeId,
							'modeofentry' => 'Direct',                                                              
							'selecteddate' => $date_of_joining,                                    
							'userstatus' => 'old',                                    
						);
				$emp_data = array(  
								'user_id'=>$user_id,                                        
								'jobtitle_id'=>$jobtitle_id, 
								'position_id'=>$position_id, 
								'prefix_id'=>$prefix_id,                                     									
								'date_of_joining'=>$date_of_joining,                                    
								'modifiedby'=>$loginUserId,				
								'modifieddate'=>gmdate("Y-m-d H:i:s")
								);
				$user_data['createdby'] = $loginUserId;
				$user_data['createddate'] = gmdate("Y-m-d H:i:s");
				$user_data['isactive'] = 1;
				if($emp_identity_code!='')
					$emp_id = $emp_identity_code."-".str_pad($user_model->getMaxEmpId($emp_identity_code), 4, '0', STR_PAD_LEFT);
				else
				$emp_id = '';

				$user_data['employeeId'] = $emp_id;			
				$user_id = $user_model->SaveorUpdateUserData($user_data, '');
				
				$emp_data['user_id'] = $user_id;
				$emp_data['createdby'] = $loginUserId;
				$emp_data['createddate'] = gmdate("Y-m-d H:i:s");;
				$emp_data['isactive'] = 1;
				$emp_data['is_orghead'] = 1;
				$employeeModal->SaveorUpdateEmployeeData($emp_data, '');
				//end of saving into employee table.
				$tableid = $user_id;
				$actionflag = 1;
				$menuID = ORGANISATIONINFO;
				try 
				{
					$result = sapp_Global::logManager($menuID,$actionflag,$loginUserId,$tableid);
				}
				catch(Exception $e) { echo $e->getMessage();}
				
				$managementUsersData = $deptModel->getDeptHeads();
				$opt ='';   
				foreach($managementUsersData as $record)
				{
					$opt .= sapp_Global::selectOptionBuilder($record['id'], $record['userfullname']);
				}
				
				$this->view->managementUsersData = $opt;
				
				$this->view->eventact = 'added';
				$close = 'close';
				$this->view->popup=$close;
			}
			else 
			{
				$messages = $emp_form->getMessages();
				foreach ($messages as $key => $val)
				{
					foreach($val as $key2 => $val2)
					{
						$msgarray[$key] = $val2;
						break;
					}                                
				} 
				$this->view->msgarray = $msgarray;			
			}
		}
		//echo "<pre>"; print_r($msgarray);die;
		$this->view->controllername = $controllername;
		$this->view->emp_form = $emp_form;
	}
}