<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Helpers\XCommonHelper;
use App\Helpers\XpricingHelper;

class SpareRequestCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\XlSpareRequest::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/spare-request');
        CRUD::setEntityNameStrings('Spare Request', 'Spare Requests');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.spare-request.list');

        CRUD::column('id')->label('S.No');
        CRUD::column('ro_number')->label('RO Number');
        CRUD::column('srv_brnch_id')->label('Branch');
        CRUD::column('cust_name')->label('Customer Name');
        CRUD::column('cust_mobile')->label('Mobile');
        CRUD::column('regn_no')->label('Vehicle No');
        CRUD::column('ro_date')->label('RO Date');
        CRUD::column('status')->label('Status');
    }

    protected function setupCreateOperation()
    {
        $this->crud->setCreateView('admin.spare-request.create');

        // ✅ Pass data using your helpers
        $this->data['branch'] = XCommonHelper::getServiceBranch();   // Service Branches only
        $this->data['models'] = XpricingHelper::getModelsX();        // All Models (as per your other project)
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
