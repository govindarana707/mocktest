<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExaminationCategoryRequest;
use App\Http\Requests\Admin\UpdateExaminationCategoryRequest;
use App\Models\ExaminationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExaminationCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ExaminationCategory::query()
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->withCount('examinations')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.examination-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.examination-categories.form', ['category' => new ExaminationCategory]);
    }

    public function store(StoreExaminationCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        ExaminationCategory::create($data);

        return redirect()->route('admin.examination-categories.index')->with('success', 'Exam category created successfully.');
    }

    public function edit(ExaminationCategory $examinationCategory): View
    {
        return view('admin.examination-categories.form', ['category' => $examinationCategory]);
    }

    public function update(UpdateExaminationCategoryRequest $request, ExaminationCategory $examinationCategory): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name'], $examinationCategory);

        $examinationCategory->update($data);

        return redirect()->route('admin.examination-categories.index')->with('success', 'Exam category updated successfully.');
    }

    public function destroy(ExaminationCategory $examinationCategory): RedirectResponse
    {
        $examinationCategory->delete();

        return back()->with('success', 'Exam category deleted. Affected examinations are now uncategorized.');
    }

    private function uniqueSlug(string $name, ?ExaminationCategory $category = null): string
    {
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $suffix = 2;

        while (ExaminationCategory::query()
            ->where('slug', $slug)
            ->when($category, fn ($query) => $query->whereKeyNot($category->id))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
