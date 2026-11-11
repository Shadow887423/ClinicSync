<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MonitoringVitalsController extends Controller
{
    /**
     * Ingest genuine browser Web Vitals & page loading performance metrics.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|string|max:500',
            'fcp' => 'nullable|numeric|min:0|max:120000',
            'lcp' => 'nullable|numeric|min:0|max:120000',
            'inp' => 'nullable|numeric|min:0|max:60000',
            'cls' => 'nullable|numeric|min:0|max:50',
            'ttfb' => 'nullable|numeric|min:0|max:60000',
            'dom_loaded' => 'nullable|numeric|min:0|max:120000',
            'fully_loaded' => 'nullable|numeric|min:0|max:120000',
            'response_time_ms' => 'nullable|numeric|min:0|max:120000',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid vitals payload', 'messages' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Determine performance classification based on fully loaded or LCP or FCP
        $primaryMetric = $validated['fully_loaded'] ?? $validated['lcp'] ?? $validated['fcp'] ?? $validated['response_time_ms'] ?? 0;

        if ($primaryMetric < 1000) {
            $classification = 'excellent';
        } elseif ($primaryMetric <= 2000) {
            $classification = 'good';
        } elseif ($primaryMetric <= 3000) {
            $classification = 'moderate';
        } elseif ($primaryMetric <= 5000) {
            $classification = 'slow';
        } else {
            $classification = 'critical';
        }

        PerformanceLog::create([
            'url' => '/' . ltrim(parse_url($validated['url'], PHP_URL_PATH) ?: '/', '/'),
            'fcp' => $validated['fcp'] ?? null,
            'lcp' => $validated['lcp'] ?? null,
            'inp' => $validated['inp'] ?? null,
            'cls' => $validated['cls'] ?? null,
            'ttfb' => $validated['ttfb'] ?? null,
            'dom_loaded' => $validated['dom_loaded'] ?? null,
            'fully_loaded' => $validated['fully_loaded'] ?? null,
            'response_time_ms' => $validated['response_time_ms'] ?? null,
            'classification' => $classification,
            'created_at' => now(),
        ]);

        return response()->json(['success' => true, 'classification' => $classification], 201);
    }
}
