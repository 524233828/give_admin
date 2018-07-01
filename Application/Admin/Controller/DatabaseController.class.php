<?php
namespace Admin\Controller;

class DatabaseController extends BaseController {
	
	/**
	* 初始化
	*/
	protected function _initialize(){
        parent::_initialize();
    }

    public function index() {
    	if(isset($_GET['data'])) {
    		$data = $_GET['data'];
    		if($data == 'backup') {
    			$this->backup();
    		}
    		if($data == 'restore') {
    			$this->restore();
    		}
    	}
    }

    /**
	* 备份
	*/
    public function backup() {
    	if(IS_POST){
    		$tables = I('post.tables');
    		if(empty($tables)){
				$this->ajaxReturn(array('status' => 0, 'msg' => '请选择需要备份的数据库表！'));
			}	
			$filesize = intval(I('post.filesize'));
			if ($filesize < 512) {
				$this->ajaxReturn(array('status' => 0, 'msg' => '出错了,请为分卷大小设置一个大于512的整数值！'));
			}
			$file = DATA_PATH.'backup/';
			$random = mt_rand(1000, 9999);
			$sql = ''; 
			$p = 1;
			foreach($tables as $table){
				$rs = M(str_replace(C('DB_PREFIX'),'',$table));
				$array = $rs->select();
				$sql.= "TRUNCATE TABLE `$table`;\n";
				foreach($array as $value){
					$sql.= $this->insertsql($table, $value);
					if (strlen($sql) >= $filesize*1000) {
						$filename = $file.date('Ymd').'_'.$random.'_'.$p.'.sql';
						$this->write_file($filename,$sql);
						$p++;
						$sql='';
					}
				}
			}
			if(!empty($sql)){
				$filename = $file.date('Ymd').'_'.$random.'_'.$p.'.sql';
				$this->write_file($filename,$sql);
			}
			$this->ajaxReturn(array('status' => 1, 'msg' => '数据库分卷备份已完成,共分成'.$p.'个sql文件存放！'));
			exit;
    	}
    	$tables = $this->get_tables();
		$this->assign('tables',$tables);
    	$this->display('backup');
    }

    //生成SQL备份语句
	private function insertsql($table, $row){
		$sql = "INSERT INTO `{$table}` VALUES ("; 
		$values = array(); 
		foreach ($row as $value) { 
			$values[] = "'" . mysql_escape_string($value) . "'"; 
		} 
		$sql .= implode(', ', $values) . ");\n"; 
		return $sql;
	}

    /**
	* 还原
	*/
    public function restore(){
    	$restore = array();
    	$filepath = DATA_PATH.'backup/*.sql';
		$filearr = glob($filepath);
		if (!empty($filearr)) {
			foreach($filearr as $k=>$sqlfile){
				preg_match("/([0-9]{8}_[0-9a-z]{4}_)([0-9]+)\.sql/i",basename($sqlfile),$num);
				$restore[$k]['filename'] = basename($sqlfile);
				$restore[$k]['filesize'] = round(filesize($sqlfile)/(1024*1024), 2);
				$restore[$k]['maketime'] = date('Y-m-d H:i:s', filemtime($sqlfile));
				$restore[$k]['pre'] = $num[1];
				$restore[$k]['number'] = $num[2];
				$restore[$k]['path'] = DATA_PATH.'backup/';
			}
		}
		$restore = array_reverse($restore);
		$count = count($restore);
		$page = new \Think\Page($count,$this->pagesize);
    	$start = $page->firstRow;
    	$end = $start + $page->listRows > $count ? $count : $page->listRows;
    	$restore = array_slice($restore, $start, $end);
		
		$this->assign('restore',$restore);
        $this->assign('page',$page->show());
    	$this->display('restore');
    }

    /**
	* 恢复
	*/
    public function recover() {
    	$rs = new \Think\Model();
		$pre = I('get.pre');
		$number = I('get.number') ? intval(I('get.number')) : 1;
		$filename = $pre.$number.'.sql';
		$filepath = DATA_PATH.'backup/'.$filename;
		if(file_exists($filepath)){
			$sql = $this->read_file($filepath);
			$sql = str_replace("\r\n", "\n", $sql); 
			foreach(explode(";\n", trim($sql)) as $query) {
				$rs->execute(trim($query));
			}
			//$url = U('database/recover',array('pre' => $pre,'number' => $number + 1));
			$this->ajaxReturn(array('status' => 1, 'msg' => '第'.$number.'个备份文件恢复成功,准备恢复下一个,请稍等！'));
		}else{
			$this->ajaxReturn(array('status' => 0, 'msg' => '无此文件！','url' => U('database/restore')));
		}
    }

    /**
	* 下载还原
	*/
	public function down(){
		$filepath = DATA_PATH.'backup/'.I('get.filename');
		if (file_exists($filepath)) {
			$filename = basename($filepath);
			$filetype = trim(substr(strrchr($filename, '.'), 1));
			$filesize = filesize($filepath);
			header('Cache-control: max-age=31536000');
			header('Expires: '.gmdate('D, d M Y H:i:s', time() + 31536000).' GMT');
			header('Content-Encoding: none');
			header('Content-Length: '.$filesize);
			header('Content-Disposition: attachment; filename='.$filename);
			header('Content-Type: '.$filetype);
			readfile($filepath);
			exit;
		}else{
			$this->ajaxReturn(array('status' => 0, 'msg' => '出错了,没有找到分卷文件！'));
		}
	}

    /**
	* 删除备份
	*/
    public function delete_backup() {
    	$filename = trim(I('get.filename'));
		@unlink(DATA_PATH.'backup/'.$filename);
		$this->ajaxReturn(array('status' => 1, 'msg' => $filename.'已经删除！'));
    }

    /**
	* 获取所有数据表
	*/
     private function get_tables(){
		$tables = \Think\Db::getInstance()->getTables(); 
		return $tables;
	}
  
}