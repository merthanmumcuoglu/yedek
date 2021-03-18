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
        $patient = new \App\Models\Patient();
        $patients = $patient->get()->getResultArray();
        $config = array(
            "title" => lang('Admin.title.appointment_add'),
            "patients" => $patients,
        );
        return view('admin/appointment/add', $config);
    }

    public function PatientLiveSearch()
    {
        $patient = new \App\Models\Patient();
        if ($this->request->getPost('search')) {
            $search = $this->request->getPost('search');
            $option = $patient->like('name', $search)->orLike('surname', $search)->orLike('telephone', $search)->orLike('identification_no', $search)->get()->getResultArray();
            if (is_array($option[0])) {
                js(
                    array(
                        'total_field' => count($option),
                        'field' => $option,
                    )
                );
            }
        }
    }

    public function ajaxAppointmentAdd()
    {
        if ($this->request->getPost('date') && $this->request->getPost('patient_id')) {
            $date = $this->request->getPost('date');

            $date = date('Y-m-d H:i', strtotime($date));
            $appoint = new \App\Models\Appointment();
            $data = array(
                'date_time' => $date,
                'patient_id' => $this->request->getPost('patient_id'),
            );
            if ($this->request->getPost('note'))
                $data['note'] = $this->request->getPost('note');
            $add = $appoint->insert($data);
            $error = $appoint->errors();
            if (!$error) {
                js(
                    array(
                        'status' => 'completed',
                        'message' => lang('Admin.appointment.add.verify.completed'),
                        'sub_message' => ''
                    )
                );
            } else {
                js(
                    array(
                        'status' => 'failed',
                        'message' => lang('Admin.appointment.add.verify.failed'),
                        'sub_message' => ''
                    )
                );
            }


        }
    }

    public function calendar()
    {
        $appoint = new \App\Models\Appointment();
        $appoint = $appoint->get()->getResultArray();
        $config = array(
            "title" => lang('Admin.title.appointment_calendar'),
            'appoint' => $appoint
        );
        return view('admin/appointment/appointment', $config);
    }

    public function CalendarSearch()
    {
        $appoint = new \App\Models\Appointment();
        $where = array('status' => '0');
        if ($this->request->getPost('date')) {
            $date_new = explode('/', $this->request->getPost('date'));
            $date = Time::createFromDate($date_new[2], $date_new[1], $date_new[0]);
            $todayold = strtotime($date);
            $today = date('Y-m-d H:i', $todayold);
            $every = date('Y-m-d H:i', strtotime("+1 day", $todayold));

            $calcu = strtotime(Time::createFromDate()) - $todayold;
            if ($calcu == 0) {
                $olddate = 0;
            } elseif ($calcu > 0) {
                $olddate = 1;
            } elseif ($calcu < 0) {
                $olddate = 2;
            }
        } else {
            $olddate = 0;
            $todayold = strtotime(Time::createFromDate());
            $today = date('Y-m-d', $todayold);
            $every = date('Y-m-d', strtotime("+1 day", $todayold));
        }

        // ->where("date_time BETWEEN '$today 00:00'  AND '$every 00:00'")

        $data = $appoint->select('appointment.id, appointment.patient_id, appointment.note, appointment.date_time, appointment.status, patient.name, patient.surname, patient.telephone, patient.identification_no')->where("date_time BETWEEN '$today'  AND '$every'")->orderBy('date_time', 'ASC')->join('patient', 'patient.id = appointment.patient_id')->get()->getResultArray();
        for ($i = 0; $i < count($data); $i++) {
            $data[$i]['date_time'] = date('H:i', strtotime($data[$i]['date_time']));
        }
        js(
            array(
                'field' => count($data),
                'field_data' => $data,
                'field_old' => $olddate
            )
        );
    }

    public function ModalResult()
    {
        if ($this->request->getPost('appointID')) {
            $appoint = new \App\Models\Appointment();
            $appoint_id = $this->request->getPost(('appointID'));
            $where = array('appointment.id' => $appoint_id);
            $result = $appoint->select('appointment.id,appointment.note ,patient.identification_no, patient.name, patient.surname, patient.telephone, patient.telephone2, price.price')->where($where)->join('price', 'price.appointment_id = appointment.id', 'left')->join('patient', 'patient.id = appointment.patient_id')->get()->getResultArray();

            js(
                array(
                    'field' => count($result),
                    'field_data' => $result
                )
            );
        }

    }

    public function AppointmentEdit()
    {
        if ($this->request->getPost('price') && $this->request->getPost('id')) {
            $id = $this->request->getPost('id');
            $price_m = $this->request->getPost('price');
            $appoint = new \App\Models\Appointment();
            $price = new \App\Models\Price();
            $control_where = array('appointment_id' => $id);
            $control = $price->where($control_where)->get()->getResultArray();

            if (is_array($control[0])) {

                $update_data = array(
                    'price' => $price_m
                );
                $update = $price->update($control[0]['id'], $update_data);
                if (!$this->request->getFileMultiple('detail')) {
                    js(
                        array(
                            'status' => 'completed',
                            'message' => lang('Admin.appointment.calendar.verify.completed_update'),
                            'sub_message' => ''
                        )
                    );
                } else {
                    $i = 0;
                    $e = 0;
                    if (is_array($this->request->getFileMultiple('detail'))) {
                        foreach ($this->request->getFileMultiple('detail') as $file) {
                            if ($file->isValid()) {
                                $image = new \App\Models\Image();
                                $file->move('uploads');
                                $control_where = array('appointment_id' => $id);
                                $control = $price->where($control_where)->get()->getResultArray();
                                if ($e == 0) {
                                    $sil = $image->where('price_id', $control[0]['id'])->delete();
                                    $e++;
                                }
                                $data = [
                                    'url' => $file->getName(),
                                    'patient_id' => $control[0]['patient_id'],
                                    'appointment_id' => $control[0]['appointment_id'],
                                    'price_id' => $control[0]['id'],
                                ];
                                $save = $image->insert($data);

                            } else {
                                $i = 2;
                            }
                        }
                        if ($i == 2) {
                            js(
                                array(
                                    'status' => 'completed',
                                    'message' => lang('Admin.appointment.calendar.verify.completed_update'),
                                    'sub_message' => ''
                                )
                            );
                        } else {
                            js(
                                array(
                                    'status' => 'completed',
                                    'message' => lang('Admin.appointment.calendar.verify.completed_update_image'),
                                    'sub_message' => ''
                                )
                            );
                        }
                    }
                }

            } else {
                $control_where = array('id' => $id);
                $control = $appoint->where($control_where)->get()->getResultArray();
                $insert_data = array(
                    'price' => $price_m,
                    'patient_id' => $control[0]['patient_id'],
                    'appointment_id' => $id,
                );
                $insert = $price->insert($insert_data);
                $error = $price->errors();
                $input = $this->validate([
                    'file' => [
                        'uploaded[file]',
                        'mime_in[file,image/jpg,image/jpeg,image/png]',
                        'max_size[file,1024]',
                    ]
                ]);

                if (!$this->request->getFileMultiple('detail')) {
                    js(
                        array(
                            'status' => 'completed',
                            'message' => lang('Admin.appointment.calendar.verify.completed'),
                            'sub_message' => ''
                        )
                    );
                } else {
                    $true = 0;
                    foreach ($this->request->getFileMultiple('detail') as $file) {

                        $file->move('uploads');
                        $control_where = array('appointment_id' => $id);
                        $control = $price->where($control_where)->get()->getResultArray();
                        $data = [
                            'url' => $file->getName(),
                            'patient_id' => $control[0]['patient_id'],
                            'appointment_id' => $control[0]['appointment_id'],
                            'price_id' => $control[0]['id'],
                        ];
                        $image = new \App\Models\Image();
                        $save = $image->insert($data);
                        $true = 1;
                    }
                    if ($true != 0) {
                        js(
                            array(
                                'status' => 'completed',
                                'message' => lang('Admin.appointment.calendar.verify.completed_image'),
                                'sub_message' => ''
                            )
                        );
                    }
                }


            }
        }

    }
}

