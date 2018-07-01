<?php
namespace Admin\Controller;

class RoleController extends BaseController {
	
    //角色的数据模型
	private $role_mod;
	private $admin_mod;
	private $page_url;

	/**
	* 初始化
	*/
	protected function _initialize(){
        parent::_initialize();
        $this->role_mod = M('role');
        $this->page_url = U('role/index');
		$this->admin_mod = M('admin');
    }

	/**
	* 列表
	 *
	*/
    public function index(){
    	$count = $this->role_mod->count();
		$page = new \Think\Page($count,$this->pagesize);
        $role = $this->role_mod->limit($page->firstRow.','.$page->listRows)->order('id desc')->select();
        $page_str = $page->show();
        $this->assign('page',$page_str);
        $this->assign('role',$role);
    	$this->display();
    }

    /**
	* 新增数据
	*/
    public function add() {
    	if (IS_POST) {
			$data = $this->role_mod->create();
			$data['created_time'] = time();
			$data['node_ids'] = implode(',', $data['node_ids']);
			$data['status'] = 1;
			if($this->role_mod->add($data)) {
				$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
			}else {
				$this->ajaxReturn(array('status' => 0, 'msg' => '操作失败！'));
			}
			exit;
		}
		$this->display();
    }

    /**
	* 编辑数据
	*/
    public function edit() {
    	$id = $_REQUEST['id'];
    	if(empty($id)) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '请选择要编辑的角色！'));
		}
    	if(IS_POST) {
			$data = $this->role_mod->create();
			$data['node_ids'] = implode(',', $data['node_ids']);
			if($this->role_mod->save($data)) {
				session('node_ids', null);
				$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
			}else {
				$this->ajaxReturn(array('status' => 0, 'msg' => '操作失败！'));
			}
			exit;
		}
		$role = $this->role_mod->where('id='.(int)$id)->find();
		$this->assign('role',$role);
		$this->display();
    }

    /**
	* 删除
	*/
    public function delete() {
    	$id = I('post.id');
		if(empty($id)) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '请选择要删除的角色！'));
		}
		if($id && is_array($id)) {
			$id = implode(',', $id);
		}
		$this->role_mod->delete($id);
		$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
    }

	/**
	 * @addBy:JoseChan
	 * @editTime: 2017.05.09 10:04
	 * @e-mail: chenyu01@linghit.com
	 */

	public function status()
	{
		$id = I('post.id');
		$status = I('post.status');
		if(empty($id)||$status===false) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '请选择要冻结/解禁的角色！'));
		}
		if($id && is_array($id)) {
			$id = implode(',', $id);
			$where = "id IN ({$id})";
			$awhere = "role_id IN ({$id})";
		}else{
			$where = "id={$id}";
			$awhere = "role_id={$id}";
		}

		D()->startTrans();
		$role_effect = $this->role_mod->where($where)->save(["status"=>$status]);
		if($status==0){
			$admin_effect = $this->admin_mod->where($awhere." AND status=1")->save(['status'=>2]);
		}else{
			$admin_effect = $this->admin_mod->where($awhere." AND status=2")->save(['status'=>1]);
		}
		if($role_effect!==false&&$admin_effect!==false){
			D()->commit();
			$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
		}else {
			D()->rollback();
			$this->ajaxReturn(array('status' => 0, 'msg' => '操作失败!'));
		}

	}

}