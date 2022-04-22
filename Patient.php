<?php

namespace App\Controllers;

use App\Libraries\Nusoap_library;
use CodeIgniter\I18n\Time;

class Patient extends BaseController
{
    public function index()aaa
    {
        $patient = new \App\Models\Patient();
        $appoint = new \App\Models\Appointment();
        $patients = $patient->get()->getResultArray();
        $now = date('Y-m-d H:i');

        foreach ($patients as $key => $value) {
            $lastappoint_where = array('patient_id' => $value['id']);
            $lastappoint = $appoint->where($lastappoint_where)->orderBy('date_time', 'DESC')->limit(1)->get()->getResultArray();
            if (is_array($lastappoint[0])) {
                $patients[$key]['date_time'] = $lastappoint[0]['date_time'];
                $lastappoint = $lastappoint[0];
                $datetime = strtotime($lastappoint['date_time']);
                $datetime_now = strtotime($now);
                $diff = abs($datetime_now - $datetime);
                $result = getdate($diff);
                $time = Time::parse($lastappoint['date_time'], 'Europe/Istanbul');
                if ($result['yday'] == 0 && strstr($time->humanize(), 'in')) {
                    //echo (($datetime_now - $datetime) / (60*60*24));
                    $patients[$key]['appoint_status_color'] = 'info';
                    $patients[$key]['appoint_status'] = lang('Admin.patient.all.appoint_now') . " - " . $result['hours'] . " " . lang('Admin.patient.all.appoint_hour');

                } else {
                    $patients[$key]['appoint_status'] = lang('Admin.patient.all.appoint_none');

                    $patients[$key]['appoint_status_color'] = 'danger';
                }
                if ($now > $lastappoint['date_time']) {
                    $patients[$key]['appoint_status'] = lang('Admin.patient.all.appoint_none');
                    $patients[$key]['appoint_status_color'] = 'danger';
                } elseif ($now < $lastappoint['date_time'] && $result['yday'] != 0) {
                    $patients[$key]['appoint_status'] = $result['yday'] . " " . lang('Admin.patient.all.appoint_time');
                    $patients[$key]['appoint_status_color'] = 'warning';
                }


                //$patients[$key]['appoint_status'] = ($lastappoint[0]['date_time'] == date('Y-m-d H:i')) ? 'today' : 'none';
            } else {
                $patients[$key]['date_time'] = 'None';
                $patients[$key]['appoint_status'] = lang('Admin.patient.all.appoint_none');
                $patients[$key]['appoint_status_color'] = 'danger';

            }

        }
        $config = array(
            "title" => lang('Admin.title.patient_all'),
            'patients' => $patients
        );
        return view('admin/patient/patient', $config);
    }

    public function add()
    {
        $config = array(
            "title" => lang('Admin.title.patient_add'),
            "total_patient" => '50',
            "balance" => '100',

        );
        return view('admin/patient/add', $config);
    }

    public function ajaxPatientAdd()
    {
        if ($this->request->isAJAX()) {
            if ($this->request->getPost('name') && $this->request->getPost('surname') && $this->request->getPost('ident') && $this->request->getPost('date') && $this->request->getPost('gender') && $this->request->getPost('telephone')) {
                $date = str_replace('/', '-', $this->request->getPost('date'));
                $explode = explode('-', $date);
                $date = $explode[2] . "-" . $explode[1] . "-" . $explode[0];

                $set = array(
                    'name' => $this->request->getPost('name'),
                    'surname' => $this->request->getPost('surname'),
                    'identification_no' => $this->request->getPost('ident'),
                    'date_birth' => $date,
                    'gender' => $this->request->getPost('gender'),
                    'telephone' => $this->request->getPost('telephone')
                );
                if ($this->request->getPost('telephone_ekstra')) {
                    $set['telephone2'] = $this->request->getPost('telephone_ekstra');
                }
                if ($this->request->getPost('note')) {
                    $set['note'] = $this->request->getPost('note');
                }

                $patient = new \App\Models\Patient();
                $patient->insert($set);
                $error = $patient->errors();
                if (!$error) {
                    js(
                        array(
                            'status' => 'completed',
                            'message' => lang('Admin.patient.add.verify.completed'),
                            'sub_message' => ''
                        )
                    );
                } else {
                    js(
                        array(
                            'status' => 'failed',
                            'message' => lang('Admin.patient.add.verify.error'),
                            'sub_message' => ''
                        )
                    );
                }
            } else {
                js(
                    array(
                        'status' => 'failed',
                        'message' => lang('Admin.patient.add.verify.failed'),
                        'sub_message' => ''
                    )
                );
            }
        }
    }

    public function ajaxPatient()
    {

        $patient = new \App\Models\Patient();
        $data = array('identification_no' => $this->request->getPost('ident'));
        $row = $patient->where($data)->countAllResults();
        $result = $patient->where($data)->get()->getResultArray();
        if (is_array($result[0])) {
            js(
                array(
                    'status' => 'info',
                    'message' => lang('Admin.patient.add.identification_info'),
                    'sub_message' => lang('Admin.patient.add.identification_sub_info')
                )
            );
        }

    }

    public function ajaxIdentVerify()
    {
        if ($this->request->getPost('name') && $this->request->getPost('surname') && $this->request->getPost('date') && $this->request->getPost('ident')) {

            $name = tr_strtoupper($this->request->getPost('name'));
            $surname = tr_strtoupper($this->request->getPost('surname'));
            $date = explode('/', $this->request->getPost('date'));
            $ident = $this->request->getPost('ident');

            if (tr_ident($name, $surname, $date[2], $ident)) {
                js(
                    array(
                        'status' => 'completed',
                        'message' => lang('Admin.patient.add.identification_verify_info_completed'),
                        'sub_message' => ''
                    )
                );
            } else {
                js(
                    array(
                        'status' => 'failed',
                        'message' => lang('Admin.patient.add.identification_verify_info_failed'),
                        'sub_message' => ''
                    )
                );
            }
        }
    }

    public function edit()
    {
        if ($this->request->getGet('patient')) {
            $patient = new \App\Models\Patient();
            $patient_where = array('id' => $this->request->getGet('patient'));
            $control = $patient->select('*')->where($patient_where)
                ->get()
                ->getResultArray();
            if (is_array($control[0])) {
                $control = $control[0];
                $birth = explode('-',$control['date_birth']);
                $control['date_birth'] = $birth[2]."/".$birth[1]."/".$birth[0];

                $config = array(
                    'title' => $control['name'] . " " . $control['surname'] . " | " . lang('Admin.title.patient_edit'),
                    'patient' => $control
                );
                $db = \Config\Database::connect();
                $appoint_where = array('patient_id' => $control['id']);
                $appoint = $db->table('appointment')->where($appoint_where)->get()->getResultArray();
                if (is_array($appoint[0])) {
                    foreach ($appoint as $key => $value) {
                        $price_where = array('price.appointment_id' => $value['id'],'image.deleted_at'=>Null);
                        $price = $db->table('price')
                            ->select('price.price,image.*')
                            ->where($price_where)
                            ->join('image', 'image.price_id = price.id')
                            ->get()->getResultArray();
                        $appoint[$key]['price'] = $price;
                    }
                    $config['appoint'] = $appoint;
                }

                return view('Admin/patient/edit', $config);
            }
        }
    }
}
