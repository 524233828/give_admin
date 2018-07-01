<?php
namespace Admin\Model;
use Think\Model;
class ImageModel extends Model{

    public function imgUrl($id)
    {
        return $info = $this->where(['id' => $id])->getField('img_url');
    }
}