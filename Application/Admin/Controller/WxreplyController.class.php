<?php
namespace Admin\Controller;

class WxreplyController extends BaseController {

	/**
	* 初始化
	*/
	protected function _initialize(){
        parent::_initialize();

    }

	/**
	* 列表
	*/
    public function index(){
    	//关注回复
    	$subscribe  = D('wx_reply')->where('type="subscribe"')->find();
    	$this->assign('subscribe',$subscribe);

    	$this->display();
    }

    public function setreply() {
    	if(IS_POST) {
    		$data = D('wx_reply')->create();
    		$reply = D('wx_reply')->where('type="'.$data['type'].'"')->find();
    		if($reply) {
	    		if(D('wx_reply')->where('type="'.$reply['type'].'"')->save($data)) {
	    			$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
	    		}
	    	}else {
	    		if(D('wx_reply')->add($data)) {
	    			$this->ajaxReturn(array('status' => 1, 'msg' => '操作成功!'));
	    		}
	    	}
    	}
    	$this->ajaxReturn(array('status' => 0, 'msg' => '操作失败!'));
    }


}