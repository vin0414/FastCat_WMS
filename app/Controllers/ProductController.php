<?php

namespace App\Controllers;
use CodeIgniter\HTTP\Response;

class ProductController extends BaseController
{
    private $db;
    public function __construct()
    {
        $this->db = db_connect();
    }

    public function productInfo()
    {
        $val = $this->request->getGet('value');
        $productInfo = new \App\Models\inventoryModel();
        $product = $productInfo->WHERE('inventID',$val)->first();
        echo $product['productName'];
    }

    public function saveReport()
    {
        $damageReport = new \App\Models\damageModel();
        $inventoryModel = new \App\Models\inventoryModel();
        //validate
        $validation = $this->validate([
            'productName'=>'required',
            'dateReport'=>'required',
            'defectType'=>'required',
            'qty'=>'required',
            'details'=>'required',
            'recommendation'=>'required',
            'file'=>'uploaded[file]'
        ]);
        //datas
        $dateCreated = date('Y-m-d');
        $itemID = $this->request->getPost('itemID');
        $productName = $this->request->getPost('productName');
        $defectType = $this->request->getPost('defectType');
        $dateReport = $this->request->getPost('dateReport');
        $qty = $this->request->getPost('qty');
        $details = $this->request->getPost('details');
        $remarks = $this->request->getPost('recommendation');
        $file = $this->request->getFile('file');
        $originalName = $file->getClientName();
        $user = session()->get('loggedUser');
        if(!$validation)
        {
            echo "Invalid! Please fill in the form to continue";
        }
        else
        {
            $totalQty = $inventoryModel->WHERE('inventID',$itemID)->first();
            if($qty>$totalQty['Qty'])
            {
                echo "Invalid! Insufficient number of stocks";
            }
            else
            {
                if($file->isValid() && ! $file->hasMoved())
                {
                    $file->move('Damage_Files/',$originalName);
                    $values = [
                        'DateCreated'=>$dateCreated,'inventID'=>$itemID,'Qty'=>$qty,
                        'Details'=>$details,'DamageRate'=>$defectType,'DateReport'=>$dateReport,'Image'=>$originalName,'Remarks'=>$remarks,'Status'=>0,'accountID'=>$user
                        ];
                    $damageReport->save($values);
                    //deduct the number of stocks vs damage stock
                    $newQty = $totalQty['Qty']-$qty;
                    $values = ['Qty'=>$newQty,];
                    $inventoryModel->update($itemID,$values);
                    echo "success";
                }
                else
                {
                    echo "File already uploaded";
                }
            }
        }
    }

    public function submitReport()
    {
        $repairModel = new \App\Models\repairModel();
        $damageModel = new \App\Models\damageModel();
        $id = $this->request->getPost('ID');
        $productID = $this->request->getPost('productID');
        $qty = $this->request->getPost('qty');
        $date_repair = $this->request->getPost('date_repair');
        $details = $this->request->getPost('details');
        $date_accomplish = "0000-00-00";
        $user = session()->get('loggedUser');
        $validation = $this->validate([
            'qty'=>'required',
            'date_repair'=>'required',
            'details'=>'required'
        ]);
        if(!$validation)
        {
            session()->setFlashdata('fail','Invalid! Please fill in the form to continue');
            return redirect()->to('/create-report/'.$id)->withInput();
        }
        else
        {
            $values = [
                'repairDate'=>$date_repair,'inventID'=>$productID,'Qty'=>$qty,'Details'=>$details,'dateAccomplished'=>$date_accomplish,'Status'=>0,'accountID'=>$user
            ];
            $repairModel->save($values);
            //update the status
            $value = ['Status'=>1];
            $damageModel->update($id,$value);
            session()->setFlashdata('success',"Great! You've successfully submitted the data.");
            return redirect()->to('/manage')->withInput();
        }
    }

    public function sendReport()
    {
        $accomplishmentModel = new \App\Models\accomplishmentModel();
        $repairModel = new \App\Models\repairModel();
        $inventoryModel = new \App\Models\inventoryModel();
        //data
        $itemID = $this->request->getPost('itemID');
        $date = $this->request->getPost('accomplish_date');
        $involveWorkers = $this->request->getPost('involveWorkers');
        $file = $this->request->getFile('file');
        $originalName = $file->getClientName();
        $validation = $this->validate([
            'accomplish_date'=>'required',
            'involveWorkers'=>'required',
            'file'=>'uploaded[file]'
        ]);
        if(!$validation)
        {
            echo "Invalid! Please fill in the form to continue";
        }
        else
        {
            if($file->isValid() && ! $file->hasMoved())
            {
                $file->move('Accomplishment/',$originalName);
                $values = [
                    'repairID'=>$itemID, 'Workers'=>$involveWorkers,'File'=>$originalName,'DateCreated'=>date('Y-m-d')
                ];
                $accomplishmentModel->save($values);
                //update the status
                $value = [
                    'dateAccomplished'=>$date,'Status'=>1,
                ];
                $repairModel->update($itemID,$value);
                //update the inventory
                $getInfo = $repairModel->WHERE('repairID',$itemID)->first();
                $inventoryQty = $inventoryModel->WHERE('inventID',$getInfo['inventID'])->first();
                //add the quantity from two models
                $total = $getInfo['Qty']+$inventoryQty['Qty'];
                $record = ['Qty'=>$total];
                $inventoryModel->update($getInfo['inventID'],$record);
                echo "success";
            }
            else
            {
                echo "File already uploaded";
            }
        }
    }

    public function sendAccomplishment()
    {
        $accomplishmentModel = new \App\Models\accomplishmentReportModel();
        $damageReportModel = new \App\Models\damageReportModel();
        $repairReportModel = new \App\Models\repairReportModel();
        $inventoryModel = new \App\Models\inventoryModel();
        //data
        $itemID = $this->request->getPost('itemID');
        $involveWorkers = $this->request->getPost('involveWorkers');
        $file = $this->request->getFile('file');
        $originalName = $file->getClientName();
        $validation = $this->validate([
            'accomplish_date'=>'required',
            'involveWorkers'=>'required',
            'file'=>'uploaded[file]'
        ]);
        if(!$validation)
        {
            echo "Invalid! Please fill in the form to continue";
        }
        else
        {
            if($file->isValid() && ! $file->hasMoved())
            {
                $file->move('Accomplishment/',$originalName);
                $values = [
                    'rrID'=>$itemID, 'Workers'=>$involveWorkers,'File'=>$originalName,'DateCreated'=>date('Y-m-d')
                ];
                $accomplishmentModel->save($values);
                //update the inventory
                $getInfo = $repairReportModel->WHERE('rrID',$itemID)->first();
                $damageInfo = $damageReportModel->WHERE('reportID',$getInfo['damageID'])->first();
                $inventoryQty = $inventoryModel->WHERE('inventID',$damageInfo['inventID'])->first();
                //add the quantity from two models
                $total = $getInfo['Qty']+$inventoryQty['Qty'];
                $record = ['Qty'=>$total];
                $inventoryModel->update($getInfo['inventID'],$record);
                echo "success";
            }
            else
            {
                echo "File already uploaded";
            }
        }
    }

    public function viewAccomplishmentReport()
    {
        $accomplishmentModel = new \App\Models\accomplishmentReportModel();
        $repairModel = new \App\Models\repairReportModel();
        $val = $this->request->getGet('value');
        $records = $accomplishmentModel->WHERE('rrID',$val)->first();
        if(empty($records['rrID']))
        {
            echo "No Record(s)";
        }
        else
        {
            //get the date completed
            $getinfo = $repairModel->WHERE('rrID',$val)->first();
            $imgFile = "Accomplishment/".$records['File'];
            $output="<div class='row g-3'>
                        <div class='col-lg-6'>
                            <div class='row g-3'>
                                <div class='col-12 form-group'>
                                    <label>Date Reported</label>
                                    <input type='date' class='form-control' value='".$records['DateCreated']."'/>
                                </div>
                                <div class='col-12 form-group'>
                                    <label>Date Completed</label>
                                    <input type='date' class='form-control' value='".$getinfo['dateAccomplished']."'/>
                                </div>
                                <div class='col-12 form-group'>
                                    <label>Repaired By</label>
                                    <textarea class='form-control'>".$records['Workers']."</textarea>
                                </div>
                            </div>
                        </div>
                        <div class='col-lg-6'>
                            <div class='img-container'>
                                <img src='".$imgFile."' id='image'/>
                            </div>
                        </div>
                    </div>";
            echo $output;
        }
    }

    public function viewReport()
    {
        $accomplishmentModel = new \App\Models\accomplishmentModel();
        $repairModel = new \App\Models\repairModel();
        $val = $this->request->getGet('value');
        $records = $accomplishmentModel->WHERE('repairID',$val)->first();
        //get the date completed
        $getinfo = $repairModel->WHERE('repairID',$val)->first();
        $imgFile = "Accomplishment/".$records['File'];
        $output="<div class='row g-3'>
                    <div class='col-lg-6'>
                        <div class='row g-3'>
                            <div class='col-12 form-group'>
                                <label>Date Reported</label>
                                <input type='date' class='form-control' value='".$records['DateCreated']."'/>
                            </div>
                            <div class='col-12 form-group'>
                                <label>Date Completed</label>
                                <input type='date' class='form-control' value='".$getinfo['dateAccomplished']."'/>
                            </div>
                            <div class='col-12 form-group'>
                                <label>Repaired By</label>
                                <textarea class='form-control'>".$records['Workers']."</textarea>
                            </div>
                        </div>
                    </div>
                    <div class='col-lg-6'>
                        <div class='img-container'>
                            <img src='".$imgFile."' id='image'/>
                        </div>
                    </div>
                </div>";
        echo $output;
    }

    public function transferItem()
    {
        $transferModel = new \App\Models\transferModel();
        $inventoryModel = new \App\Models\inventoryModel();
        //datas
        $inventID = $this->request->getPost('inventID');
        $categoryID = $this->request->getPost('categoryID');
        $supplierID = $this->request->getPost('supplierID');
        $expirationDate = $this->request->getPost('expirationdate');
        $itemNumber = $this->request->getPost('itemNumber');
        $code = $this->request->getPost('Code');
        $productName = $this->request->getPost('productName');
        $datePrepared = $this->request->getPost('datePrepared');
        $dateEffective = $this->request->getPost('dateEffective');
        $qty = $this->request->getPost('qty');
        $itemUnit = $this->request->getPost('itemUnit');
        $unitPrice = $this->request->getPost('unitPrice');
        $warehouse = $this->request->getPost('warehouse');
        $description = $this->request->getPost('description');
        $dept = $this->request->getPost('department');
        $delivery = $this->request->getPost('delivery');
        $track_num = $this->request->getPost('track_number');
        $driver = $this->request->getPost('driver');
        $plate_num = $this->request->getPost('plate_number');

        $validation = $this->validate([
            'itemNumber'=>'required','productName'=>'required','datePrepared'=>'required',
            'dateEffective'=>'required','qty'=>'required','itemUnit'=>'required','warehouse'=>'required','description'=>'required'
        ]);
        if(!$validation)
        {
            session()->setFlashdata('fail','Invalid! Please fill in the form to continue');
            return redirect()->to('/transfer/'.$inventID)->withInput();
        }
        else
        {
            if($delivery=="Logistics")
            {
                if(empty($track_num))
                {
                    session()->setFlashdata('fail','Invalid! Please enter the tracking number');
                    return redirect()->to('/transfer/'.$inventID)->withInput();
                }
                else
                {
                    $values = ['inventID'=>$inventID,'productID'=>$itemNumber,'productName'=>$productName,
                    'Code'=>$code,'Description'=>$description,'Qty'=>$qty,
                    'ItemUnit'=>$itemUnit,'unitPrice'=>$unitPrice,'datePrepared'=>$datePrepared,
                    'dateEffective'=>$dateEffective,'warehouseID'=>$warehouse,'categoryID'=>$categoryID,
                    'supplierID'=>$supplierID,'ExpirationDate'=>$expirationDate,'Status'=>0,'Department'=>$dept,
                    'cargo_type'=>$delivery,'Driver'=>$driver,'Plate_number'=>$plate_num,'TrackingNumber'=>$track_num,
                    'accountID'=>session()->get('loggedUser')];
                    $transferModel->save($values);

                    $invent = $inventoryModel->WHERE('inventID',$inventID)->first();
                    $newQty = $invent['Qty']-$qty;
                    $record = ['Qty'=>$newQty,];
                    $inventoryModel->update($inventID,$record);

                    session()->setFlashdata('success','Great! Successfully submitted the request');
                    return redirect()->to('/stocks')->withInput();
                }
            }
            else if($delivery=="Company Service")
            {
                if(empty($driver)||empty($plate_num))
                {
                    session()->setFlashdata('fail','Invalid! Please select your driver and plate number');
                    return redirect()->to('/transfer/'.$inventID)->withInput();
                }
                else
                {
                    $values = ['inventID'=>$inventID,'productID'=>$itemNumber,'productName'=>$productName,
                    'Code'=>$code,'Description'=>$description,'Qty'=>$qty,
                    'ItemUnit'=>$itemUnit,'unitPrice'=>$unitPrice,'datePrepared'=>$datePrepared,
                    'dateEffective'=>$dateEffective,'warehouseID'=>$warehouse,'categoryID'=>$categoryID,
                    'supplierID'=>$supplierID,'ExpirationDate'=>$expirationDate,'Status'=>0,'Department'=>$dept,
                    'cargo_type','Driver','Plate_number','TrackingNumber',
                    'accountID'=>session()->get('loggedUser')];
                    $transferModel->save($values);

                    $invent = $inventoryModel->WHERE('inventID',$inventID)->first();
                    $newQty = $invent['Qty']-$qty;
                    $record = ['Qty'=>$newQty,];
                    $inventoryModel->update($inventID,$record);

                    session()->setFlashdata('success','Great! Successfully submitted the request');
                    return redirect()->to('/stocks')->withInput();
                }
            }
        }
    }

    public function receiveReport()
    {
        $receiveLogsModel = new \App\Models\receiveLogsModel();
        $transferModel = new \App\Models\transferModel();
        $inventoryModel = new \App\Models\inventoryModel();
        //data
        $dateReceived = date('Y-m-d');
        $id = $this->request->getPost('transferID');
        $receiver = $this->request->getPost('receiver');
        $file = $this->request->getFile('file');
        $originalName = $file->getClientName();

        $validation = $this->validate([
            'receiver'=>'required',
            'file'=>'uploaded[file]'
        ]);
        if(!$validation)
        {
            session()->setFlashdata('fail','Invalid! Please fill in the form to continue');
            return redirect()->to('/receiving-item')->withInput();
        }
        else
        {
            if($file->isValid() && ! $file->hasMoved())
            {
                $file->move('Report/',$originalName);
                $values = [
                    'dateReceived'=>$dateReceived,'receivedBy'=>$receiver,'proof'=>$originalName,'transferID'=>$id
                ];
                $receiveLogsModel->save($values);
                //update the status
                $record = ['Status'=>1];
                $transferModel->update($id,$record);
                //save the data to inventory
                $builder = $this->db->table('tbltransferitem');
                $builder->select('*');
                $builder->WHERE('transferID',$id);
                $data = $builder->get();
                if($row = $data->getRow())
                {
                    $check = $inventoryModel->WHERE('productID',$row->productID)->WHERE('warehouseID',$row->warehouseID)->first();
                    if(empty($check['productID']))
                    {
                        $value = [
                            'Date'=>$dateReceived,'Location'=>'N/A','productID'=>$row->productID,'productName'=>$row->productName,'Code'=>$row->Code,
                            'Description'=>$row->Description,'ItemUnit'=>$row->ItemUnit,'unitPrice'=>$row->unitPrice,'Qty'=>$row->Qty,'ReOrder'=>0,
                            'categoryID'=>$row->categoryID,'ExpirationDate'=>$row->ExpirationDate,'supplierID'=>$row->supplierID,'warehouseID'=>$row->warehouseID,
                        ];
                        $inventoryModel->save($value);
                    }
                    else
                    {
                        $newQty = $check['Qty']+$row->Qty;
                        $values = ['Qty'=>$newQty,];
                        $inventoryModel->update($check['inventID'],$values);
                    }
                }
                session()->setFlashdata('success','Great! Successfully received');
                return redirect()->to('/receiving-item')->withInput();
            }
            else
            {
                session()->setFlashdata('fail','Invalid! File already uploaded');
                return redirect()->to('/receiving-item')->withInput();
            }
        }
    }

    public function damageReport()
    {
        $damageReportModel = new \App\Models\damageReportModel();
        $inventoryModel = new \App\Models\inventoryModel();
        //datas
        $validation = $this->validate([
            'itemID'=>'required',
            'dateReport'=>'required',
            'defectType'=>'required',
            'qty'=>'required',
            'details'=>'required',
            'recommendation'=>'required',
            'file'=>'uploaded[file]'
        ]);
        //datas
        $dateCreated = date('Y-m-d');
        $itemID = $this->request->getPost('itemID');
        $defectType = $this->request->getPost('defectType');
        $dateReport = $this->request->getPost('dateReport');
        $qty = $this->request->getPost('qty');
        $details = $this->request->getPost('details');
        $remarks = $this->request->getPost('recommendation');
        $file = $this->request->getFile('file');
        $originalName = $file->getClientName();
        $user = session()->get('loggedUser');
        if(!$validation)
        {
            session()->setFlashdata('fail','Invalid! Please fill in the form');
            return redirect()->to('/damage-report')->withInput();
        }
        else
        {
            $totalQty = $inventoryModel->WHERE('inventID',$itemID)->first();
            if($qty>$totalQty['Qty'])
            {
                session()->setFlashdata('fail','Insufficient number of stocks');
                return redirect()->to('/damage-report')->withInput();
            }
            else
            {
                if($file->isValid() && ! $file->hasMoved())
                {
                    $file->move('Damage_Files/',$originalName);
                    $values = [
                        'DateCreated'=>$dateCreated,'inventID'=>$itemID,'Qty'=>$qty,
                        'Details'=>$details,'DamageRate'=>$defectType,'DateReport'=>$dateReport,
                        'Image'=>$originalName,'Remarks'=>$remarks,'Status'=>0,'DateApproved'=>'0000-00-00','accountID'=>$user
                        ];
                    $damageReportModel->save($values);
                    session()->setFlashdata('success','Great! Successfully submitted for review');
                    return redirect()->to('/damage-report')->withInput();
                }
                else
                {
                    session()->setFlashdata('fail','Invalid! File already uploaded');
                    return redirect()->to('/damage-report')->withInput();
                }
            }
        }
    }

    public function acceptDamageReport()
    {
        $damageReportModel = new \App\Models\damageReportModel();
        $inventoryModel = new \App\Models\inventoryModel();
        //datas
        $itemID = $this->request->getPost('value');

        $values = ['Status'=>1];
        $damageReportModel->update($itemID,$values);
        //update the inventory
        $builder = $this->db->table('tbldamagereport');
        $builder->select('Qty,inventID');
        $builder->WHERE('reportID',$itemID);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            $inventory = $inventoryModel->WHERE('inventID',$row->inventID)->first();
            $remain_qty = $inventory['Qty']-$row->Qty;
            $new_values = ['Qty'=>$remain_qty];
            $inventoryModel->update($inventory['inventID'],$new_values);
        }
        echo "Success";
    }

    public function repairReport()
    {
        $repairReport = new \App\Models\repairReportModel();
        //data
        $itemID  = $this->request->getPost('itemID');
        $details = $this->request->getPost('details');
        $startDate = $this->request->getPost('startDate');
        $dateAccomplish = $this->request->getPost('dateAccomplish');
        $status=0;
        $approveDate = "0000-00-00";

        $validation = $this->validate([
            'itemID'=>'required','details'=>'required','startDate'=>'required','dateAccomplish'=>'required',
        ]);
        if(!$validation)
        {
            session()->setFlashdata('fail','Invalid! Please fill in the form');
            return redirect()->to('/repair-report')->withInput();
        }
        else
        {
            $values = [
                'repairDate'=>$startDate,'reportID'=>$itemID,'Details'=>$details,'dateAccomplished'=>$dateAccomplish,
                'Status'=>$status,'approveDate'=>$approveDate,'accountID'=>session()->get('loggedUser')
            ];
            $repairReport->save($values);
            session()->setFlashdata('success','Great! Successfully submitted for review');
            return redirect()->to('/repair-report')->withInput();
        }
    }

    public function acceptRepairReport()
    {
        $repairReport = new \App\Models\repairReportModel();
        //datas
        $itemID = $this->request->getPost('value');

        $values = ['Status'=>1,'approveDate'=>date('Y-m-d')];
        $repairReport->update($itemID,$values);
        echo "Success";
    }

    public function saveRequest()
    {
        $requestModel = new \App\Models\requestModel();
        //save data 
        $itemID = $this->request->getPost('itemID');
        $productName = $this->request->getPost('productName');
        $qty = $this->request->getPost('qty');
        $dateCreated = $this->request->getPost('dateCreated');
        $dateEffective = $this->request->getPost('dateEffective');
        $location = $this->request->getPost('location');
        $details = $this->request->getPost('details');

        $validation = $this->validate([
            'itemID'=>'required','productName'=>'required',
            'qty'=>'required','dateCreated'=>'required',
            'dateEffective'=>'required','location'=>'required',
            'details'=>'required',
        ]);

        if(!$validation)
        {
            session()->setFlashdata('fail','Invalid! Please fill in the form');
            return redirect()->to('/transfer-item')->withInput();
        }
        else
        {
            $values = [
                'itemID'=>$itemID, 'productName'=>$productName,'Qty'=>$qty,
                'EffectiveDate'=>$dateEffective,'warehouseID'=>$location,'Details'=>$details,
                'Status'=>0,'accountID'=>session()->get('loggedUser'),'DateCreated'=>$dateCreated
            ];
            $requestModel->save($values);

            session()->setFlashdata('success','Great! Successfully submitted for review');
            return redirect()->to('/transfer-item')->withInput();
        }
    }

    public function acceptRequest()
    {
        $requestModel = new \App\Models\requestModel();
        $logModel = new \App\Models\logModel();
        //data
        $item = $this->request->getPost('value');
        $values = ['Status'=>1];
        $requestModel->update($item,$values);
        //log records
        $records = ['accountID'=>session()->get('loggedUser'),'DateApproved'=>date('Y-m-d')];
        $logModel->save($records);
        echo "Success";
    }

    public function scanning()
    {
        $val = $this->request->getPost('text');
        $scanModel = new \App\Models\scanItemModel();
        //datas
        $productName = "";
        $builder = $this->db->table('tblinventory a');
        $builder->select('a.*');
        $builder->join('tblqrcode b','b.inventID=a.inventID','LEFT');
        $builder->WHERE('b.TextValue',$val);
        $data = $builder->get();
        if($row = $data->getRow())
        {
            $productName = $row->productName;
        }
        //check if item is already scanned
        $builder = $this->db->table('tblscanned_items');
        $builder->select('*');
        $builder->WHERE('Code',$val)->WHERE('accountID',session()->get('loggedUser'));
        $data = $builder->get();
        if($row=$data->getRow())
        {
            echo $row->productName." already scanned";
        }
        else
        {
            $values = [
                'productName'=>$productName,'Code'=>$val,
                'accountID'=>session()->get('loggedUser'),'Status'=>0,'Date'=>date('Y-m-d')
            ];
            $scanModel->save($values);
            echo "success";
        }
    }

    public function viewItems()
    {
        $user = session()->get('loggedUser');
        $builder = $this->db->table('tblscanned_items');
        $builder->select('*');
        $builder->WHERE('Status',0);
        $builder->WHERE('accountID',$user);
        $data = $builder->get();
        foreach($data->getResult() as $row)
        {
            ?>
            <tr>
                <td><input type="checkbox" class="checkbox" value="<?php echo $row->scanID ?>" name="itemID[]" id="itemID" checked/></td>
                <td><?php echo $row->productName ?></td>
                <td><?php echo $row->Code ?></td>
            </tr>
            <?php
        }
    }
}