<?php
namespace Common\Common;

/**
 * 工具类
 * @author liuwenwei
 *
 */
class Util{
	/**
	 * 生成随机字符串
	 * @param integer $length 生成字符串位数
	 * @return string
	 */
	public static function getRandStr($length){
		$char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
		$str = '';
		for($i=0; $i < $length; $i++){
			$str = $str.$char[mt_rand(0,strlen($char)-1)];
		}
		return $str;
	}
	
	/**
	 * 生成随机数字
	 * @param integer $length 生成数字的位数
	 * @return string
	 */
	public static function getRandNum($length){
		$str = '';
		for($i=0; $i < $length; $i++){
			$str = $str.mt_rand(0, 9);
		}
		return $str;
	}

	/**
	 * 写日志
	 * @param string $content 日志内容
	 * @param string $file 日志保存路径
	 */
	public static function addLog($content, $file=null){
		if($file == null){
			$file = 'runtime/log.txt';
		}
		
		file_put_contents($file, "\n"."\n".'【'.date("Y-m-d H:i:s").'】'.$content, FILE_APPEND);
	}
	
	/**
	 * 过滤emoji表情
	 * @param string $str 待过滤字符串
	 * @return string
	 */
	public static function filterEmoji($str){
		$str = preg_replace_callback('/./u',function(array $match){
					return strlen($match[0]) >= 4 ? '' : $match[0];
				},$str);
		return $str;
	}
}