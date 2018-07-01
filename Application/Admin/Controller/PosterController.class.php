<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/15
 * Time: 11:11
 */

namespace Admin\Controller;

use Common\Common\Util;
use Common\Helpers\WatermarkHelper;
use Endroid\QrCode\QrCode;

class PosterController extends BaseController
{
    protected $poster_id;
    private $poster;
    private $userinfo;
    private $ttf_path = PUBLIC_PATH . 'static/font/SIMHEI.TTF';
    private $qrcode_url;    //二维码地址
    private $user_head_im;  //用户头像图片资源
    private $wechat;        //微信

    const QRCODE_CACHE_DIR = UPLOAD_PATH . 'qrcode/cache/'; //图片缓存地址


    public function __construct()
    {
        parent::__construct();
//        $this->wechat = $this->getWechatByAppId();
//        $this->userinfo = M('user')->where(['id' => $uid])->field('openid, nickname, headimgurl')->find();

    }

    public function preview()
    {
        $this->display();
    }
    /**
     * 创建图片
     */
    public function createPoster()
    {
        $id = I('id');
        $this->poster = M('recommend')->where(['id' => $id])->find();
        $watermarks = M('watermark')
            ->where([
                'id' => ['in', $this->poster['watermarks']]
            ])
            ->order('sort desc, id asc')
            ->select();

        $watermark = new WatermarkHelper();
        $watermark->create([
                'width' => $this->poster['width'],
                'height' => $this->poster['height']
            ]);
        foreach ($watermarks as $key => $value) {

            switch ($value['identify']) {
//                case 'head':
//                    //用户头像
//                    $value['im'] = $this->getUserHeadIm();
//                    break;

                case 'qrcode':
                    //公众号二维码
                    $value['path'] = $this->getQrcode();
                    break;

                case 'price':
                    //价格
                    if ($this->poster['price'] > 1) {
                        $value['text'] = floor($this->poster['price']) . '元';
                    } else {
                        $value['text'] = $this->poster['price'] . '元';
                    }

                    break;

//                case 'nickname':
//                    //用户昵称
//                    $this->userinfo['nickname'] = Util::filterEmoji(urldecode(trim($this->userinfo['nickname'])));//移除emoji表情
//                    $value['new_text'] = $this->userinfo['nickname'];
//                    break;

                default:
                    if (isset($this->poster[$value['identify']])) {
                        $value['text'] = $this->poster[$value['identify']];
                    }

                    break;
            }

            switch ($value['type']) {
                case WatermarkHelper::TYPE_IMAGE:   //图片
                    if (empty($value['path']) && $value['img']) {
                        $value['path'] = D('image')->imgUrl($value['img']);
                    }
                    $watermark->waterImage($value);
                    break;

                case WatermarkHelper::TYPE_TEXT:    //文字
                    $value['ttf'] = $this->ttf_path;
                    $watermark->waterText($value);
                    break;

                default:
                    break;
            }
        }

//        $save_path = self::QRCODE_CACHE_DIR . time() . mt_rand(1000, 9999) . '.jpg';
//        $watermark->save($save_path);
//		$res = $this->wechat->uploadMedia(['media'=>curl_file_create(realpath($save_path))], 'image');
//        @unlink($save_path);
//        if (!empty($res['media_id'])) {
//            return $res['media_id'];
//        }

        echo '<img style="margin: 20px; max-width: 500px; max-height: 500px" src="'.$watermark->base64().'">';
        die();
    }

    /**
     * 获取公众号二维码
     * @return mixed
     */
    private function getQrcode()
    {
        if (!$this->qrcode_url) {
            $params = [
                'id' => $this->poster['id'],
            ];
            //下载二维码
            $qrCode = new QrCode(CC('web_host') . CC('recommend_url') . '?' . http_build_query($params));
            $this->qrcode_url = $qrCode->writeDataUri();
        }
        return $this->qrcode_url;
    }

    /**
     * 获取用户头像
     * @return null|resource
     */
    private function getUserHeadIm()
    {
        if (!$this->user_head_im) {
            $this->user_head_im = $this->getWxAvatarIm($this->userinfo['headimgurl']);
        }
        return $this->user_head_im;
    }
}