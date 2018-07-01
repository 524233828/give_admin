<?php
namespace Admin\Controller;
use Org\Wx\Wechat;

class WxmenuController extends BaseController {
	
    //微信菜单的数据模型
	private $wx_menu_mod;
	private $page_url;
	private $menu;

	/**
	* 初始化
	*/
	protected function _initialize(){
        parent::_initialize();
        $this->wx_menu_mod = D('wx_menu');
        $this->menu = $this->get_menu();
        $this->page_url = U('wx_menu/index');
    }

	/**
	* 列表
	*/
    public function index(){
        $this->assign('menu',$this->menu);
    	$this->display();
    }

    /**
	* 新增数据
	*/
    public function add() {
    	if (IS_POST) {
			$data = $this->wx_menu_mod->create();
			$data['event_content'] = json_encode(array('url' => $_POST['url']));
			if($this->wx_menu_mod->add($data)) {
				$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
			}else {
				$this->ajaxReturn(array('status' => 0, 'msg' => '操作失败！'));
			}
			exit;
		}
		$this->assign('menu_list',$this->menu);
		$this->display();
    }

    /**
	* 编辑数据
	*/
    public function edit() {
    	$id = $_REQUEST['id'];
    	if(empty($id)) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '请选择要编辑的菜单！'));
		}
    	if(IS_POST) {
			$data = $this->wx_menu_mod->create();
			$data['event_content'] = json_encode(array('url' => $_POST['url']));
			if($this->wx_menu_mod->save($data)) {
				$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
			}else {
				$this->ajaxReturn(array('status' => 0, 'msg' => '操作失败！'));
			}
			exit;
		}
		$menu = $this->wx_menu_mod->where('id='.(int)$id)->find();
		$menu['event_content'] = json_decode($menu['event_content'],true);
		$this->assign('menu',$menu);
		$this->assign('menu_list',$this->menu);
		$this->display();
    }

    /**
	* 删除
	*/
    public function delete() {
    	$id = I('post.id');
		if(empty($id)) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '请选择要删除的菜单！'));
		}
		if($id && is_array($id)) {
			$id = implode(',', $id);
		}
		$this->wx_menu_mod->where('id in('.$id.') or pid in('.$id.')')->delete();
		$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
    }

    /**
	* 设置状态
	*/
    public function status() {
    	$id = I('post.id');
    	$status = I('post.status');
		if(empty($id) && empty($status)) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '请选择要设置的菜单！'));
		}
		$this->wx_menu_mod->where('id='.(int)$id)->save(array('status' => $status));
		$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
    }

    /**
	* 排序
	*/
    public function sort() {
    	$id = I('post.id');
    	$list_sort = I('post.sort');
		if(empty($id) && empty($list_sort)) {
			$this->ajaxReturn(array('status' => 0, 'msg' => '请选择要设置的菜单！'));
		}
		$this->wx_menu_mod->where('id='.(int)$id)->save(array('list_sort' => $list_sort));
		$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
    }  

    /**
	* 生成菜单
	*/
    public function createmenu() {
    	$options = array('token' => $this->setting['wx_token'],
                         'encodingaeskey' => $this->setting['wx_encodingaeskey'],
                         'appid' => $this->setting['wx_appid'],
                         'appsecret' => $this->setting['wx_appsecret']
                        );
 	 	$weObj = new Wechat($options);
 	 	//获取菜单操作:
	   // $menu = $weObj->getMenu();
	    //设置菜单
	    $data = $this->process_menu();
	    
	    if($weObj->createMenu($data)){
	    	$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
	    }
	    $this->ajaxReturn(array('status' => 0, 'msg' => '操作失败！'));
    }


    /**
	* 撤销菜单
	*/
    public function cancelmenu() {
    	$options = array('token' => $this->setting['wx_token'],
                         'encodingaeskey' => $this->setting['wx_encodingaeskey'],
                         'appid' => $this->setting['wx_appid'],
                         'appsecret' => $this->setting['wx_appsecret']
                        );
 	 	$weObj = new Wechat($options);
 	 	if($weObj->deleteMenu()) {
 	 		$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
 	 	}
 	 	$this->ajaxReturn(array('status' => 0, 'msg' => '操作失败！'));
    }

    /**
	* 获取栏目
	*/
    private function get_menu() {
    	$menu = $this->wx_menu_mod->order('list_sort asc,pid asc')->select();
    	$menu = $this->process_cate($menu);
    	return $menu;
    } 

    private function process_menu() {
    	$menu = $this->menu;
    	$data = array();
    	$i = 0;
    	foreach ($menu as $k => $v) {

    		if($v['pid'] == 0 && $v['status'] == 1) {
    			$data[$i]['name'] = $v['name'];
    			$sub = array();
    			//是否有子级
    			$j = 0;
    			foreach ($menu as $sk => $sv) {
    				if($sv['pid'] == $v['id'] && $sv['status'] == 1) {
    					$event_content = json_decode($sv['event_content'],true);
    					array_push($sub, array('type' => 'view', 'name' => $sv['name'], 'url' => $event_content['url']));
    					$j++;
    				}
    			}
    			if(count($sub) >= 1) {
    				$data[$i]['sub_button'] = $sub;
    			}else {
    				$event_content = json_decode($v['event_content'],true);

    				$data[$i]['type'] = 'view';
    				$data[$i]['url'] = $event_content['url'];
    			}
    			$i++;
    		}
    	}
    	return array('button' => $data);
    } 
}