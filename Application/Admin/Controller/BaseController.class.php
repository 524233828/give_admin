<?php
namespace Admin\Controller;

use Common\Common\Wechat;
use Think\Controller;
use Think\Page;

class BaseController extends Controller
{
    private $redis;

    /**
     * 初始化
     */
    protected function _initialize()
    {
        //检查是否登录
        $this->checkLogin();
    }

    /**
     * 检查是否登录
     */
    protected function checkLogin()
    {
        if (!($admin_info = session('admin_info')) && CONTROLLER_NAME != 'Admin') {
            redirect(U('admin/login'));
            return;
        }
        $this->assign('admin_info', $admin_info);
    }

    /**
     * 是否开启调试模式
     * @return bool
     */
    protected function isDebug()
    {
        if ('prod' === $this->getEnv()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 获取redis对象
     * @return \Redis;
     */
    public function getRedis(){
        $arr = require APP_PATH . '/Common/Conf/redis.php';
        $arr = $arr[APP_STATUS];
        $this->redis = new \Redis();
        $this->redis->connect($arr['host'], $arr['port']);
        $this->redis->auth($arr['auth']);
        $this->redis->select($arr['db']);

        return $this->redis;

    }

    /**
     * 公共的列表显示
     * @param  [type] $table [表名]
     */
    public function lists($table = null){

        if(!$table){
            if (I('get.table')) {
              $table = I('get.table');
            }else{
              $table = session('table');
            }
        }

        list($model, $attributes) = $this->modelDatas($table,'list');

        //状态
        if( $status = I('status')){
            $where['status'] = ['IN', $status];
        }else{
            $where['status'] = 1;
        }

        //查询条件
        if (I('where')) {
            $w_arr = explode(',', I('where'));
            foreach ($w_arr as $key => $value) {
                list($w_k, $w_v) = explode(':', $value);
                $where[$w_k] = $w_v;
            }
        }

        //搜索关键词
        $search_list = option_arr($model['search_list']);


        if (I('search_k') == null) {
            $search_k = session('search_k.'.$table);
            $search_v = session('search_v.'.$table);
            if ($search_k) {
                foreach ($search_k as $k => $item) {
                    $search_v && $where[$item] = ['LIKE','%' . $search_v[$k] . '%'];
                }
            }

        } else {
            $search_k = I('search_k');
            $search_v = I('search_v');
            session('search_k.'.$table, $search_k ? : null);
            session('search_v.'.$table, $search_v ? : null);
            foreach ($search_list as $key => $value) {
                foreach ($search_k as $k => $item) {
                    if ($item == $value[1]) {
                        $search_v[$k] && $where[$item] = ['LIKE','%' . $search_v[$k] . '%'];
                    }
                }
            }
            
        }
        $page = $this->page($table,$where);
        $list = M($table)->where($where)->order($model['list_sort']?:' id desc')->limit($page['limit'])->select();
        foreach ($list as $key => $value) {
            foreach ($attributes as $k => $v) {
                if($v['type']=='img'){
                    $imgs[$k][] = $value[ $v['name'] ];
                }else if($v['type']=='imgs'){
                    $imgs_temp = explode(',', $value[ $v['name'] ]);
                    foreach ($imgs_temp as $value) {
                        $imgs[$k][] = $value;
                    }
                }
            }
        }
        if (!empty($imgs)) {
            $img2 = [];
            foreach ($imgs as $value) {
                $img2 = array_merge($img2,$value);
            }
            $img2 = array_unique($img2);
            $paths = M('image')->where([ 'id'=>['IN', $img2]])->getField('id,img_url');
            foreach ($list as &$value) {
                foreach ($attributes as $k => $v) {
                    if($v['type']=='img'){
                        $value[ $v['name'] ] = $paths[$value[$v['name']] ];
                    }else if($v['type']=='imgs'){
                        $imgs_temp = explode(',', $value[$v['name']]);
                        $value[$v['name']] = [];
                        foreach ($imgs_temp as $vv) {
                            $value[$v['name']][] = $paths[$vv];
                        }
                    }
                }            
            }
        }

        $this->assign('attributes', $attributes);
        $this->assign('list', $list);
        $this->assign('table', $table);
        $this->assign('page', $page['show']);
        $this->assign('search_list', $search_list);
    }

    /**
     * 公共编辑页
     * @param  [type] $to_url [description]
     */
    public function edit($to_url = null)
    {
        $table = I('table')?:session('table');
        $id = I('id');
        is_array($id) && $id = $id[0];

        list($model, $attributes) = $this->modelDatas($table,'edit');

        if (IS_POST) {
            $data = M($table)->create();
            foreach ($attributes as $key => $value) {
                if ($value['type']=='ctime') {    //添加时间
                    // $data[$value['name']] = time();
                    $data[$value['name']] = strtotime($data[$value['name']]);
                }else if($value['type']=='time'){
                    $data[$value['name']] = strtotime($data[$value['name']]);
                }
            }

            unset($data['table']);
            if ($id) {
                $where['id'] = $id;
                unset($data['id']);
                $res = $data = M($table)->where($where)->save($data);

            }else{

                $res = $data = M($table)->add($data);
            }
            
            if ($res>0) {
                $this->success('成功',$to_url?:U('Common/lists',array('table'=>$table, 'p'=>I('post.p'))));
            }else{
                $this->error('您没有修改数据');
            }

        } else {
            $data = [];
            if ($id) $data = M($table)->find($id);

            $this->assign('data', $data);
            $this->assign('attributes',$attributes);
        }

    }

    public function modelDatas($table, $type='')
    {
        session('table',$table);
        $model = M('model')
            ->where(['name'=>$table,'status'=>1])
            ->field('template_add,template_list,template_edit,list_grid,search_key,search_list,list_row,field_sort,list_sort')
            ->find();
        $attributes = M('attribute')
            ->where(['model_name'=>$table,'status'=>1])
            ->field('id,model_name,name,title,default,type,extra,remark,is_show,is_must,list_width')
            ->order($model['field_sort']? : ' sort desc, id asc')
            ->select();
        if ($type=='list') {
            foreach ($attributes as $key => $value) {
                if ($value['is_show'] % 2 == 0) {   //去除在列表隐藏的字段 1:列表 2:添加 4:编辑
                    unset($attributes[$key]);
                }
            }            
        }

        return [$model, $attributes];
    }

    protected function page($table, $where, $row=20){
        $count = M($table)->where($where)->count();
        $Page  = new Page($count,$row);
        $show  = $Page->show();
        $limit = $Page->firstRow.','.$Page->listRows;
        return [
            'show'=>$show,
            'limit'=>$limit,
        ];
    }

    /**
     * 获取微信头像
     * @param $url
     * @return null|resource
     */
    public function getWxAvatarIm($url)
    {
        set_time_limit(0);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $file = curl_exec($curl);
        curl_close($curl);
        if ($file) {
            return  imagecreatefromstring($file);
        } else {
            return null;
        }

    }

    /**
     * 通过app_id获取微信
     * @param $app_id
     * @return Wechat
     */
    public function getWechatByAppId($app_id = 0)
    {
        return new Wechat(
            [
                'token' => C('wx_token'),
                'encodingaeskey' => C('wx_aes_key'),
                'appid' => C('wx_appid'),
                'appsecret' => C('wx_app_secret'),
                'debug' => CC('debug'),
                'logcallback' => function ($content, $type) use ($app_id) {
                    $data['data'] = $content;
                    $data['create_time'] = time();
                    $data['app_id'] = $app_id;
                    $data['type'] = $type;
                    M('log')->add($data);
                }
            ]
        );
    }

}