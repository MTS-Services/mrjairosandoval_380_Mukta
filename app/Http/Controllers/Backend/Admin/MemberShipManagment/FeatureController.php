<?php

namespace App\Http\Controllers\Backend\Admin\MemberShipManagment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberShipManagement\FeatureRequest;
use App\Http\Traits\AuditRelationTraits;
use App\Services\Admin\MemberShipManagement\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class FeatureController extends Controller
{
    use AuditRelationTraits;

    protected FeatureService $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    public static function middleware(): array
    {
        return [
            'auth:admin', // Applies 'auth:admin' to all methods
        ];
    }

    protected function redirectIndex(): RedirectResponse
    {
        return redirect()->route('mm.feature.index');
    }

    protected function redirectTrashed(): RedirectResponse
    {
        return redirect()->route('mm.feature.trash');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->featureService->getFeatures();

            return DataTables::eloquent($query)
                ->editColumn('status', fn($feature) => "<span class='badge badge-soft {$feature->status_color}'>{$feature->status_label}</span>")
                ->editColumn('created_by', fn($feature) => $this->creater_name($feature))
                ->editColumn('created_at', fn($feature) => $feature->created_at_formatted)
                ->editColumn('action', fn($feature) => view('components.admin.action-buttons', ['menuItems' => $this->menuItems($feature)])->render())
                ->rawColumns(['status', 'created_by', 'created_at', 'action'])
                ->make(true);
        }

        return view('backend.admin.membership-management.feature.index');
    }

    protected function menuItems($model): array
    {
        return [
            [
                'routeName' => 'javascript:void(0)',
                'data-id' => encrypt($model->id),
                'className' => 'view',
                'label' => 'Details',
            ],
            [
                'routeName' => 'mm.feature.status',
                'params' => [encrypt($model->id)],
                'label' => $model->status_btn_label,
            ],
            [
                'routeName' => 'mm.feature.edit',
                'params' => [encrypt($model->id)],
                'label' => 'Edit',
            ],
            [
                'routeName' => 'mm.feature.destroy',
                'params' => [encrypt($model->id)],
                'label' => 'Delete',
                'delete' => true,
            ],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('backend.admin.membership-management.feature.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FeatureRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $this->featureService->createFeature($validated);
            session()->flash('success', 'Feature created successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Feature creation failed!');
            throw $e;
        }

        return $this->redirectIndex();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $data = $this->featureService->getFeature($id);
        $data['creater_name'] = $this->creater_name($data);
        $data['updater_name'] = $this->updater_name($data);

        return response()->json($data);
    }

    public function status(string $id): RedirectResponse
    {
        $feature = $this->featureService->getFeature($id);
        $this->featureService->toggleStatus($feature);
        session()->flash('success', 'Feature status updated successfully!');

        return redirect()->back();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): View
    {
        $data['feature'] = $this->featureService->getFeature($id);

        return view('backend.admin.membership-management.feature.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FeatureRequest $request, string $id): RedirectResponse
    {
        try {
            $feature = $this->featureService->getFeature($id);
            $validated = $request->validated();
            $this->featureService->updateFeature($feature, $validated);
            session()->flash('success', 'Feature updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Feature update failed!');
            throw $e;
        }

        return $this->redirectIndex();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        try {
            $feature = $this->featureService->getFeature($id);
            $this->featureService->delete($feature);
            session()->flash('success', 'Feature deleted successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Feature delete failed!');
            throw $e;
        }

        return $this->redirectIndex();
    }

    public function trash(Request $request): mixed
    {
        if ($request->ajax()) {
            $query = $this->featureService->getFeatures()->onlyTrashed();

            return DataTables::eloquent($query)
                ->editColumn('status', fn($feature) => "<span class='badge badge-soft {$feature->status_color}'>{$feature->status_label}</span>")
                ->editColumn('deleted_by', fn($feature) => $this->deleter_name($feature))
                ->editColumn('deleted_at', fn($feature) => $feature->deleted_at_formatted)
                ->editColumn('action', fn($feature) => view('components.admin.action-buttons', [
                    'menuItems' => $this->trashedMenuItems($feature),
                ])->render())
                ->rawColumns(['status','deleted_by', 'deleted_at', 'action'])
                ->make(true);
        }

        return view('backend.admin.membership-management.feature.trash');
    }

    protected function trashedMenuItems($model): array
    {
        return [
            [
                'routeName' => 'mm.feature.restore',
                'params' => [encrypt($model->id)],
                'label' => 'Restore',
            ],
            [
                'routeName' => 'mm.feature.permanent-delete',
                'params' => [encrypt($model->id)],
                'label' => 'Permanent Delete',
                'p-delete' => true,
            ],
        ];
    }

    public function restore(string $id): RedirectResponse
    {
        try {
            $this->featureService->restore($id);
            session()->flash('success', 'Feature restored successfully');
        } catch (\Throwable $e) {
            session()->flash('error', 'Feature restore failed');
            throw $e;
        }

        return $this->redirectTrashed();
    }

    public function permanentDelete(string $id): RedirectResponse
    {
        try {
            $this->featureService->permanentDelete($id);
            session()->flash('success', 'Feature permanently deleted successfully');
        } catch (\Throwable $e) {
            session()->flash('error', 'Feature permanent delete failed');
            throw $e;
        }

        return $this->redirectTrashed();
    }
}