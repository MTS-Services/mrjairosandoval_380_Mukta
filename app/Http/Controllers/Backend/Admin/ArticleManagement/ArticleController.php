<?php

namespace App\Http\Controllers\Backend\Admin\ArticleManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleManagement\ArticleRequest;
use App\Http\Traits\AuditRelationTraits;
use App\Models\ArticleCategory;
use App\Models\Articles;
use GuzzleHttp\Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use App\Services\Admin\ArticleManagement\ArticleService;

class ArticleController extends Controller
{
    use AuditRelationTraits;
    protected ArticleService $article;

    public function __construct(ArticleService $article)
    {
        $this->article = $article;
    }
    protected function redirectIndex(): RedirectResponse
    {
        return redirect()->route('arm.article.index');
    }

    protected function redirectTrashed(): RedirectResponse
    {
        return redirect()->route('arm.article.trash');
    }


    public static function middleware(): array
    {
        return [
            'auth:admin', // Applies 'auth:admin' to all methods

            // Permission middlewares using the Middleware class
            new Middleware('permission:article-list', only: ['index']),
            new Middleware('permission:article-details', only: ['show']),
            new Middleware('permission:article-create', only: ['create', 'store']),
            new Middleware('permission:article-edit', only: ['edit', 'update']),
            new Middleware('permission:article-delete', only: ['destroy']),
            new Middleware('permission:article-trash', only: ['trash']),
            new Middleware('permission:article-restore', only: ['restore']),
            new Middleware('permission:article-permanent-delete', only: ['permanentDelete']),
            //add more permissions if needed
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->article->getArticles();
            return DataTables::eloquent($query)
                ->editColumn('category_id', fn($article) => $article->articleCategory->name)
                ->editColumn('status', fn($article) => "<span class='badge badge-soft {$article->status_color}'>{$article->status_label}</span>")

                ->editColumn('created_by', function ($article) {
                    return $this->creater_name($article);
                })
                ->editColumn('created_at', function ($article) {
                    return $article->created_at;
                })
                ->editColumn('action', function ($article) {
                    $menuItems = $this->menuItems($article);
                    return view('components.admin.action-buttons', compact('menuItems'))->render();
                })
                ->rawColumns(['status', 'category_id', 'action', 'created_by', 'created_at',])
                ->make(true);
        }
        return view('backend.admin.article-management.articles.index');
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
                'routeName' => 'arm.article.edit',
                'params' => [encrypt($model->id)],
                'label' => 'Edit',
                'permissions' => ['permission-edit']
            ],
            [
                'routeName' => 'arm.article.status',
                'params' => [encrypt($model->id)],
                'label' => $model->status ? 'Inactive' : 'Activate',
                'status' => true,
                'permissions' => ['permission-status']
            ],

            [
                'routeName' => 'arm.article.destroy',
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
        $data['articleCategories'] = ArticleCategory::active()->get();
        return view('backend.admin.article-management.articles.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ArticleRequest $request)
    {

        try {
            $validated = $request->validated();
            $file = $request->validated('image') && $request->hasFile('image') ? $request->file('image') : null;
            $this->article->createArticle($validated, $file);
            session()->flash('success', 'Article created successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Article create failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = $this->article->getArticle($id);
        $data['status'] = $data->status ? 'Active' : 'Inactive';
        $data['category_name'] = $data->articleCategory->name ?? 'N/A';
        $data['creater_name'] = $this->creater_name($data);
        $data['updater_name'] = $this->updater_name($data);

        return response()->json($data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data['articleCategories'] = ArticleCategory::active()->get();
        $data['article'] = $this->article->getArticle($id);
        return view('backend.admin.article-management.articles.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ArticleRequest $request, string $id)
    {
        try {
            $article = $this->article->getArticle($id);
            $validated = $request->validated();
            $file = $request->validated('image') && $request->hasFile('image') ? $request->file('image') : null;
            $this->article->updateArticle($article, $validated, $file);
            session()->flash('success', 'Article updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Article update failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    public function status(string $id)
    {
        try {
            $article = $this->article->getArticle($id);
            $this->article->toggleStatus($article);
            session()->flash('success', 'Article status updated successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Article status update failed!');
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
            $article = $this->article->getArticle($id);
            $this->article->delete($article);
            session()->flash('success', 'Article deleted successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Article delete failed!');
            throw $e;
        }
        return $this->redirectIndex();
    }

    public function trash(Request $request)
    {
        if ($request->ajax()) {
            $query = $this->article->getArticles()->onlyTrashed();
            return DataTables::eloquent($query)
                ->editColumn('category_id', fn($article) => $article->articleCategory->name)
                ->editColumn('status', fn($article) => "<span class='badge badge-soft {$article->status_color}'>{$article->status_label}</span>")
                ->editColumn('deleted_by', function ($article) {
                    return $this->creater_name($article);
                })
                ->editColumn('created_at', fn($article) => $article->created_at_formatted)
                ->editColumn('action', fn($article) => view('components.admin.action-buttons', [
                    'menuItems' => $this->menuItemsTrashed($article),
                ])->render())
                ->rawColumns(['status','category_id', 'action', 'deleted_by', 'created_at',])
                ->make(true);
        }
        return view('backend.admin.article-management.articles.trash');
    }



    protected function menuItemsTrashed($model): array
    {
        return [
            [
                'routeName' => 'arm.article.restore',
                'params' => [encrypt($model->id)],
                'label' => 'Restore',
            ],
            [
                'routeName' => 'arm.article.permanent-delete',
                'params' => [encrypt($model->id)],
                'label' => 'Permanent Delete',
                'p-delete' => true,
            ]


        ];
    }


    public function restore(string $id): RedirectResponse
    {
        try {
            $article = Articles::onlyTrashed()->findOrFail(decrypt($id));

            $this->article->restore($article, $id);
            session()->flash('success', "Article restored successfully");
        } catch (\Throwable $e) {
            session()->flash('Article restore failed');
            throw $e;
        }
        return $this->redirectTrashed();
    }


    public function permanentDelete(string $encryptedId): RedirectResponse
    {
        try {
            $id = decrypt($encryptedId);
            $article = Articles::onlyTrashed()->findOrFail($id);

            $this->article->deletePermanent($article, $id);
            $article->forceDelete();

            session()->flash('success', 'Article permanently deleted successfully!');
        } catch (\Throwable $e) {
            session()->flash('error', 'Article permanent delete failed');
            throw $e;
        }
        return $this->redirectTrashed();
    }
}
