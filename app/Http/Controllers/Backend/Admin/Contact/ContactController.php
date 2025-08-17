<?php

namespace App\Http\Controllers\Backend\Admin\Contact;

use App\Http\Controllers\Controller;
use App\Http\Traits\AuditRelationTraits;
use Illuminate\Http\Request;

class ContactController extends Controller
{
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
