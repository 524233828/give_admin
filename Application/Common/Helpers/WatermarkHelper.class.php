<?php
/**
 * User: liwenye
 * Date: 2018/4/20 
 */

namespace Common\Helpers;

class WatermarkHelper
{
	public $bg_image;			//背景图片信息
	public $canvas;				//画布信息
	public $water_images;		//水印图片信息
	public $water_texts;		//水印文字信息
    public $ttf_path;			//字体地址
    const TYPE_IMAGE = 1;
    const TYPE_TEXT = 2;
    const WIDTH_NO_CHANGE_LINE = 0;

    public function __construct()
	{
		
	}

    /**
     * 创建图片
     * @param $config_new
     * @return $this [type]         [description]
     */
	public function create($config_new)
	{
		$config = [
			'im' => null,
			'width' => 500,		//宽
			'height' => 500,	//高
			'save_path' => '',	//保存地址
		];
		$this->canvas = $this->updateConfig($config_new, $config);
		return $this;
	}

    /**
     * 背景图
     * @param  [string/im] $path   [图片地址/图片资源]
     * @return $this [type]               [description]
     */
	public function bgImage($path)
	{
		$config = [
			'im' => null,
			'path' => '',
		];
		if (is_string($path)) {		//地址
			$config['path'] = $path;
			$this->bg_image = $config;
		} else {					//资源
			$config['im'] = $path;
			$this->bg_image = $config;
		}

		return $this;
	}

    /**
     * 添加水印图片
     * @param $config_new
     * @return $this [type]         [description]
     */
	public function waterImage($config_new)
	{
		//默认值
		$config = [
			'identify' => null,
            'im' => null,
			'position' => 1,
            'path' => '',
            'width' => $this->canvas['width'],
            'height' => $this->canvas['height'],
            'percent' => 100,
            'offset' => '0,0'
		];

		$config = $this->updateConfig($config_new, $config);
		if ($config['identify'] === null) {
			$this->water_images[] = $config;
		} else {
			$this->water_images[$config['identify']] = $config;
		}

		return $this;

	}

    /**
     * 添加水印文字
     * @param $config_new
     * @return $this [type]         [description]
     */
	public function waterText($config_new)
	{
		//默认值
		$config = [
			'identify' => null,
            'position' => 1,
            'text' => '',
            'ttf' => $this->ttf_path,
            'size' => 16,
            'angle' => 0,
            'width' => 0,
            'height' => 0,
            'color' => '#000000',
            'offset' => '0,0'
		];

		$config = $this->updateConfig($config_new, $config);

		if ($config['identify'] === null) {
			$this->water_texts[] = $config;
		} else {
			$this->water_texts[$config['identify']] = $config;
		}

		return $this;
	}

    /**
     * 保存在本地
     * @param  [type] $path [description]
     * @return bool [type]       [description]
     */
	public function save($path)
	{
		$this->createCanvas();
		$this->addBgImage();
		$this->addwaterImages();
		$this->addwaterTexts();

		$this->canvas['save_path'] = $path;
		return $this->saveImage();
	}

    /**
     * [base64 description]
     * @return string [type] [description]
     */
	public function base64()
	{
		$this->createCanvas();
		$this->addBgImage();
		$this->addwaterImages();
		$this->addwaterTexts();
		return $this->getBase64();
	}


    /**
     * 创建画布
     * @return $this [type] [description]
     */
    private function createCanvas()
    {
    	if (!$this->canvas['im']) {
	    	$this->canvas['im'] = imagecreatetruecolor($this->canvas['width'], $this->canvas['height']);
    	}
    	return $this;
    }

    /**
     * 添加背景
     */
    private function addBgImage()
    {
    	if (!$this->bg_image['im']) {
			$this->bg_image['im'] = $this->getImagesSource($this->bg_image['path']);
    	}
    	if ($this->bg_image['im']) {
	        imagecopy($this->canvas['im'], $this->bg_image['im'], 0, 0, 0, 0, $this->canvas['width'], $this->canvas['height']);
    	}

    	return $this;
    }

    /**
     * 添加水印图片
     */
    private function addwaterImages()
    {
        if (empty($this->water_images)) {
            return $this;
        }
        foreach ($this->water_images as &$v) {
            $offset = $this->calculateWaterOffset($this->canvas, $v, $v['position']);
            if (empty($v['im']) && $v['path']) {
                $v['im'] = $this->getImagesSource($v['path']);
            }
            if ($v['im']) {
                $v['im'] = $this->resize($v['im'], $v['width'], $v['height']);
                $this->imagecopymerge_alpha($this->canvas['im'], $v['im'], $offset['x'], $offset['y'], 0, 0, $v['width'], $v['height'], $v['percent']);
            }
    	}
    	return $this;
    }

    /**
     * 添加水印文字
     */
    private function addwaterTexts()
    {
        if (empty($this->water_texts)) {
            return $this;
        }
        foreach ($this->water_texts as &$v) {
            if ($v['width'] > 0) {
                $v['text'] = $this->generateTxt($v['text'], $v['ttf'], $v['size'], $v['width']);
                $offset = $this->calculateWaterOffset($this->canvas, $v, $v['position']);
            } else {
    			$offset = $this->calculateTextOffset($this->canvas, $v, $v['position']);
            }
            $color = $this->formatColorRgb($v['color']);
            $col = imagecolorallocatealpha($this->canvas['im'], $color[0], $color[1], $color[2], $color[3]);

            imagettftext($this->canvas['im'], $v['size'], $v['angle'], $offset['x'], $offset['y'], $col, $v['ttf'], $v['text']);
    	}
    	return $this;
    }

    /**
     * 缩放图片
     * @param $im
     * @param $new_width
     * @param $new_height
     * @return resource [type]             [description]
     */
    public function resize($im, $new_width, $new_height)
    {
        $png = imagecreatetruecolor($new_width, $new_height);
        imagesavealpha($png, true);
        $trans_colour = imagecolorallocatealpha($png, 0, 0, 0, 127);
        imagefill($png, 0, 0, $trans_colour);
        imagecopyresized($png, $im, 0, 0, 0, 0, $new_width, $new_height, imagesx($im), imagesy($im));
        return $png;
    }

    /**
     * 把资源保存为图片
     * @return bool [type] [description]
     */
    public function saveImage()
    {
    	$path = $this->canvas['save_path'];
	    $save_dir = substr($path, 0, strrpos($path, '/'));
	    if (!is_dir($save_dir)) {
	    	mkdir($save_dir, 0777,  true);
	    }
		return imagejpeg($this->canvas['im'], $path);
    }

    public function getBase64()
    {
		ob_start();
		imagepng($this->canvas['im']);
		$fileContent = ob_get_contents();
		ob_end_clean();
		$image_base64 = "data:image/png;base64,". base64_encode ($fileContent);
		return $image_base64;
    }

    /**
     * 载入图像资源
     * @param  [type] $path 图像地址
     * @return resource [type]       [description]
     */
    public function getImagesSource($path)
    {
    	if ($path) {
	    	return  imagecreatefromstring(file_get_contents($path));
    	}
    }

    /**
     * 更新配置
     * @param $config_new
     * @param $config
     * @return mixed [type]             [description]
     */
	private function updateConfig($config_new, $config)
	{
		foreach ($config as $key => $value) {
		    if (!empty($config_new[$key])) {
                $config[$key] = $config_new[$key];
		    }

		}

		return $config;
	}

    /**
     * 解析颜色值为rgb格式
     * @param [type] $color  [16进制颜色值]
     * @return array
     */
    public function formatColorRgb($color)
    {
        if(is_string($color) && 0 === strpos($color, '#')){
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if(empty($color[3]) || $color[3] > 127){
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            E('错误的颜色值');
        }

        return $color;
    }

    /**
     * 计算文字位置
     * @param $bg        [背景信息]
     * @param $text      [背景信息]
     * @param int $pos   [位置 1-9]
     * @return array     [文字位置]
     */
    public function calculateTextOffset($bg, $text, $pos = 1)    
    {
		//计算昵称文字水印的宽高
		$text_box = imagettfbbox($text['size'], $text['angle'], $text['ttf'], $text['text']);
        $text['width'] = abs($text_box[2] - $text_box[0]);
        $text['height'] = abs($text_box[1] - $text_box[7]);
		return $this->calculateWaterOffset($bg, $text, $pos);
    }

    /**
     * 计算水印位置
     * @param $bg       [背景信息]
     * @param $water    [水印信息]
     * @param int $pos  [位置 1-9]
     * @return array    [水印位置]
     */
    public function calculateWaterOffset($bg, $water, $pos = 1)
    {
        /* 设置偏移量 */
        $offset = $water['offset'];
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } elseif (strpos($offset, ',')) {
        	$offset = explode(',', $offset);
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }

        /* 设定水印位置 */
        switch ($pos) {
            /* 左上角水印 */
            case 1:
                $x = $y = 0;
                $x += $ox;
                $y += $oy;
                break;

            /* 上居中水印 */
            case 2:
                $x = ($bg['width'] - $water['width'])/2;
                $y = 0;
                $x += $ox;
                $y += $oy;
                break;

            /* 右上角水印 */
            case 3:
                $x = $bg['width'] - $water['width'];
                $y = 0;
                $x -= $ox;
                $y += $oy;
                break;

            /* 左居中水印 */
            case 4:
                $x = 0;
                $y = ($bg['height'] - $water['height'])/2;
                $x += $ox;
                $y += $oy;
                break;

            /* 居中水印 */
            case 5:
                $x = ($bg['width'] - $water['width'])/2;
                $y = ($bg['height'] - $water['height'])/2;
                $x += $ox;
                $y += $oy;
                break;

            /* 右居中水印 */
            case 6:
                $x = $bg['width'] - $water['width'];
                $y = ($bg['height'] - $water['height'])/2;
                $x -= $ox;
                $y += $oy;
                break;

            /* 左下角水印 */
            case 7:
                $x = 0;
                $y = $bg['height'] - $water['height'];
                $x += $ox;
                $y -= $oy;
                break;

            /* 下居中水印 */
            case 8:
                $x = ($bg['width'] - $water['width'])/2;
                $y = $bg['height'] - $water['height'];
                $x += $ox;
                $y -= $oy;
                break;

            /* 右下角水印 */
            case 9:
                $x = $bg['width'] - $water['width'];
                $y = $bg['height'] - $water['height'];
                $x -= $ox;
                $y -= $oy;
                break;

            default:

        }
        return ['x' => $x, 'y' => $y];
    }

    /**
     * 支持透明背景的水印复制
     * @param $dst_im
     * @param $src_im
     * @param $dst_x
     * @param $dst_y
     * @param $src_x
     * @param $src_y
     * @param $src_w
     * @param $src_h
     * @param $opacity
     */
    public function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity){
        $cut = imagecreatetruecolor($src_w, $src_h);
        // ImageCopyReSampled($cut, $dst_im, 0, 0, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);

        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
    }

    /**
     * 文字换行
     * @param string $str
     * @param string $font
     * @param $size
     * @param int $width
     * @return string
     */
    protected function generateTxt($str, $font, $size, $width)
    {
        if ($width == self::WIDTH_NO_CHANGE_LINE) {
            return $str;
        }

        $temp_str = '';
        $ret = '';
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $box = imagettfbbox($size, 0, $font, $temp_str);
            $str_len = $box[2] - $box[0];
            $temptext = mb_substr($str, $i, 1);

            $temp = imagettfbbox($size, 0, $font, $temptext);

            if ($str_len + $temp[2] - $temp[0] < $width) {
                $temp_str .= mb_substr($str, $i, 1);

                // 是不是最后半行 不满一行的情况
                if ($i == mb_strlen($str) - 1) {
                    $ret .= $temp_str;
                }
            } else {
                $texts = mb_substr($str, $i, 1);

                $symbol = preg_match('/[\\\\pP]/u', $texts) ? true : false;
                // 如果是标点符号，则添加在第一行的结尾
                if ($symbol) {
                    $temp_str .= $texts;
                } else {
                    $i--;
                }

                $ret .= $temp_str . PHP_EOL;

                $temp_str = '';
            }
        }

        return $ret;
    }
}