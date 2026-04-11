<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class KeyValueCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Keyvalue::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/keyvalue');
        CRUD::setEntityNameStrings('key-value', 'key-values');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('keyvalue.view')) abort(403);
        CRUD::column('keyword_master.keyword')->label('Keyword');
        CRUD::column('key');
        CRUD::column('value')->limit(50);
        CRUD::column('level');
        CRUD::column('status')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('keyvalue.create')) abort(403);
        CRUD::field('keyword_master_id')->type('select2')->entity('keywordMaster')->attribute('keyword');
        CRUD::field('key')->required();
        CRUD::field('value')->type('textarea')->required();
        CRUD::field('parent_id')->type('select2')->entity('parent')->attribute('key')->hint('Optional - for hierarchical values');
        CRUD::field('level')->type('number')->default(0);
        CRUD::field('status')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('keyvalue.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
