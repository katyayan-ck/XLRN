<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Core\Employee;
use App\Models\Core\Post;
use App\Models\Core\EmployeePostAssignment;

class EmployeePostAssignmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(EmployeePostAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-post-assignment');
        CRUD::setEntityNameStrings('post assignment', 'post assignments');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.employee-post-assignment.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.employee-post-assignment.list');

        $assignments = EmployeePostAssignment::with(['employee.person', 'post'])
            ->select([
                'id',
                'employee_id',
                'post_id',
                'from_date',
                'to_date',
                'assignment_order',
                'is_current'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $assignments->map(function ($assign, $index) {
            $mapped = $assign->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['employee_name'] = $assign->employee && $assign->employee->person
                ? trim($assign->employee->person->first_name . ' ' . $assign->employee->person->last_name)
                : '—';
            $mapped['post_name'] = $assign->post
                ? ($assign->post->name ?? $assign->post->display_name ?? $assign->post->title ?? '—')
                : '—';

            $editUrl = backpack_url("employee-post-assignment/{$assign->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '" class="btn btn-sm btn-primary py-1 px-2" title="Edit">Edit</a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.employee-post-assignment.list', [
            'title' => 'All Post Assignments',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',      'headerName' => 'S.No'],
                    ['field' => 'employee_name',  'headerName' => 'Employee'],
                    ['field' => 'post_name',      'headerName' => 'Post'],
                    ['field' => 'from_date',      'headerName' => 'From Date'],
                    ['field' => 'to_date',        'headerName' => 'To Date'],
                    ['field' => 'assignment_order', 'headerName' => 'Order'],
                    ['field' => 'is_current',     'headerName' => 'Current'],
                    ['field' => 'action',         'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.employee-post-assignment.create');

        return view('admin.employee-post-assignment.create', [
            'title'      => 'Add New Post Assignment',
            'employees'  => Employee::with('person')->orderBy('code')->get(),
            'posts' => Post::orderBy('id')->get(),   // Safe sorting (id se, name nahi tha)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'post_id'         => 'required|exists:posts,id',
            'from_date'       => 'required|date',
            'to_date'         => 'nullable|date|after_or_equal:from_date',
            'assignment_order' => 'integer|min:1',
            'is_current'      => 'boolean',
        ]);

        EmployeePostAssignment::create($validated);

        \Alert::success('Post Assignment created successfully!')->flash();
        return redirect(backpack_url('employee-post-assignment'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.employee-post-assignment.edit');

        $assignment = EmployeePostAssignment::with(['employee.person', 'post'])->findOrFail($id);

        return view('admin.employee-post-assignment.edit', [
            'title'      => 'Edit Post Assignment',
            'assignment' => $assignment,
            'employees'  => Employee::with('person')->orderBy('code')->get(),
            'posts' => Post::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $assignment = EmployeePostAssignment::findOrFail($id);

        $validated = $request->validate([
            'employee_id'     => 'required|exists:employees,id',
            'post_id'         => 'required|exists:posts,id',
            'from_date'       => 'required|date',
            'to_date'         => 'nullable|date|after_or_equal:from_date',
            'assignment_order' => 'integer|min:1',
            'is_current'      => 'boolean',
        ]);

        $assignment->update($validated);

        \Alert::success('Post Assignment updated successfully!')->flash();
        return redirect(backpack_url('employee-post-assignment'));
    }
}
