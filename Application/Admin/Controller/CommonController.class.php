<?php
namespace Admin\Controller;

class CommonController extends BaseController {


	public function index(){
		
	}

    /**
     * 通用列表
     * @param null $table
     */
	public function lists($table = null)
	{
		parent::lists();
		$this->display();
	}

    /**
     * 通用列表
     * @param null $to_url
     */
	public function edit($to_url = null)
	{
        parent::edit();
		$this->display();
	}

	/**
	 * 模型 / 属性添加
	 */
	public function modelAdd()
	{
		$table = I('table');
		$table_title = I('title');
		if ($table) {
			try {
			  	$columns = M()->query('show full columns from '.C('DB_PREFIX').$table);
			} catch (\Exception $e) {
				$show_text[] = '表 '.$table.' 不存在';
			  	$this->assign('table',$table);
			  	$this->assign('title',$table_title);
			  	$this->assign('show_text',$show_text);
				$this->display();
				die;
			}

		  	$model_data = M('model')->where(['name'=>$table])->field('id, title')->find();
		  	if (!$model_id = $model_data['id']) {
		  		$model_data = array(
			  		'name'=> $table,
			  		'title'=> $table_title,
			  		'createtime'=> time(),
			  		);
			  	$model_id = M('model')->add($model_data);
				$show_text[] = "<div class='label-primary' >添加模型:".$table."成功</div>";


		  	}else{
		  		($model_data['title'] != $table_title) && M('model')->where(['id'=>$model_id])->save(['title'=>$table_title]);
		  		$show_text[] = "<div class='label-primary' >模型:".$table." 已经存在</div>";
		  	}

		  	$fields_selected = I('fields');
		  	foreach ($columns as $key => $value) {
				$comment = $value['comment']? : $value['field'];
				$fields[] = ['field'=>$value['field'], 'comment'=>$comment];
		  		if ($fields_selected && in_array($value['field'], $fields_selected)) {
			  		$attr_id = M('attribute')->where(array('model_name'=>$table,'name'=>$value['field']))->getField('id');
			  		if (!$attr_id) {
			  			$attr_data = array(
					  		'model_name'=>$table,
					  		'model_id'=>$model_id,
					  		'name'=>$value['field'],
					  		'title'=>$value['comment']? : $value['field'],
					  		'default'=>$value['default']?:'',
					  		'field'=>$value['type'],
					  		'is_show'=>$value['comment']? 7 : 7,
					  		);
					  	M('attribute')->add($attr_data);

					  	$show_text[] = "<div class='label-primary' >添加字段".$value['field'].'-'.$comment." 成功</div>";
			  		}else{
			  			$show_text[] = "<div class='label-danger' >字段".$value['field'].'-'.$comment." 已经存在</div>";
			  		}
		  		}
		  	}
		  	$this->assign('table',$table);
		  	$this->assign('title',$table_title);
		  	$this->assign('show_text',$show_text);
		  	$this->assign('fields',$fields);
			$this->display();
		}else{
			$this->display();
		}
	}

	/**
	 * 删除
	 */
    public function delete()
    {
    	$table = I('table');
    	$where['id'] = I('id');
    	if($table && $where['id']){
    		$force = I('key');
    		$key = I('key');
    		if ( $force && $key ) {	//强制删除
    			if($this->parCode($key) == $table .'_'. $where['id']){

			    	$res = M($table)->where($where)->delete();
			    	if ($res>0) {
				    	$this->success('删除成功',2);
			    	}else{
				    	$this->error('删除失败',2);
			    	}
    			}

    		}else{			//弱性删除
		    	$data['status'] = -1;
		    	$res = M($table)->where($where)->save($data);
		    	if ($res>0) {
			    	$this->success('删除成功',2);
		    	}else{
			    	$this->error('删除失败',2);
		    	}    			
    		}



    	}else{
		    $this->error('非法操作');
    	}
	}
	
	/**
	 * 是否显示
	 */
	public function isShow()
	{
		$table = I('post.table');
		$cate = I('post.cate');
		$where['id'] = I('post.id');
		if ($table && $cate) {
			if ($cate==2) {
				$data['is_show'] = 0; //不显示
			}else{
				$data['is_show'] = 1; //显示
			}
			$res = M($table)->where($where)->save($data);
			if ($res>0) {
				$this->success($res);
			}else{
				$this->error($res);
			}
		}

	}

	/**
	 * 获取外键表的数据
	 * attribute表 type: fkey
	 * 
	 * attribute表 extra字段的格式如下:
	 * ftable:userinfo  //外键关联表
	 * to_key:id 		//外键对应关联表的字段
	 * to_field:		//外键直接转换成的字段
	 * link:			//点击跳转的链接
	 * fields:portrait|portrait,nickname,phone,email
	 */
	public function getForeignKey()
	{
		$fvalue = I('post.fvalue');
		$where['model_table'] = I('post.table');
		$where['name'] = $fkey = I('post.fkey');
		$extra = M('attribute')->where($where)->getField('extra');
		$extra_arr = option_arr($extra,'list');
		$fields_arr = explode(",",$extra_arr['fields']);
        $types = $fields = [];
		foreach ($fields_arr as $key => $value) {
			list($field, $type) = explode("|",$value);
			$fields[] = $field;
			$types[$field] = $type ? $type : 'string';
		}

		$to_key = $extra_arr['to_key'] ? : 'id';
		$where2[$to_key] = $fvalue;
		$info = M($extra_arr['ftable'])->field($fields)->where($where2)->find();
        $info2 = [];
        foreach ($info as $key => $value) {
            $info2[$key]['type'] = $types[$key] ? : 'string';
            switch ($info2[$key]['type']) {
                case 'img':
                    $info2[$key]['value'] = D('image')->imgUrl($value);
                    break;
                case 'imgs':
                    $info2[$key]['value'] = D('image')->imgUrl(explode(',', $value)[0]);
                    break;
                default:
                    $info2[$key]['value'] = $value;
                    break;
            }
		}

        $this->success(['list' => $info2, 'extra' => $extra_arr]);
	}

	public function clearCache(){
        $admin_info = session('admin_info');
        session('[destroy]');
        S('configs_update', 1);
        session('admin_info', $admin_info);
        $this->success('清除成功!');
	}

	public function updateConfigs(){
		S('configs_update', 1);
		S('cache_update', 1, ['expire' => 5]);
        $this->success('更新成功!');
	}


}