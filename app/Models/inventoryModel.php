<?php

namespace App\Models;

use CodeIgniter\Model;

class inventoryModel extends Model
{
    protected $table      = 'tblinventory';
    protected $primaryKey = 'inventID';
    protected $useAutoIncrement  = true;
    protected $insertID = 0;
    protected $returnType = 'array';
    protected $userSoftDelete = false;
    protected $protectFields = true;
    protected $allowedFields = ['Date','Location','productID','productName','Code','Description','ItemUnit','unitPrice','Qty','ReOrder','categoryID','ExpirationDate','supplierID','warehouseID'];

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
}