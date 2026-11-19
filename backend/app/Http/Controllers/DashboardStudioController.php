<?php

namespace App\Http\Controllers;

use App\Services\DataGovInService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class DashboardStudioController extends Controller
{
    public function index(): View
    {
        $dashboards = DB::table('custom_dashboards')
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get();

        return view('modules.dashboard-studio.index', compact('dashboards'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', 'in:private,public'],
        ]);

        $visibility = self::normalizeVisibility($validated['visibility'] ?? 'private');

        $id = DB::table('custom_dashboards')->insertGetId([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'visibility' => $visibility,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('dashboard-studio.show', $id)
            ->with('success', 'Dashboard created successfully.');
    }

    public function update(Request $request, int $dashboard): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', 'in:private,public'],
        ]);

        $visibility = self::normalizeVisibility($validated['visibility'] ?? $dashboard->visibility ?? 'private');

        DB::table('custom_dashboards')
            ->where('id', $dashboard->id)
            ->update([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'visibility' => $visibility,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('dashboard-studio.show', $dashboard->id)
            ->with('success', 'Dashboard updated successfully.');
    }

    public function show(int $dashboard): View
    {
        $dashboard = $this->ownedDashboard($dashboard);
        $viewData = $this->dashboardViewData($dashboard);
        $viewData['readOnly'] = false;

        return view('modules.dashboard-studio.show', $viewData);
    }

    public function publicShow(int $dashboard): View
    {
        $dashboard = DB::table('custom_dashboards')
            ->where('id', $dashboard)
            ->where('visibility', 'public')
            ->first();

        abort_unless($dashboard, 404);

        $viewData = $this->dashboardViewData($dashboard);
        $viewData['readOnly'] = true;

        return view('modules.dashboard-studio.show', $viewData);
    }

    private function dashboardViewData(object $dashboard): array
    {
        $sources = DB::table('dashboard_data_sources')
            ->where('dashboard_id', $dashboard->id)
            ->orderByDesc('id')
            ->get();

        $widgets = DB::table('dashboard_widgets')
            ->where('dashboard_id', $dashboard->id)
            ->orderBy('position')
            ->orderByDesc('id')
            ->get();

        $sourceData = [];
        $service = app(DataGovInService::class);

        foreach ($sources as $source) {
            $snapshot = DB::table('dashboard_data_snapshots')
                ->where('data_source_id', $source->id)
                ->orderByDesc('fetched_at')
                ->first();

            $payload = $snapshot ? json_decode($snapshot->payload, true) : [];
            $records = is_array($payload) ? $service->records($payload) : [];
            $columns = $service->columns($records);

            $stateColumn = null;
            $valueColumn = null;

            if (!empty($records) && is_array($records[0])) {
                foreach (array_keys($records[0]) as $column) {
                    $normal = strtolower((string) $column);

                    if ($stateColumn === null &&
                        (str_contains($normal, 'name_of_the_state') ||
                         str_contains($normal, 'state') ||
                         str_contains($normal, 'ut'))) {
                        $stateColumn = $column;
                    }

                    if ($valueColumn === null &&
                        (str_contains($normal, 'pending_cases') ||
                         str_contains($normal, 'cases'))) {
                        $valueColumn = $column;
                    }
                }

                if ($stateColumn === null) {
                    foreach (array_keys($records[0]) as $column) {
                        if (!is_numeric($records[0][$column] ?? null)) {
                            $stateColumn = $column;
                            break;
                        }
                    }
                }

                if ($valueColumn === null) {
                    foreach (array_keys($records[0]) as $column) {
                        $normal = strtolower((string) $column);

                        if ($column !== $stateColumn &&
                            !str_contains($normal, 'sl__no') &&
                            !str_contains($normal, 'serial') &&
                            is_numeric(str_replace(',', '', (string) ($records[0][$column] ?? '')))) {
                            $valueColumn = $column;
                            break;
                        }
                    }
                }
            }

            $chartRows = [];
            $caseTotal = 0;
            $stateCount = 0;
            $nationalTotal = null;

            if ($stateColumn !== null && $valueColumn !== null) {
                foreach ($records as $record) {
                    if (!is_array($record)) {
                        continue;
                    }

                    $stateName = trim((string) ($record[$stateColumn] ?? ''));
                    $rawValue = str_replace(',', '', (string) ($record[$valueColumn] ?? ''));

                    if ($stateName === '' || !is_numeric($rawValue)) {
                        continue;
                    }

                    $value = (float) $rawValue;

                    if (strtolower($stateName) === 'total') {
                        $nationalTotal = $value;
                        continue;
                    }

                    $stateCount++;
                    $caseTotal += $value;

                    $chartRows[] = [
                        'state' => $stateName,
                        'value' => $value,
                    ];
                }
            }

            usort($chartRows, fn ($a, $b) => $b['value'] <=> $a['value']);

            $topStates = array_slice($chartRows, 0, 10);
            $maximum = !empty($topStates)
                ? max(array_column($topStates, 'value'))
                : 0;

            foreach ($topStates as &$item) {
                $item['bar_percent'] = $maximum > 0
                    ? round(($item['value'] / $maximum) * 100, 2)
                    : 0;
            }
            unset($item);

            $sourceData[$source->id] = [
                'snapshot' => $snapshot,
                'records' => $records,
                'columns' => $columns,
                'total' => $payload['total'] ?? count($records),
                'state_column' => $stateColumn,
                'value_column' => $valueColumn,
                'state_count' => $stateCount,
                'case_total' => $nationalTotal ?? $caseTotal,
                'top_states' => $topStates,
                'chart_rows' => $chartRows,
                'has_visual_data' => $stateColumn !== null && $valueColumn !== null,
            ];
        }

        $widgetData = [];

        foreach ($widgets as $widget) {
            $widget->configuration = is_string($widget->configuration)
                ? json_decode($widget->configuration, true)
                : $widget->configuration;

            if (!is_array($widget->configuration)) {
                $widget->configuration = [];
            }

            $source = $sourceData[$widget->data_source_id] ?? null;
            $records = $source['records'] ?? [];
            $stateColumn = $widget->configuration['state_column'] ?? $source['state_column'] ?? null;
            $valueColumn = $widget->configuration['value_column'] ?? $source['value_column'] ?? null;
            $limit = max(1, min((int) ($widget->configuration['limit'] ?? 10), 50));
            $sort = in_array(($widget->configuration['sort'] ?? 'desc'), ['asc', 'desc'], true)
                ? $widget->configuration['sort']
                : 'desc';

            $rows = [];
            $total = 0;

            if ($stateColumn !== null && $valueColumn !== null && is_array($records)) {
                foreach ($records as $record) {
                    if (!is_array($record)) {
                        continue;
                    }

                    $stateName = trim((string) ($record[$stateColumn] ?? ''));
                    $rawValue = str_replace(',', '', (string) ($record[$valueColumn] ?? ''));

                    if ($stateName === '' || !is_numeric($rawValue)) {
                        continue;
                    }

                    $value = (float) $rawValue;

                    if (strtolower($stateName) === 'total') {
                        $total = $value;
                        continue;
                    }

                    $rows[] = [
                        'state' => $stateName,
                        'value' => $value,
                    ];
                }
            }

            usort($rows, fn ($a, $b) => $sort === 'asc'
                ? $a['value'] <=> $b['value']
                : $b['value'] <=> $a['value']);

            $rows = array_slice($rows, 0, $limit);

            $maximum = !empty($rows) ? max(array_column($rows, 'value')) : 0;

            foreach ($rows as &$row) {
                $row['bar_percent'] = $maximum > 0
                    ? round(($row['value'] / $maximum) * 100, 2)
                    : 0;
            }
            unset($row);

            $widgetData[$widget->id] = [
                'rows' => $rows,
                'total' => $total ?: array_sum(array_column($rows, 'value')),
                'max_value' => $maximum,
                'state_column' => $stateColumn,
                'value_column' => $valueColumn,
            ];
        }

        return compact(
            'dashboard',
            'sources',
            'widgets',
            'widgetData',
            'sourceData'
        );
    }

    public function storeSource(Request $request, int $dashboard): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'resource_id' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $resourceId = trim((string) $value);

                    if (!DataGovInService::isValidResourceId($resourceId)) {
                        $fail('The resource ID must be a valid data.gov.in identifier.');
                    }
                },
            ],
            'dataset_title' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['resource_id'] = trim((string) $validated['resource_id']);

        DB::table('dashboard_data_sources')->insert([
            'dashboard_id' => $dashboard->id,
            'name' => $validated['name'],
            'provider' => 'data.gov.in',
            'resource_id' => $validated['resource_id'],
            'dataset_title' => $validated['dataset_title'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('dashboard-studio.show', $dashboard->id)
            ->with('success', 'Data source registered. Test the connection to retrieve its fields and records.');
    }

    public function storeWidget(Request $request, int $dashboard): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        $validated = $request->validate([
            'data_source_id' => ['required', 'integer', 'exists:dashboard_data_sources,id'],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:table,bar_chart,line_chart'],
            'state_column' => ['nullable', 'string', 'max:100'],
            'value_column' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:asc,desc'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[0-9A-Fa-f]{3,8}$/'],
        ]);

        $source = DB::table('dashboard_data_sources')
            ->where('dashboard_id', $dashboard->id)
            ->where('id', $validated['data_source_id'])
            ->first();

        abort_unless($source, 404);

        $position = (int) DB::table('dashboard_widgets')
            ->where('dashboard_id', $dashboard->id)
            ->max('position');

        DB::table('dashboard_widgets')->insert([
            'dashboard_id' => $dashboard->id,
            'data_source_id' => $source->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'configuration' => json_encode([
                'state_column' => $validated['state_column'] ?? null,
                'value_column' => $validated['value_column'] ?? null,
                'limit' => $validated['limit'] ?? 10,
                'sort' => $validated['sort'] ?? 'desc',
                'color' => $validated['color'] ?? '#2563eb',
            ], JSON_THROW_ON_ERROR),
            'position' => $position + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('dashboard-studio.show', $dashboard->id)
            ->with('success', 'Widget saved successfully.');
    }

    public function updateWidget(Request $request, int $dashboard, int $widget): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        $existing = DB::table('dashboard_widgets')
            ->where('dashboard_id', $dashboard->id)
            ->where('id', $widget)
            ->first();

        abort_unless($existing, 404);

        $validated = $request->validate([
            'data_source_id' => ['required', 'integer', 'exists:dashboard_data_sources,id'],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:table,bar_chart,line_chart'],
            'state_column' => ['nullable', 'string', 'max:100'],
            'value_column' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'sort' => ['nullable', 'in:asc,desc'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#?[0-9A-Fa-f]{3,8}$/'],
        ]);

        $source = DB::table('dashboard_data_sources')
            ->where('dashboard_id', $dashboard->id)
            ->where('id', $validated['data_source_id'])
            ->first();

        abort_unless($source, 404);

        DB::table('dashboard_widgets')
            ->where('id', $existing->id)
            ->update([
                'data_source_id' => $source->id,
                'title' => $validated['title'],
                'type' => $validated['type'],
                'configuration' => json_encode([
                    'state_column' => $validated['state_column'] ?? null,
                    'value_column' => $validated['value_column'] ?? null,
                    'limit' => $validated['limit'] ?? 10,
                    'sort' => $validated['sort'] ?? 'desc',
                    'color' => $validated['color'] ?? '#2563eb',
                ], JSON_THROW_ON_ERROR),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('dashboard-studio.show', $dashboard->id)
            ->with('success', 'Widget updated successfully.');
    }

    public function destroyWidget(int $dashboard, int $widget): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        $deleted = DB::table('dashboard_widgets')
            ->where('dashboard_id', $dashboard->id)
            ->where('id', $widget)
            ->delete();

        abort_unless($deleted, 404);

        return redirect()
            ->route('dashboard-studio.show', $dashboard->id)
            ->with('success', 'Widget deleted successfully.');
    }

    public function testSource(int $dashboard, int $source): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        $dataSource = DB::table('dashboard_data_sources')
            ->where('dashboard_id', $dashboard->id)
            ->where('id', $source)
            ->first();

        abort_unless($dataSource, 404);

        $startedAt = now();

        $logId = DB::table('dashboard_refresh_logs')->insertGetId([
            'data_source_id' => $dataSource->id,
            'status' => 'running',
            'message' => 'API request started.',
            'row_count' => 0,
            'started_at' => $startedAt,
            'created_at' => $startedAt,
            'updated_at' => $startedAt,
        ]);

        try {
            $service = app(DataGovInService::class);
            $payload = $service->fetch($dataSource->resource_id);
            $records = $service->records($payload);

            DB::table('dashboard_data_snapshots')->insert([
                'data_source_id' => $dataSource->id,
                'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
                'row_count' => count($records),
                'fetched_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('dashboard_data_sources')
                ->where('id', $dataSource->id)
                ->update([
                    'last_tested_at' => now(),
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

            DB::table('dashboard_refresh_logs')
                ->where('id', $logId)
                ->update([
                    'status' => 'success',
                    'message' => 'API request successful.',
                    'row_count' => count($records),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            return redirect()
                ->route('dashboard-studio.show', $dashboard->id)
                ->with('success', 'API connection successful. Retrieved ' . count($records) . ' records.');
        } catch (Throwable $exception) {
            Log::warning('Dashboard Studio data.gov.in request failed', [
                'source_id' => $dataSource->id,
                'exception_class' => get_class($exception),
            ]);

            $safeMessage = 'The API request failed. Check the server configuration and application log.';

            DB::table('dashboard_data_sources')
                ->where('id', $dataSource->id)
                ->update([
                    'last_error' => $safeMessage,
                    'updated_at' => now(),
                ]);

            DB::table('dashboard_refresh_logs')
                ->where('id', $logId)
                ->update([
                    'status' => 'failed',
                    'message' => $safeMessage,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            return redirect()
                ->route('dashboard-studio.show', $dashboard->id)
                ->with('error', $safeMessage);
        }
    }

    public function destroySource(int $dashboard, int $source): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        DB::table('dashboard_data_sources')
            ->where('dashboard_id', $dashboard->id)
            ->where('id', $source)
            ->delete();

        return redirect()
            ->route('dashboard-studio.show', $dashboard->id)
            ->with('success', 'Data source and its saved snapshots have been deleted.');
    }

    public function destroy(int $dashboard): RedirectResponse
    {
        $dashboard = $this->ownedDashboard($dashboard);

        DB::table('custom_dashboards')
            ->where('id', $dashboard->id)
            ->delete();

        return redirect()
            ->route('dashboard-studio.index')
            ->with('success', 'Dashboard deleted.');
    }

    public static function normalizeVisibility(mixed $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['private', 'public'], true)
            ? $normalized
            : 'private';
    }

    private function ownedDashboard(int $id): object
    {
        $dashboard = DB::table('custom_dashboards')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        abort_unless($dashboard, 404);

        return $dashboard;
    }
}