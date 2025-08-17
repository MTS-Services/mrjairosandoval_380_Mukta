<?php

namespace App\Http\Controllers\Backend\Admin\ServiceManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Services\ServiceRequest;
use App\Http\Traits\AuditRelationTraits;
use App\Models\Services;
use App\Services\Admin\Service\Service;
use GuzzleHttp\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ServiceController extends Controller
{
  
     use AuditRelationTraits;
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
     protected function redirectIndex(): RedirectResponse
    {
        return redirect()->route('sm.service.index');
    }

    protected function redirectTrashed(): RedirectResponse
    {
        return redirect()->route('sm.service.trash');
    }


     public static function middleware(): array
    {
        return [
            'auth:admin', // Applies 'auth:admin' to all methods

            // Permission middlewares using the Middleware class
            new Middleware('permission:services-list', only: ['index']),
            new Middleware('permission:services-details', only: ['show']),
            new Middleware('permission:services-create', only: ['create', 'store']),
            new Middleware('permission:services-edit', only: ['edit', 'update']),
            new Middleware('permission:services-delete', only: ['destroy']),
            new Middleware('permission:services-trash', only: ['trash']),
            new Middleware('permission:services-restore', only: ['restore']),
            new Middleware('permission:services-permanent-delete', only: ['permanentDelete']),
            //add more permissions if needed
        ];
    }
  
    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
    {
         if ($request->ajax()) {
            $query = $this->service->getServices();
            return DataTables::eloquent($query)
            
                ->editColumn('status', fn($service) => "<span class='badge badge-soft {$service->status_color}'>{$service->status_label}</span>")
              
                ->editColumn('created_by', function ($service) {
                    return $this->creater_name($service);
                })
                ->editColumn('created_at', function ($service) {
                    return $service->created_at;
                })
                ->editColumn('action', function ($service) {
                    $menuItems = $this->menuItems($service);
                    return view('components.admin.action-buttons', compact('menuItems'))->render();
                })
                ->rawColumns(['status','action', 'created_by', 'created_at',])
                ->make(true);
        }
        return view('backend.admin.services.index');
    }

     

      protected function menuItems($model): array
    {
        return [
            [
                'routeName' => 'javascript:void(0)',
                'data-id' => encrypt($model->id),
                'className' => 'view',
                'label' => 'Details',
                'permissions' => ['permission-list', 'permission-delete', 'permission-status']
            ],
            [
                'routeName' => 'sm.service.edit',
                'params' => [encrypt($model->id)],
                'label' => 'Edit',
                'permissions' => ['permission-edit']
            ],
            [
                'routeName' => 'sm.service.status',
                'params' => [encrypt($model->id)],
                'label' => $model->status ? 'Inactive' : 'Activate',
                'status' => true,
                'permissions' => ['permission-status']
            ],

            [
                'routeName' => 'sm.service.destroy',
                'params' => [encrypt($model->id)],
                'label' => 'Delete',
                'delete' => true,
                'permissions' => ['permission-delete']
            ]

        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.admin.services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ServiceRequest $request)
    {
        try {
            $validated = $request->validated();
            $file = $request->validated('icon') && $request->hasFile('icon') ? $request->file('icon') : null;
            $this->service->createService($validated, $file);
            session()->flash('success', 'Service created successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Service create failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = $this->service->getService($id);
        $data['creater_name'] = $this->creater_name($data);
        $data['updater_name'] = $this->updater_name($data);
       
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data['service'] = $this->service->getService($id);
        return view('backend.admin.services.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServiceRequest $request, string $id)
    {
        try {
            $service = $this->service->getService($id);

            $validated = $request->validated();
            $file = $request->validated('icon') && $request->hasFile('icon') ? $request->file('icon') : null;
            $this->service->updateService($service, $validated, $file);
            session()->flash('success', 'Service updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Service update failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $service = $this->service->getService($id);
            $this->service->delete($service);
            session()->flash('success', 'Service deleted successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Service delete failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    public function status(string $id)
    {
        try {
            $service = $this->service->getService($id);
            $this->service->toggleStatus($service);
            session()->flash('success', 'Service status updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Service status update failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

     public function trash(Request $request)
    {
         if ($request->ajax()) {
            $query = $this->service->getServices()->onlyTrashed();
            return DataTables::eloquent($query)
                ->editColumn('status', fn($service) => "<span class='badge badge-soft {$service->status_color}'>{$service->status_label}</span>")
                ->editColumn('deleted_by', function ($service) {
                    return $this->creater_name($service);
                })
                ->editColumn('created_at', fn($service) => $service->created_at_formatted)
                ->editColumn('action', function ($service) {
                    $menuItems = $this->menuItemsTrashed($service);
                    return view('components.admin.action-buttons', compact('menuItems'))->render();
                })
                ->rawColumns(['status','action', 'deleted_by', 'created_at',])
                ->make(true);
        }
        return view('backend.admin.services.trash');
    }

     

      protected function menuItemsTrashed($model): array
    {
        return [
           [
                'routeName' => 'sm.service.restore',
                'params' => [encrypt($model->id)],
                'label' => 'Restore',
            ],
            [
                'routeName' => 'sm.service.permanent-delete',
                'params' => [encrypt($model->id)],
                'label' => 'Permanent Delete',
                'p-delete' => true,
            ]
           

        ];
    }


 public function restore(string $id): RedirectResponse
    {
        try {
            $service = Services::onlyTrashed()->findOrFail(decrypt($id));

            $this->service->restore($service, $id);
            session()->flash('success', "Service restored successfully");
        } catch (\Throwable $e) {
            session()->flash('Faq restore failed');
            throw $e;
        }
        return $this->redirectTrashed();
    }


    public function permanentDelete(string $encryptedId): RedirectResponse
    {
        try {
            $id = decrypt($encryptedId);
            $service = Services::onlyTrashed()->findOrFail($id);

            $this->service->deletePermanent($service, $id);
            $service->forceDelete();

            session()->flash('success', 'Service permanently deleted successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Service permanent delete failed');
            throw $e;
        }
        return $this->redirectTrashed();
    }

}
