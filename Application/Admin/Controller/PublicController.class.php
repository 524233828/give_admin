<?php
namespace Admin\Controller;

class PublicController extends BaseController
{

    /**
     * 初始化
     */
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 上传文件
     */
    /*public function upload() {
        $savePath = I('post.savePath');
        if (!empty($_FILES)) {
            $upload = $this->_upload($_FILES,$savePath);
            $this->ajaxReturn($upload);
        }
    }*/

    public function upload()
    {
        if (!isset($_FILES['Filedata']) || !$_FILES['Filedata'] || !$_FILES['Filedata']['size']) {
            $this->ajaxReturn(['status' => -1, 'msg' => '上传失败']);
        }

        $file = $_FILES['Filedata'];
        $time = microtime();

        if ($file['size'] > 1024 * 1024) {
            $this->ajaxReturn(['status' => -2, 'msg' => '上传文件太大']);
        }

        $urlparam = substr(md5(rand(1, 1000) . $time), 0, 16) . "#" . '';
        $channel = 'yiqiwen';
        $url = "http://resource.img.ggwan.com/yd/newwap/upload.php?softid=getUserPic&channel=" . $channel . "&data=" . base64_encode($urlparam);

        $fileurl = $file['tmp_name'];

        $param = array();


        $result = $this->postdata($url, $param, $fileurl);

        if (!$this->is_serialized($result)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '上传失败']);
        } else {
            $result = unserialize($result);
        }

        $this->ajaxReturn(['status' => 1, 'msg' => $result['url']]);
    }

    // 文件上传
    private function _upload($file, $savePath)
    {
        /*$exts = array();
        if(trim($this->setting['attach_pic_type']) != '') {
            array_push($exts, $this->setting['attach_pic_type']);
        }
        if(trim($this->setting['attach_video_type']) != '') {
            array_push($exts, $this->setting['attach_video_type']);
        }
        if(trim($this->setting['attach_file_type']) != '') {
            array_push($exts, $this->setting['attach_file_type']);
        }
        if(count($exts) > 0) {
            $exts = implode(',', $exts);
        }else {
            $exts = '*';
        }*/
        $exts = 'jpg,gif,png,jpeg';

        $config = array('maxSize' => 5120 * 5120,
            'exts' => explode(',', $exts),
            'savePath' => $savePath . '/',
            'rootPath' => UPLOAD_PATH,
            'subName' => array('date', 'Y/m/d'),
            'saveName' => array('uniqid', ''));
        //导入上传类
        $upload = new \Think\Upload($config);
        if (!$info = $upload->upload($file)) {
            //捕获上传异常
            return array('status' => 0, 'msg' => $upload->getError());
        }
        return array('status' => 1, 'msg' => UPLOAD_PATH . $info['Filedata']['savepath'] . $info['Filedata']['savename']);
    }

    private function postdata($posturl, $data = array(), $file = '')
    {
        $url = parse_url($posturl);
        if (!$url)
            return "couldn't parse url";
        if (!isset($url['port']))
            $url['port'] = "";
        if (!isset($url['query']))
            $url['query'] = "";

        $boundary = "---------------------------" . substr(md5(rand(0, 32000)), 0, 10);
        $boundary_2 = "--$boundary";

        $content = $encoded = "";
        if ($data) {
            while (list($k, $v) = each($data)) {
                $encoded .= $boundary_2 . "\r\nContent-Disposition: form-data; name=\"" . rawurlencode($k) . "\"\r\n\r\n";
                $encoded .= rawurlencode($v) . "\r\n";
            }
        }


        if ($file) {
            $ext = strrchr($file, ".");
            $type = "image/jpeg";
            switch ($ext) {
                case '.gif':
                    $type = "image/gif";
                    break;
                case '.jpg':
                    $type = "image/jpeg";
                    break;
                case '.png':
                    $type = "image/png";
                    break;
            }
            $encoded .= $boundary_2 . "\r\nContent-Disposition: form-data; name=\"file\"; filename=\"$file\"\r\nContent-Type: $type\r\n\r\n";
            //$content = join("", file($file));
            $content = implode("", file($file));
            $encoded .= $content . "\r\n";
        }

        $encoded .= "\r\n" . $boundary_2 . "--\r\n\r\n";
        $length = strlen($encoded);


        $fp = fsockopen($url['host'], $url['port'] ? $url['port'] : 80);
        if (!$fp)
            return "Failed to open socket to $url[host]";

        fputs($fp, sprintf("POST %s%s%s HTTP/1.0\r\n", $url['path'], $url['query'] ? "?" : "", $url['query']));
        fputs($fp, "Host: $url[host]\r\n");
        fputs($fp, "Content-type: multipart/form-data; boundary=$boundary\r\n");
        fputs($fp, "Content-length: " . $length . "\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $encoded);

        $line = fgets($fp, 1024);
        //if (!preg_match("/^HTTP/1\.. 200/i", $line)) return null;

        $results = "";
        $inheader = 1;
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            if ($inheader && ($line == "\r\n" || $line == "\r\r\n")) {
                $inheader = 0;
            } elseif (!$inheader) {
                $results .= $line;
            }
        }
        fclose($fp);
        return $results;
    }

    //应用于其他方法 检测是否是序列化
    private function is_serialized($data) {
        // if it isn't a string, it isn't serialized
        if (!is_string($data))
            return false;
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}