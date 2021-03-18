<?php

namespace App\Controllers;

class Login extends BaseController
{


    public function index()
    {
        $config = array(
            "title" => lang('Login.title')
        );
        return view('login/login.php', $config);
    }

    public function ajaxLogin()
    {
        $userModel = new \App\Models\Login();


        $data = array(
            'email' => $this->request->getPost('email'),
            'password' => md5($this->request->getPost('password'))
        );
        $userModel->where($data);
        $row = $userModel->countAllResults();
        $user = $userModel->get()->getResultArray()[0];

        if (!$row) {
            js(
                array(
                    'status' => 'failed',
                    'message' => lang('Login.return.failed'),
                    'sub_message' => lang('Login.return.failed_sub')
                )
            );
        } else {
            set_cookie([
                'name' => 'user_id_r',
                'value' => md5($user['id']).md5($user['email'] . $user['password']),
                'expire' => time() + 1000,
                'httponly' => false
            ]);
            set_cookie([
                'name' => 'user_id',
                'value' => $user['id'],
                'expire' => time() + 1000,
                'httponly' => false
            ]);
            js(
                array(
                    'status' => 'completed',
                    'message' => lang('Login.return.completed'),
                    'sub_message' => lang('Login.return.completed_sub')
                )
            );
        }
    }
}
