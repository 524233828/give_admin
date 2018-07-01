<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace Think\Template\TagLib;
use Think\Template\TagLib;
/**
 * YICMS标签库解析类
 */
class Yicms extends TagLib {

    // 标签定义
    protected $tags   =  array(
        // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
        'yilist'    =>  array('attr'=>'name,id,key,mod,empty,field,where,order,limit,cateid','level'=>3,'alias'=>'iterate'),
        );

    /**
     * yilist标签解析 循环输出数据集
     * 格式：
     * <yilist name="user" id="user" empty="" >
     * {user.username}
     * {user.email}
     * </yilist>
     * @access public
     * @param array $tag 标签属性
     * @param string $content  标签内容
     * @return string|void
     */
    public function _yilist($tag,$content) {
        $name  =    $tag['name'];   //表名
        $id    =    $tag['id'];     //内部变量名
        $empty =    isset($tag['empty'])?$tag['empty']:'';
        $key   =    !empty($tag['key'])?$tag['key']:'i';
        $mod   =    isset($tag['mod'])?$tag['mod']:'2';
        $field =    isset($tag['field'])?$tag['field']:'*'; //选中字段
        //$where =    isset($tag['where'])?$tag['where']:'1=1';  //where条件
        $order =    isset($tag['order'])?$tag['order']:'id desc'; //默认主键ID倒叙
        $limit =    isset($tag['limit'])?$tag['limit']:'10'; //默认10条数据

        if(isset($tag['where'])) {
            $where = str_replace('gt', '>', $tag['where']);
            $where = str_replace('lt', '<', $where);
        }else {
            $where = '1=1';
        }
        //当前分类
        if(isset($tag['cateid'])) {
            $where .= ' and cate_id=\'".$'.trim($tag['cateid']).'."\'';
        }

        $parseStr   =  '<?php ';
        if(isset($tag['page']) && $tag['page'] == 1) {
            $parseStr .= '$total = D("'.$name.'")->where("'.$where.'")->count();';
            $parseStr .= '$page = new \Think\Page($total,'.$limit.');';
            $parseStr .= '$result = D("'.$name.'")->field("'.$field.'")->where("'.$where.'")->limit($page->firstRow.",".$page->listRows)->order("'.$order.'")->select();';
            $parseStr .= '$page = $page->show();';
        }else {
            $parseStr .= '$result = D("'.$name.'")->field("'.$field.'")->where("'.$where.'")->limit("'.$limit.'")->order("'.$order.'")->select();';
            if(isset($tag['sub']) && $tag['sub'] == 1) {
                $parseStr .= '$result = pcate($result);';
            }
        }
        $parseStr .= ' $__LIST__ = $result;';
        $parseStr .= 'if( count($__LIST__)==0 ) : echo "'.$empty.'" ;';
        $parseStr .= 'else: ';
        $parseStr .= 'foreach($__LIST__ as $key=>$'.$id.'): ';
        $parseStr .= '$mod = ($'.$key.' % '.$mod.' );';
        $parseStr .= '++$'.$key.';?>';
        $parseStr .= $this->tpl->parse($content);
        $parseStr .= '<?php endforeach; endif; ?>';
       /*var_dump($parseStr);
       exit;*/
        if(!empty($parseStr)) {
            return $parseStr;
        }
        return ;
    }

}
