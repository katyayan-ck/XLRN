<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use App\Models\Admin\Branch;
use App\Models\Admin\Department;
use App\Models\Admin\Designation;
use App\Models\IAM\Post;

class PostCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;


    public function setup()
    {
        CRUD::setModel(Post::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/post');
        CRUD::setEntityNameStrings('post', 'posts');
    }

    protected function setupListOperation()
    {
        $this->crud->setListView('admin.post.list');
    }

    public function index()
    {
        $this->crud->setListView('admin.post.list');

        $posts = Post::with(['branch', 'department', 'designation'])
            ->select([
                'id',
                'code',
                'title',
                'branch_id',
                'department_id',
                'designation_id',
                'description',
                'max_assignees',
                'is_active',
                'is_vacant'
            ])
            ->orderBy('id', 'desc')
            ->get();

        $gridData = $posts->map(function ($post, $index) {
            $mapped = $post->toArray();
            $mapped['serial_no'] = $index + 1;
            $mapped['branch']      = $post->branch?->name ?? '—';
            $mapped['department']  = $post->department?->name ?? '—';
            $mapped['designation'] = $post->designation?->name ?? '—';

            $editUrl = backpack_url("post/{$post->id}/edit");

            $mapped['action'] = '
                <div class="d-flex gap-2 justify-content-center">
                    <a href="' . $editUrl . '"
                       class="btn btn-sm btn-primary py-1 px-2"
                       title="Edit">
                         Edit
                    </a>
                </div>
            ';
            return $mapped;
        })->values();

        return view('admin.post.list', [
            'title' => 'All Posts',
            'gridConfig' => [
                'columns' => [
                    ['field' => 'serial_no',     'headerName' => 'S.No'],
                    ['field' => 'code',          'headerName' => 'Post Code'],
                    ['field' => 'title',         'headerName' => 'Post Title'],
                    ['field' => 'designation',   'headerName' => 'Designation'],
                    ['field' => 'department',    'headerName' => 'Department'],
                    ['field' => 'branch',        'headerName' => 'Branch'],
                    ['field' => 'max_assignees', 'headerName' => 'Max Assignees'],
                    ['field' => 'description',   'headerName' => 'Description'],
                    ['field' => 'is_active',     'headerName' => 'Active'],
                    ['field' => 'is_vacant',     'headerName' => 'Vacant'],
                    ['field' => 'action',        'headerName' => 'Actions']
                ],
                'data' => $gridData
            ]
        ]);
    }

    public function create()
    {
        $this->crud->setCreateView('admin.post.create');

        return view('admin.post.create', [
            'title'        => 'Add New Post',
            'branches'     => Branch::orderBy('name')->get(),
            'departments'  => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'           => 'required|string|unique:xlr8_iam_post,code',
            'title'          => 'required|string|max:255',
            'branch_id'      => 'required|exists:xlr8_admin_branch,id',
            'department_id'  => 'required|exists:xlr8_admin_department,id',
            'designation_id' => 'required|exists:xlr8_admin_designation,id',
            'description'    => 'nullable|string',
            'max_assignees'  => 'required|integer|min:1',
            'is_active'      => 'boolean',
            'is_vacant'      => 'boolean',
        ]);

        Post::create($validated);

        \Alert::success('Post created successfully!')->flash();

        return redirect(backpack_url('post'));
    }

    public function edit($id)
    {
        $this->crud->setEditView('admin.post.edit');

        $post = Post::with(['branch', 'department', 'designation'])->findOrFail($id);

        return view('admin.post.edit', [
            'title'        => 'Edit Post - ' . $post->title,
            'post'         => $post,
            'branches'     => Branch::orderBy('name')->get(),
            'departments'  => Department::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'code'           => 'required|string|unique:xlr8_iam_post,code,' . $id,
            'title'          => 'required|string|max:255',
            'branch_id'      => 'required|exists:xlr8_admin_branch,id',
            'department_id'  => 'required|exists:xlr8_admin_department,id',
            'designation_id' => 'required|exists:xlr8_admin_designation,id',
            'description'    => 'nullable|string',
            'max_assignees'  => 'required|integer|min:1',
            'is_active'      => 'boolean',
            'is_vacant'      => 'boolean',
        ]);

        $post->update($validated);

        \Alert::success('Post updated successfully!')->flash();

        return redirect(backpack_url('post'));
    }
}
