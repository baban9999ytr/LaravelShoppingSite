<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\FeatureValue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class FeatureController extends Controller
{
    
    public function index(): Response
    {
        return Inertia::render('Features/Index', [
            'features' => Feature::with('values')->latest()->get(),
        ]);
    }

   
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:features,slug',
            'values' => 'required|array|min:1',
            'values.*.value' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $slug = ! empty($validated['slug'])
                    ? Str::slug($validated['slug'])
                    : Str::slug($validated['name']);

                $feature = Feature::create([
                    'name' => $validated['name'],
                    'slug' => $slug,
                ]);

                $valuesToInsert = collect($validated['values'])
                    ->map(fn ($v) => trim($v['value'] ?? ''))
                    ->filter()
                    ->unique()
                    ->map(fn ($val) => ['value' => $val])
                    ->values()
                    ->all();

                if (! empty($valuesToInsert)) {
                    $feature->values()->createMany($valuesToInsert);
                }
            });

            return redirect()->back()->with('status', 'Özellik ve değerleri başarıyla oluşturuldu.');
        } catch (Throwable $e) {
            Log::error('Admin\FeatureController@store failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Özellik eklenirken bir hata oluştu.',
            ]);
        }
    }

    
    public function update(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:features,slug,'.$feature->id,
        ]);

        try {
            if (! empty($validated['slug']) && $feature->slug !== $validated['slug']) {
                $feature->slug = Str::slug($validated['slug']);
            }

            $feature->name = $validated['name'];
            $feature->save();

            return redirect()->back()->with('status', 'Özellik adı güncellendi.');
        } catch (Throwable $e) {
            Log::error('Admin\FeatureController@update failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Özellik güncellenirken hata oluştu.',
            ]);
        }
    }

    public function destroy(Feature $feature): RedirectResponse
    {
        try {
            DB::transaction(function () use ($feature) {
                $feature->values()->delete();
                $feature->delete();
            });

            return redirect()->back()->with('status', 'Özellik ve bağlı değerler silindi.');
        } catch (Throwable $e) {
            Log::error('Admin\FeatureController@destroy failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Özellik silinemedi.',
            ]);
        }
    }

  
    public function storeValue(Request $request, Feature $feature): RedirectResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
        ]);

        try {
            $feature->values()->create([
                'value' => trim($validated['value']),
            ]);

            return redirect()->back()->with('status', 'Özellik değeri eklendi.');
        } catch (Throwable $e) {
            Log::error('Admin\FeatureController@storeValue failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Değer eklenirken hata oluştu.',
            ]);
        }
    }

    

    public function destroyValue(FeatureValue $featureValue): RedirectResponse
    {
        try {
            $featureValue->delete();

            return redirect()->back()->with('status', 'Özellik değeri silindi.');
        } catch (Throwable $e) {
            Log::error('Admin\FeatureController@destroyValue failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Değer silinemedi.',
            ]);
        }
    }
}