<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Helpers\XCommonHelper;
use App\Helpers\XpricingHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpareRequestCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setRoute(config('backpack.base.route_prefix') . '/spare-request');
        CRUD::setEntityNameStrings('Spare Request', 'Spare Requests');
        // We are NOT setting model because we use custom list view with AG Grid
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.spare-request.list');
        // No columns needed here - we define everything in the blade file
    }

    protected function setupCreateOperation()
    {
        $this->crud->setCreateView('admin.spare-request.create');

        $this->data['branch'] = XCommonHelper::getServiceBranch();
        $this->data['models'] = XpricingHelper::getModelsX();
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Fetch parts for autocomplete (Part No or Name)
     */
    public function fetchParts(Request $request)
    {
        $query = $request->get('query');
        $type  = $request->get('type');

        if (empty($query) || !in_array($type, ['part_no', 'name'])) {
            return response()->json([]);
        }

        $parts = DB::table('xcelr8_spare_master')
            ->where($type, 'LIKE', "%{$query}%")
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->select('id', 'part_no', 'name')
            ->limit(15)
            ->get();

        return response()->json($parts);
    }
}
