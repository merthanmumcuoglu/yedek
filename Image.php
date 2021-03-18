<?php

namespace App\Models;

use CodeIgniter\Model;

class Image extends Model
{
    protected $table = 'image';

    protected $primaryKey = 'id';

    protected $allowedFields = ['patient_id','appointment_id','price_id','url'];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField  = 'deleted_at';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $skipValidation = false;

}