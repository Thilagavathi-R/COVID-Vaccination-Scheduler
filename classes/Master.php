<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			if(isset($sql))
			$resp['sql'] = $sql;
			return json_encode($resp);
			exit;
		}
	}
	function save_location(){
		extract($_POST);
		$data = "";
		$_POST['description'] = addslashes(htmlentities($_POST['description']));
		foreach($_POST as $k=> $v){
			if($k != 'id'){
				if(!empty($data)) $data.=", ";
				$data.=" {$k} = '{$v}'";
			}
		}
		$check = $this->conn->query("SELECT * FROM `location` where `location` = '{$location}' ".(!empty($id) ? "and id != {$id}" : ''))->num_rows;
		$this->capture_err();
		if($check > 0){
			$resp['status'] = 'failed';
			$resp['msg'] = "Location already set.";
		}else{
			if(empty($id)){
				$sql = "INSERT INTO `location` set $data";
				$save = $this->conn->query($sql);
			}else{
				$sql = "UPDATE `location` set $data where id = {$id}";
				$save = $this->conn->query($sql);
			}
			$this->capture_err();

			if($save){
				$resp['status'] = "success";
			}else{
				$resp['status'] = "failed";
				$resp['sql'] = $sql;
			}
		}
		return json_encode($resp);
	}

	function delete_location(){
		$sql = "DELETE FROM `location` where id = '{$_POST['id']}' ";
		$delete = $this->conn->query($sql);
		$this->capture_err();
		if($delete){
			$resp['status'] = 'success';
		}else{
			$resp['status'] = "failed";
			$resp['sql'] = $sql;
		}
		return json_encode($resp);
	}

	function save_schedule(){
		extract($_POST);
		$loop = true;
		while(true){
			$code = mt_rand(1,999999999999);
			$code = sprintf("%'.012d",$code);
			$chk = $this->conn->query("SELECT * FROM `individuals` where code = '{$code}' ")->num_rows;
			if($chk <= 0)
				break;
		}
		$sql = "INSERT INTO `individuals` set name = '{$name}', `code` = '{$code}'  ";
		$save_inv = $this->conn->query($sql);
		$this->capture_err();
		if($save_inv){
			$id = $this->conn->insert_id;
			$sql = "INSERT INTO `schedules` set location_id='{$lid}', date_sched = '{$date_sched}',individual_id = '{$id}' ";
			$save_sched = $this->conn->query($sql);
			$this->capture_err();
			$data = "";
			foreach($_POST as $k=> $v){
				if(!in_array($k,array('lid','date_sched'))){
					if(!empty($data)) $data .=", ";
					$data .= " ({$id},'{$k}','{$v}')";
				}
			}
			$sql = "INSERT INTO `individual_meta` (individual_id,meta_field,meta_value) VALUES $data ";
			$save_meta = $this->conn->query($sql);
			$this->capture_err();
			if($save_sched && $save_meta){
				$resp['status'] = 'success';
				$resp['name'] = $name;
				$resp['code'] = $code;
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = "There's an error while submitting the data.";
			}

		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "There's an error while submitting the data.";
		}
		return json_encode($resp);
	}
	function multiple_action(){
		extract($_POST);
		if($_action == 'no_show' || $_action =='done'){
			$status = $_action == 'no_show' ? 2 : 1;
			$sql = "UPDATE `schedules` set status = '{$status}' where individual_id in (".(implode(",",$ids)).") ";
			$process = $this->conn->query($sql);
			$this->capture_err();
		}else{
			$sql = "DELETE s.*,i.*,im.* from  `schedules` s inner join `individuals` i on s.individual_id = i.id  inner join `individual_meta` im on im.individual_id = i.id where s.individual_id in (".(implode(",",$ids)).") ";
			$process = $this->conn->query($sql);
			$this->capture_err();
		}
		if($process){
			$resp['status'] = 'success';
			$act = $_action == 'delete' ? "Deleted" : "Updated";
			$this->settings->set_flashdata("success","Individual/s successfully ".$act);
		}else{
			$resp['status'] = 'failed';
			$resp['error_sql'] = $sql;
		}
		return json_encode($resp);
	}
	
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_location':
		echo $Master->save_location();
	break;
	case 'delete_location':
		echo $Master->delete_location();
	break;
	case 'save_schedule':
		echo $Master->save_schedule();
	break;
	case 'multiple_action':
		echo $Master->multiple_action();
	break;
	default:
		// echo $sysset->index();
		break;
}