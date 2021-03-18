<?php

namespace App\Controllers;

use CodeIgniter\I18n\Time;

class Appointment extends BaseController
{
    public function index()
    {
        $patient = new \App\Models\Patient();
        $patients = $patient->get()->getResultArray();
        $config = array(
            "title" => lang('Admin.title.patient_all'),
            'patients' => $patients
        );
        return view('admin/patient/patient', $config);
    }

    public function add()
    {
