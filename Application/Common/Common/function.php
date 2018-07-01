<?php

//根据广告标识获取广告JS
function getAd($name) {
      $str = '';
      $ad = D('ad')->where('name="'.$name.'"')->find();
      if($ad) {
          $str = '<script src="/Data/ad/'.$name.'.js" type="text/javascript"></script>';
      }
      return $str;
}

//获取子级类目
function pcate($cate,$cateid = 0) {
	$allcate = array();
	$i = 0;
	foreach ($cate as $key => $value) {
		if($value['pid'] == $cateid) {
			$allcate[$i] = $value;
			$allcate[$i]['sub'] = pcate($cate,$value['id']);
			$i++;
		}
	}
	return $allcate;
}

/**
 * 一位数组变成多维数组 无限分级
 * @param  [type] $items [description]
 * @param  string $pid [description]
 * @return array       [description]
 */
function gen_tree($items,$pid ="pid") {
    foreach ($items as &$it){
        $map[$it['id']] = &$it;   //数据的ID名生成新的引用索引树
    }
    $tree = [];
    foreach ($items as &$it){
        $parent = &$map[$it[$pid]];
        if($parent) {
            $parent['son'][] = &$it;
        }else{
            $tree[] = &$it;
        }
    }
    return $tree;
}


//格式化时间轴
function trans_time($time) {  
  $rtime = date("m-d H:i",$time);  
  $htime = date("H:i",$time);  
    
  $time = time() - $time;  

  if ($time < 60) {  
      $str = $time.'秒前';  
  }  
  elseif ($time < 60 * 60) {  
      $min = floor($time/60);  
      $str = $min.'分钟前';  
  }  
  elseif ($time < 60 * 60 * 24) {  
      $h = floor($time/(60*60));  
      $str = $h.'小时前 ';  
  }  
  elseif ($time < 60 * 60 * 24 * 3) {  
      $d = floor($time/(60*60*24));  
      if($d==1)  
         $str = '昨天';  
      else  
         $str = '前天';  
  }  
  else {  
      $str = $rtime;  
  }  
  return $str;  
}

function P($arr, $is_die=1){
  echo "<pre>";
  print_r($arr);
  echo "</pre>";
  if ($is_die) {
    die;
  }
}

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @param mixed $default 默认值
 * @return mixed
 */
function CC($name=null, $value=null,$default=null)
{
    static $_configs = [];
    if (!$_configs) {   //
      if (1 || S('configs_update') || !$_configs = S('configs')) {
          $_configs2 = M('config')->where(['status'=>1])->select();
          $_configs2 = gen_tree($_configs2);  //生成树
          $_configs = config_tree($_configs2);
          S('configs', $_configs);
          S('configs_update', null);
      }
    }
    // 无参数时获取所有
    if (empty($name)) {
        return $_configs;
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = ($name);
            if (is_null($value))
                return isset($_configs[$name]) ? $_configs[$name] : $default;
            $_configs[$name] = $value;
            return null;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0]   =  ($name[0]);
        if (is_null($value))
            return isset($_configs[$name[0]][$name[1]]) ? $_configs[$name[0]][$name[1]] : $default;
        $_configs[$name[0]][$name[1]] = $value;
        return null;
    }

}

/**
 * 生成配置文件
 * @param $configs
 * @return mixed
 */
function config_tree($configs) {
    $configs2 = [];
    foreach ($configs as $key => $value) {
        if ($value['son']) {
            $configs2[ $value['key'] ] = config_tree($value['son']);
        }else{
            $configs2[ $value['key'] ] = $value['value'];
        }
    }
    return $configs2;
}


/**
 * 字符串组建option类型 数组
 * @param  [string] $string [字符串 格式如下]
 * 0:不显示
 * 1:显示
 * @param string $type
 * @return array            [description]
 * list:一维数组
 * [1] => 男
 * [2] => 女
 *
 * edit:二维数组
 */
function option_arr($string, $type='edit'){
    $arr = explode("\n",$string);
    foreach ($arr as $key => $value) {
        $value = trim($value);
        $arr2 = explode(':',$value);
        if (!$arr2[0]===null)  return array();
        $arr[$key] = array();
        if ($type == 'list') {        //列表
            if (false!==strpos($arr2[0], '_ico')) {
              $arr3[trim($arr2[0])] = A('Api/Api')->imgUrl(trim($arr2[1]));
            }else{
              $arr3[trim($arr2[0])] = trim($arr2[1]);
            }

            $arr = $arr3;
        }else{
            // if (!$arr2[0])  return array();
            foreach ($arr2 as $k => $v) {
                $v = trim($v);
                $arr[$key][$k] = $v;
            }
        }
    }
    return $arr;
}

/**
 * 获取二维数组的子数组
 * @param  [type] &$info [二维数组]
 * @param  string $field [子数组的key]
 * @return array         [一维数组]
 */
function child_arr(&$info, $field='id'){
    foreach ($info as $key => $value) {
        $arr[] = $value[$field];
    }
    return $arr;
}

/**
 * 把大写转换成下划线
 * @param  [string] $str [description]
 * @return string
 */
function format_line($str){

    if(is_numeric($str))
        return $str;

    $temp_array = array();
    for($i=0; $i<strlen($str); $i++){
        $ascii_code = ord($str[$i]);
        if($ascii_code >= 65 && $ascii_code <= 90){
            if($i == 0){
                $temp_array[] = chr($ascii_code + 32);
            }else{
                $temp_array[] = '_'.chr($ascii_code + 32);
            }
        }else{
            $temp_array[] = $str[$i];
        }
    }
    return implode('',$temp_array);
}

/**
 * 把下划线转换成大写
 * @param  [string] $str [description]
 * @return string
 */
function format_upper($str){
    
    if (!strpos($str, '_'))
        return $str;

    $arr = explode('_', $str);
    $temp = '';
    foreach ($arr as $key => $value) {
        if ($key == 0) {
            $temp = $value;
        }else{
            $temp .= ucfirst($value);
        }
    }
    return $temp;
       
}

/**
 * 把数组的键下划线转换成大写
 * @param  [array] $array [数组 支持多维]
 * @return array
 */
function key_format_upper($array){
    $temp = array();
    if(is_array($array)){
        foreach ($array as $key => $value ){
            if(is_array($value)){
                $key = format_upper($key);
                $temp[$key] = key_format_upper($value);
            }
            else{
                $key = format_upper($key);
                $temp[$key] = $value;
            }
        }
    }

    return $temp;

}

/**
 * 把数组的键大写转换成下划线
 * @param  [array] $array [数组 支持多维]
 * @return array
 */
function key_format_line($array){
    $temp = array();
    if(is_array($array)){
        foreach ($array as $key => $value ){
            if(is_array($value)){
                $key = format_line($key);
                $temp[$key] = key_format_line($value);
            }
            else{
                $key = format_line($key);
                $temp[$key] = $value;
            }
        }
    }

    return $temp;

}

