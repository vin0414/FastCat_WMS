<?php

namespace App\Controllers;
use App\Libraries\Hash;
header('Access-Control-Allow-Origin: *');
class Dashboard extends BaseController
{
    private $db;
    public function __construct()
    {
        $this->db = db_connect();
    }

    public function Notification($username)
    {
        $accountModel = new \App\Models\accountModel();
        $user_info = $accountModel->where('username', $username)->WHERE('Status',1)->first();
        //PRF
        $prf=0;$canvass=0;$po = 0;
        $builder = $this->db->table('tblreview');
        $builder->select('COUNT(reviewID)total');
        $builder->WHERE('Status',0)->WHERE('accountID',$user_info['accountID']);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            $prf = $row->total;
        }
        //Purchase Order
        $builder = $this->db->table('tblpurchase_review');
        $builder->select('COUNT(prID)total');
        $builder->WHERE('Status',0)->WHERE('accountID',$user_info['accountID']);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            $po = $row->total;
        }
        //canvass
        $builder = $this->db->table('tblcanvass_review');
        $builder->select('COUNT(crID)total');
        $builder->WHERE('Status',0)->WHERE('accountID',$user_info['accountID']);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            $canvass= $row->total;
        }

        echo $prf + $canvass + $po;
    }
    
    public function autoLogin($username)
    {
        $accountModel = new \App\Models\accountModel();
        $systemLogsModel = new \App\Models\systemLogsModel();
        $warehouseModel = new \App\Models\warehouseModel();
        $password = "Fastcat_01";
        
        $user_info = $accountModel->where('username', $username)->WHERE('Status',1)->first();
        if(empty($user_info['accountID']))
        {
            session()->setFlashdata('fail','Invalid! No existing account');
            return redirect()->to('/auth')->withInput();
        }
        else
        {
            $check_password = Hash::check($password, $user_info['password']);
            if(!$check_password || empty($check_password))
            {
                session()->setFlashdata('fail','Invalid Username or Password!');
                return redirect()->to('/auth')->withInput();
            }
            else
            {
                $warehouse = $warehouseModel->WHERE('warehouseID',$user_info['warehouseID'])->first();
                session()->set('loggedUser', $user_info['accountID']);
                session()->set('fullname', $user_info['Fullname']);
                session()->set('role',$user_info['systemRole']);
                session()->set('assignment',$user_info['warehouseID']);
                session()->set('department',$user_info['Department']);
                session()->set('location',$warehouse['warehouseName']);
                    
                //save the logs
                $values = ['accountID'=>$user_info['accountID'],'Date'=>date('Y-m-d H:i:s a'),'Activity'=>'Logged-In'];
                $systemLogsModel->save($values);
                return redirect()->to('/dashboard');
            }
        }
    }

    public function listSupplier()
    {
        $builder = $this->db->table('tblsupplier a');
        $builder->select('a.supplierName,FORMAT(count(inventID),0)total');
        $builder->join('tblinventory b','b.supplierID=a.supplierID','LEFT');
        $builder->groupBy('a.supplierID')->orderby('total','DESC')->limit(10);
        $data = $builder->get();
        foreach($data->getResult() as $row)
        {
            ?>
            <li class="d-flex align-items-center justify-content-between">
                <div class="name-avatar d-flex align-items-center pr-2">
                    <div class="txt">
                        <div class="font-14 weight-600"><?php echo $row->supplierName ?></div>
                    </div>
                </div>
                <div class="cta flex-shrink-0">
                    <a href="#" class="btn btn-sm btn-outline-primary"><?php echo $row->total ?></a>
                </div>
            </li>
            <?php
        }
    }

    public function outofStock()
    {
        $builder = $this->db->table('tblinventory a');
        $builder->select('a.*,b.categoryName');
        $builder->join('tblcategory b','b.categoryID=a.categoryID','LEFT');
        $builder->WHERE('a.Qty',0);
        $data = $builder->get();
        foreach($data->getResult() as $row)
        {
            ?>
            <li class="d-flex align-items-center justify-content-between">
                <div class="name-avatar d-flex align-items-center pr-2">
                    <div class="txt">
                        <div class="font-14 weight-600"><?php echo $row->productName ?></div>
                        <div class="font-12 weight-500" data-color="#b2b1b6"><?php echo $row->Description ?></div>
                    </div>
                </div>
                <div class="cta flex-shrink-0">
                    <a href="#" class="btn btn-sm btn-outline-primary"><?php echo $row->categoryName ?></a>
                </div>
            </li>
            <?php
        }
    }

    public function returnOrder()
    {
        $builder = $this->db->table('tblreturn');
        $builder->select('FORMAT(COUNT(returnID),0)total');
        $builder->WHERE('Status',0);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            echo $row->total;
        }
    }

    public function damageItem()
    {
        $builder = $this->db->table('tbldamagereport a');
        $builder->select('FORMAT(COUNT(a.reportID),0)total');
        $builder->join('tblinventory b','b.inventID=a.inventID','LEFT');
        $builder->WHERE('a.Status',0);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            echo $row->total;
        }
    }

    public function overhaulItem()
    {
        $builder = $this->db->table('tblrepairreport a');
        $builder->select('FORMAT(COUNT(a.rrID),0)total');
        $builder->join('tbldamagereport b','b.reportID=a.reportID','LEFT');
        $builder->join('tblinventory c','c.inventID=b.inventID','LEFT');
        $builder->WHERE('a.Status',0);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            echo $row->total;
        }
    }

    public function transferItem()
    {
        $builder = $this->db->table('tbltransfer_request');
        $builder->select('FORMAT(COUNT(requestID),0)total');
        $builder->WHERE('Status',0);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            echo $row->total;
        }
    }
}