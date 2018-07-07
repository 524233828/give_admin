<?php
namespace Admin\Controller;

class IndexController extends BaseController {
	
    public function index(){
        $menus = M('admin_menu')->where(['status' => 1])->order('sort ASC,id asc')->select();
        $menus = gen_tree($menus);

        $this->assign('admin_info', session('admin_info'));
        $this->assign('menus', $menus);
        $this->display();
    }

    public function main() {
    	$this->display();
    }

    public function welcome()
    {
        $this->display();
    }

}