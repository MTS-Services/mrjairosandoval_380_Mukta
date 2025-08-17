<?php

namespace App\Http\Controllers\Backend\Admin\ArticleManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleManagement\ArticleCategoryRequest;
use App\Http\Traits\AuditRelationTraits;
use App\Services\Admin\ArticleManagement\ArticleCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ArticleCategoryController extends Controller
{
    use AuditRelationTraits;

    protected ArticleCategoryService $articleCategoryService;

    public function __construct(ArticleCategoryService $articleCategoryService)
    {
        $this->articleCategoryService = $articleCategoryService;
    }

    protected function redirectIndex(): RedirectResponse
    {
        return redirect()->route('arm.article-category.index');
    }

    protected function redirectTrashed(): RedirectResponse
    {
        return redirect()->route('arm.article-category.trash');
    }

    public static function middleware(): array
    {
        return [
            'auth:admin',
            // Add permission middlewares if needed
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->articleCategoryService->getCategories();
            return DataTables::eloquent($query)
                ->editColumn('status', fn($category) => "<span class='badge badge-soft {$category->status_color}'>{$category->status_label}</span>")
                ->editColumn('created_by', fn($category) => $this->creater_name($category))
                ->editColumn('created_at', fn($category) => $category->created_at)
                ->editColumn('action', fn($category) => view('components.admin.action-buttons', [
                    'menuItems' => $this->menuItems($category),
                ])->render())
                ->rawColumns(['status', 'action', 'created_by', 'created_at'])
                ->make(true);
        }

        return view('backend.admin.article-management.category.index');
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
                'routeName' => 'arm.article-category.edit',
                'params' => [encrypt($model->id)],
                'label' => 'Edit',
            ],
            [
                'routeName' => 'arm.article-category.status',
                'params' => [encrypt($model->id)],
                'label' => $model->status ? 'Inactive' : 'Activate',
            ],
            [
                'routeName' => 'arm.article-category.destroy',
                'params' => [encrypt($model->id)],
                'label' => 'Delete',
                'delete' => true,
            ],
        ];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
        return view('backend.admin.article-management.category.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArticleCategoryRequest $request)
    {
        try {
            $validated = $request->validated();
            $this->articleCategoryService->createCategory($validated);
            session()->flash('success', 'Category created successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Category creation failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = $this->articleCategoryService->getCategory($id);
        $category['status'] = $category->status ? 'Active' : 'Inactive';
        $category['creater_name'] = $this->creater_name($category);
        $category['updater_name'] = $this->updater_name($category);

        return response()->json($category);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data['articleCategory'] = $this->articleCategoryService->getCategory($id);
        return view('backend.admin.article-management.category.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArticleCategoryRequest $request, string $id)
    {
     
        try {
            $category = $this->articleCategoryService->getCategory($id);
            $validated = $request->validated();
            $this->articleCategoryService->updateCategory($category, $validated);
            session()->flash('success', 'Category updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Category update failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    /**
     * Toggle status (active/inactive).
     */
    public function status(string $id)
    {
        $category = $this->articleCategoryService->getCategory($id);
        $this->articleCategoryService->toggleStatus($category);
        session()->flash('success', 'Category status updated successfully!');
        return $this->redirectIndex();
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $category = $this->articleCategoryService->getCategory($id);
        $this->articleCategoryService->delete($category);
        session()->flash('success', 'Category deleted successfully!');
        return $this->redirectIndex();
    }

    /**
     * Display trashed categories.
     */
    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->articleCategoryService->getCategories()->onlyTrashed();
            return DataTables::eloquent($query)
                ->editColumn('status', fn($category) => "<span class='badge badge-soft {$category->status_color}'>{$category->status_label}</span>")
                ->editColumn('deleted_by', fn($category) => $this->deleter_name($category))
                ->editColumn('deleted_at', fn($category) => $category->deleted_at_formatted)
                ->editColumn('action', fn($category) => view('components.admin.action-buttons', [
                    'menuItems' => $this->menuItemsTrashed($category),
                ])->render())
                ->rawColumns(['status', 'deleted_by', 'deleted_at', 'action'])
                ->make(true);
        }

        return view('backend.admin.article-management.category.trash');
    }

    protected function menuItemsTrashed($model): array
    {
        return [
            [
                'routeName' => 'arm.article-category.restore',
                'params' => [encrypt($model->id)],
                'label' => 'Restore',
            ],
            [
                'routeName' => 'arm.article-category.permanent-delete',
                'params' => [encrypt($model->id)],
                'label' => 'Permanent Delete',
                'p-delete' => true,
            ],
        ];
    }

    /**
     * Restore a soft-deleted category.
     */
    public function restore(string $id): RedirectResponse
    {
        $this->articleCategoryService->restore($id);
        session()->flash('success', 'Category restored successfully!');
        return $this->redirectTrashed();
    }

    /**
     * Permanently delete a category.
     */
    public function permanentDelete(string $id): RedirectResponse
    {
        $this->articleCategoryService->deletePermanent($id);
        session()->flash('success', 'Category permanently deleted successfully!');
        return $this->redirectTrashed();
    }
}
