<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SpareCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\XlSpareMaster::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/spare');
        CRUD::setEntityNameStrings('spare', 'spares');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.spare.list'); // Custom list view if needed
    }

    // Main Dashboard (Import + Links)
    public function index()
    {
        return view('admin.spare.index'); // Reuse or copy your existing spare/index.blade.php
    }

    // Parts Ordering Report
    public function orderingReport()
    {
        return app(\App\Http\Controllers\SpareOrderingreportController::class)
            ->orderingreport(request());
    }

    // Partwise Requirement
    public function partwise()
    {
        return app(\App\Http\Controllers\SpareImportController::class)
            ->partwise();
    }
}
