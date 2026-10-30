<?php

namespace App\Http\Controllers;

use App\Support\Access;
use App\Support\Audit;
use App\Support\ContentRegistry;
use App\Support\SafeContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentController extends Controller
{
    private function module(Request $request, string $module): array
    {
        $config = ContentRegistry::get($module);
        abort_unless(Access::allows($request->user(), $config['ability']), 403);

        return $config;
    }

    public function index(Request $request, string $module)
    {
        $config = $this->module($request, $module);
        $query = $config['model']::query();
        $request->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|in:draft,published', 'sort' => 'nullable|in:title,created_at,sort_order', 'direction' => 'nullable|in:asc,desc', 'page' => 'nullable|integer|min:1']);
        $query->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->q.'%'));
        if ($request->filled('status') && ! in_array($module, ['navigation', 'tags'], true)) {
            $query->where('status', $request->status);
        }
        $allowed = in_array($module, ['tags'], true) ? ['title', 'created_at'] : ['title', 'created_at', 'sort_order'];
        $sort = in_array($request->sort, $allowed, true) ? $request->sort : 'created_at';

        return $query->orderBy($sort, $request->direction ?? 'desc')->paginate(12);
    }

    public function show(Request $request, string $module, int $id)
    {
        $config = $this->module($request, $module);
        $record = $config['model']::findOrFail($id);
        foreach ($config['relations'] as $relation) {
            $record->setAttribute($relation, $record->$relation()->pluck($record->$relation()->getRelated()->getTable().'.id'));
        }

        return $record;
    }

    public function save(Request $request, string $module, ?int $id = null)
    {
        $config = $this->module($request, $module);
        $record = $id ? $config['model']::findOrFail($id) : new $config['model'];
        $data = SafeContent::sanitize($request->validate(ContentRegistry::rules($module, $id)));
        if ($module === 'branches' && ! empty($data['hours'])) {
            foreach ($data['hours'] as $hours) {
                if (! ($hours['closed'] ?? false) && (! filled($hours['open'] ?? null) || ! filled($hours['close'] ?? null) || $hours['open'] >= $hours['close'])) {
                    throw ValidationException::withMessages(['hours' => 'Every open day needs an opening time before its closing time.']);
                }
            }if (count(array_unique(array_column($data['hours'], 'day'))) !== count($data['hours'])) {
                throw ValidationException::withMessages(['hours' => 'Enter each weekday only once.']);
            }
        }
        if ($module === 'gallery' && ($data['status'] ?? $record->status) === 'published' && in_array($data['kind'] ?? $record->kind, ['team', 'treatment', 'before_after'], true)) {
            if (! ($data['consent'] ?? false) || ! filled($data['consent_reference'] ?? null)) {
                throw ValidationException::withMessages(['consent' => 'Record verified publication consent and a private record reference first.']);
            }
            if (($data['kind'] ?? null) === 'before_after' && ! filled($data['after_image'] ?? null)) {
                throw ValidationException::withMessages(['after_image' => 'A before-and-after item needs both images.']);
            }
            $data['consent_recorded_at'] = now();
        }
        if ($module === 'testimonials' && ($data['status'] ?? null) === 'published' && (! ($data['consent'] ?? false) || ! ($data['approved'] ?? false))) {
            throw ValidationException::withMessages(['approved' => 'Published testimonials require verified consent and approval.']);
        }
        if ($module === 'articles' && ($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
            throw ValidationException::withMessages(['published_at' => 'Set a publication date before publishing.']);
        }
        DB::transaction(function () use ($record, $config, $data, $module) {
            $attributes = array_diff_key($data, array_flip($config['relations']));
            if (isset($attributes['sort_order']) && $attributes['sort_order'] === null) {
                $attributes['sort_order'] = 0;
            }
            $record->fill($attributes)->save();
            foreach ($config['relations'] as $relation) {
                $record->$relation()->sync(array_filter($data[$relation] ?? [], fn ($value) => $relation !== 'related' || $value !== $record->id));
            }
            Audit::record($record->wasRecentlyCreated ? 'created' : 'updated', $module, $record->id);
        });

        return response()->json(['record' => $record, 'message' => 'Changes saved.'], $id ? 200 : 201);
    }

    public function destroy(Request $request, string $module, int $id)
    {
        $config = $this->module($request, $module);
        $record = $config['model']::findOrFail($id);
        DB::transaction(function () use ($record, $module, $id) {
            $record->delete();
            Audit::record('deleted', $module, $id);
        });

        return response()->json(['message' => 'Item deleted.']);
    }
}
