<?php

namespace App\Http\Controllers\Backend\Admin\BannerManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banner\BannerRequest;
use App\Http\Traits\AuditRelationTraits;
use App\Models\Banner;
use App\Services\Admin\Banner\BannerService;
use GuzzleHttp\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
      use AuditRelationTraits;
    protected BannerService $banner;

    public function __construct(BannerService $banner)
    {
        $this->banner = $banner;
    }
     protected function redirectIndex(): RedirectResponse
    {
        return redirect()->route('bm.banner.index');
    }

    protected function redirectTrashed(): RedirectResponse
    {
        return redirect()->route('bm.banner.trash');
    }


     public static function middleware(): array
    {
        return [
            'auth:admin', // Applies 'auth:admin' to all methods

            // Permission middlewares using the Middleware class
            new Middleware('permission:banner-list', only: ['index']),
            new Middleware('permission:banner-details', only: ['show']),
            new Middleware('permission:banner-create', only: ['create', 'store']),
            new Middleware('permission:banner-edit', only: ['edit', 'update']),
            new Middleware('permission:banner-delete', only: ['destroy']),
            new Middleware('permission:banner-trash', only: ['trash']),
            new Middleware('permission:banner-restore', only: ['restore']),
            new Middleware('permission:banner-permanent-delete', only: ['permanentDelete']),
            //add more permissions if needed
        ];
    }
  
    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
    {
         if ($request->ajax()) {
            $query = $this->banner->getBanners();
            return DataTables::eloquent($query)
            
                ->editColumn('status', fn($banner) => "<span class='badge badge-soft {$banner->status_color}'>{$banner->status_label}</span>")
              
                ->editColumn('created_by', function ($banner) {
                    return $this->creater_name($banner);
                })
                ->editColumn('created_at', function ($banner) {
                    return $banner->created_at;
                })
                ->editColumn('action', function ($banner) {
                    $menuItems = $this->menuItems($banner);
                    return view('components.admin.action-buttons', compact('menuItems'))->render();
                })
                ->rawColumns(['status','action', 'created_by', 'created_at',])
                ->make(true);
        }
        return view('backend.admin.banners.index');
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
                'routeName' => 'bm.banner.edit',
                'params' => [encrypt($model->id)],
                'label' => 'Edit',
                'permissions' => ['permission-edit']
            ],
            [
                'routeName' => 'bm.banner.status',
                'params' => [encrypt($model->id)],
                'label' => $model->status ? 'Inactive' : 'Activate',
                'status' => true,
                'permissions' => ['permission-status']
            ],

            [
                'routeName' => 'bm.banner.destroy',
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
        return view('backend.admin.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BannerRequest $request)
    {
         try {
            $validated = $request->validated();
            $file = $request->validated('image') && $request->hasFile('image') ? $request->file('image') : null;
            $this->banner->createBanner($validated, $file);
            session()->flash('success', 'Banner created successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Banner create failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
          $data = $this->banner->getBanner($id);
          $data['status'] = $data->status ? 'Active' : 'Inactive';
        $data['creater_name'] = $this->creater_name($data);
        $data['updater_name'] = $this->updater_name($data);
       
        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data['banner'] = $this->banner->getBanner($id);
        return view('backend.admin.banners.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BannerRequest $request, string $id)
    {
        try {
            $validated = $request->validated();
            $file = $request->validated('image') && $request->hasFile('image') ? $request->file('image') : null;
            $this->banner->updateBanner($this->banner->getBanner($id), $validated, $file);
            session()->flash('success', 'Banner updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Banner update failed!');
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
            $banner = $this->banner->getBanner($id);
            $this->banner->delete($banner);
            session()->flash('success', 'Banner deleted successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Banner delete failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }
    public function status(string $id)
    {
        try {
            $banner = $this->banner->getBanner($id);
            $this->banner->toggleStatus($banner);
            session()->flash('success', 'Banner status updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Banner status update failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    public function trash(Request $request)
    {
         if ($request->ajax()) {
            $query = $this->banner->getBanners()->onlyTrashed();
            return DataTables::eloquent($query)
                ->editColumn('status', fn($banner) => "<span class='badge badge-soft {$banner->status_color}'>{$banner->status_label}</span>")
                ->editColumn('deleted_by', function ($banner) {
                    return $this->creater_name($banner);
                })
                ->editColumn('created_at', fn($banner) => $banner->created_at_formatted)
               ->editColumn('action', fn($banner) => view('components.admin.action-buttons', [
                    'menuItems' => $this->menuItemsTrashed($banner),
                ])->render())
                ->rawColumns(['status','action', 'deleted_by', 'created_at',])
                ->make(true);
        }
        return view('backend.admin.banners.trash');
    }

     

      protected function menuItemsTrashed($model): array
    {
        return [
           [
                'routeName' => 'bm.banner.restore',
                'params' => [encrypt($model->id)],
                'label' => 'Restore',
            ],
            [
                'routeName' => 'bm.banner.permanent-delete',
                'params' => [encrypt($model->id)],
                'label' => 'Permanent Delete',
                'p-delete' => true,
            ]
           

        ];
    }


 public function restore(string $id): RedirectResponse
    {
        try {
            $banner = Banner::onlyTrashed()->findOrFail(decrypt($id));

            $this->banner->restore($banner, $id);
            session()->flash('success', "Banner restored successfully");
        } catch (\Throwable $e) {
            session()->flash('Banner restore failed');
            throw $e;
        }
        return $this->redirectTrashed();
    }


    public function permanentDelete(string $encryptedId): RedirectResponse
    {
        try {
            $id = decrypt($encryptedId);
            $banner = Banner::onlyTrashed()->findOrFail($id);

            $this->banner->deletePermanent($banner, $id);
            $banner->forceDelete();

            session()->flash('success', 'Banner permanently deleted successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Banner permanent delete failed');
            throw $e;
        }
        return $this->redirectTrashed();
    }
}
