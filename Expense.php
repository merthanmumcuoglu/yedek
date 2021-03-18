<?php

namespace App\Controllers;

use CodeIgniter\I18n\Time;

class Expense extends BaseController
{
    public function index()
    {
    }

    public function all()
    {

        $expense = new \App\Models\Expense();
        $year = date('Y');
        $month = date('m');
        $all_where = array(
            'year' => $year,
            'month' => $month
        );
        $all = $expense->where($all_where)->get()->getResultArray();
        $config = array(
            'title' => lang('Admin.title.expense_all'),
            'expense' => $all
        );
        return view('admin/expense/expense', $config);

    }
    public function modal(){
        if($this->request->getPost('ID')){

            $expense = new \App\Models\Expense();
            $expense_where = array('id'=>$this->request->getPost('ID'));
            $result = $expense->where($expense_where)->get()->getResultArray();
            if(is_array($result[0])){
                js(array(
                    'field_data' => $result
                ));
            }
        }

    }
}