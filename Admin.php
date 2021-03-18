<?php

namespace App\Controllers;

class Admin extends BaseController
{
    public function index()
    {
        $config = array(
            "title" => lang('Admin.title.index'),
            "total_patient" => '50',
            "balance" => '100',

        );
        return view('admin/index', $config);
    }
}
