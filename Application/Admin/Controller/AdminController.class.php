<?php
namespace Admin\Controller;

class AdminController extends BaseController
{
    /**
     * 2018/06/30
     * 管理员登录
     */
    public function login()
    {
        //已登陆跳转
        if (session('admin_info')) {
            redirect(U('index/index'));
            exit;
        }
        //表单提交数据检测
        if (IS_POST) {
            $username = I('post.username');
            $password = I('post.password');
            if (!$username || !$password) {
                redirect(U('admin/login'));
            }

            $code = I('post.code');
            //验证码
            $verify = new \Think\Verify();
            if (!$verify->check($code)) {
                $this->error("验证码错误");
                exit;
            }

            //生成认证条件
            $admin_info = M('admin')->where(['name' => $username])->find();
            //使用用户名、密码和状态的方式进行认证
            if ($admin_info) {
                if ($admin_info['status'] == 1) {
                    if ($admin_info['password'] != md5(C('MD5_KEY') . $password)) {
                        $this->error('密码错误！');
                    }
                    $data['last_login_time'] = time();
                    $data['last_login_ip'] = get_client_ip();
                    //记录最后登录时间
                    M('admin')->where(['id' => $admin_info['id']])->save($data);

                    unset($admin_info['password']);
                    //保存session
                    session('admin_info', $admin_info);
                    $this->success('登录成功！', U('index/index'));
                } else {
                    $this->error('此帐号已禁用！');
                }

            } else {
                $this->error('此帐号不存在！');
            }
        } else {
            $this->display();
        }
    }

    /**
     * 验证码
     */
    public function verify()
    {
        $verify = new \Think\Verify(
            [
                'useCurve' => false,
                'useNoise' => false,
                'fontSize' => 25,
                'imageH' => 50,
                'imageW' => 180,
                'length' => 4
            ]
        );
        $verify->entry();
    }
}