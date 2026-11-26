@extends('layouts.app')

@section('title', $dashboard->title)
@section('page-heading', $dashboard->title)

@section('content')
<style>
    .ds-wrap{max-width:1450px;margin:25px auto;padding:0 20px;color:var(--vn-text,#1f2937)}
    .ds-muted{color:#64748b}
    .ds-card{background:var(--vn-card,#fff);border:1px solid var(--vn-border,#e5e7eb);border-radius:12px;padding:20px;margin:18px 0}
    .ds-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}
    .ds-stat{background:var(--vn-card,#fff);border:1px solid var(--vn-border,#e5e7eb);border-radius:12px;padding:18px}
    .ds-stat strong{display:block;font-size:clamp(24px,3vw,34px);margin-top:8px;overflow-wrap:anywhere}
    .ds-button{display:inline-block;padding:9px 14px;border:0;border-radius:7px;background:#2563eb;color:white;text-decoration:none;cursor:pointer;font-size:14px}
    .ds-button-danger{background:#b91c1c}
    .ds-input{width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:7px;background:var(--vn-card,#fff);color:inherit}
    .ds-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;align-items:end}
    .ds-table-wrap{overflow-x:auto}
    .ds-table{width:100%;border-collapse:collapse;min-width:620px}
    .ds-table th,.ds-table td{padding:11px 12px;border-bottom:1px solid var(--vn-border,#e5e7eb);text-align:left}
    .ds-table th{font-size:13px;color:#64748b;background:var(--vn-soft,#f8fafc)}
    .ds-number{text-align:right!important;font-variant-numeric:tabular-nums}
    .ds-bar-row{display:grid;grid-template-columns:minmax(120px,190px) 1fr minmax(90px,125px);gap:12px;align-items:center;margin:13px 0}
    .ds-bar-track{height:13px;background:#e2e8f0;border-radius:20px;overflow:hidden}
    .ds-bar-fill{height:100%;background:#2563eb;border-radius:20px}
    .ds-alert{padding:13px 15px;border-radius:8px;margin:12px 0;background:#fff7ed;color:#9a3412;border:1px solid #fed7aa}
    .ds-success{padding:12px 15px;border-radius:8px;background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;margin:12px 0}
    .ds-error{padding:12px 15px;border-radius:8px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;margin:12px 0}
    .ds-actions{display:flex;gap:9px;flex-wrap:wrap;align-items:center}
    @media(max-width:650px){.ds-wrap{padding:0 12px}.ds-card{padding:14px}.ds-bar-row{grid-template-columns:1fr 90px;gap:6px}.ds-bar-track{grid-column:1/-1;grid-row:2}.ds-bar-row .ds-bar-value{grid-column:2;grid-row:1;text-align:right}}
</style>

<div class="ds-wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap">
        <div>
            <a href="{{ route('dashboard-studio.index') }}" class="ds-muted" style="text-decoration:none">← Dashboard Studio</a>
            <h1 style="font-size:27px;font-weight:700;margin:10px 0 4px">{{ $dashboard->title }}</h1>
            <div class="ds-muted">{{ $dashboard->description ?: 'Dashboard summary' }}</div>
        </div>
        <span style="font-size:12px;padding:7px 12px;border-radius:999px;background:{{ $dashboard->visibility === 'public' ? '#dcfce7' : '#e2e8f0' }};color:{{ $dashboard->visibility === 'public' ? '#166534' : '#475569' }};">
            {{ ucfirst($dashboard->visibility ?? 'private') }} dashboard
        </span>
    </div>

    @if(($readOnly ?? false))
        <div class="ds-alert" style="margin-top:15px;">Public preview mode — this dashboard is visible without sign-in, but management controls are hidden.</div>
    @elseif($dashboard->visibility === 'public')
        @php($publicDashboardUrl = route('dashboard-studio.public-show', $dashboard->id))
        <div class="ds-alert" style="margin-top:15px;display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
            <span>Anyone with this link can see the dashboard:</span>
            <a href="{{ $publicDashboardUrl }}" style="font-weight:600;word-break:break-all;">{{ $publicDashboardUrl }}</a>
            <button type="button" data-copy="{{ $publicDashboardUrl }}" class="ds-button">Copy link</button>
        </div>
    @endif

    @if(session('success'))
        <div class="ds-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="ds-error">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="ds-error">
            <strong>Please correct the following:</strong>
            <ul style="margin:7px 0 0 20px">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @if(!($readOnly ?? false))
        <div class="ds-card">
            <h2 style="font-size:19px;font-weight:700;margin:0 0 15px">Dashboard details</h2>
            <form method="POST" action="{{ route('dashboard-studio.update', $dashboard->id) }}">
                @csrf
                @method('PATCH')
                <div class="ds-form-grid">
                    <div>
                        <label for="dashboard-title">Title</label>
                        <input class="ds-input" id="dashboard-title" name="title" value="{{ old('title', $dashboard->title) }}" required maxlength="150">
                    </div>
                    <div>
                        <label for="dashboard-visibility">Visibility</label>
                        <select class="ds-input" id="dashboard-visibility" name="visibility">
                            <option value="private" {{ old('visibility', $dashboard->visibility ?? 'private') === 'private' ? 'selected' : '' }}>Private</option>
                            <option value="public" {{ old('visibility', $dashboard->visibility ?? 'private') === 'public' ? 'selected' : '' }}>Public</option>
                        </select>
                    </div>
                    <div>
                        <label for="dashboard-description">Description</label>
                        <textarea class="ds-input" id="dashboard-description" name="description" rows="3" maxlength="2000" placeholder="Optional dashboard description">{{ old('description', $dashboard->description) }}</textarea>
                    </div>
                    <div>
                        <button class="ds-button" type="submit">Save dashboard</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="ds-card">
            <h2 style="font-size:19px;font-weight:700;margin:0 0 15px">Register a data.gov.in resource</h2>
        <form method="POST" action="{{ route('dashboard-studio.sources.store', $dashboard->id) }}">
            @csrf
            <div class="ds-form-grid">
                <div>
                    <label for="ds-name">Data source name</label>
                    <input class="ds-input" id="ds-name" name="name" required maxlength="150" placeholder="e.g. District Court Pending Cases (2015)">
                </div>
                <div>
                    <label for="ds-resource">Resource ID</label>
                    <input class="ds-input" id="ds-resource" name="resource_id" required maxlength="50" placeholder="data.gov.in resource UUID">
                </div>
                <div>
                    <label for="ds-title">Dataset title / description</label>
                    <input class="ds-input" id="ds-title" name="dataset_title" maxlength="255" placeholder="Dataset title">
                </div>
                <div>
                    <button class="ds-button" type="submit">Add data source</button>
                </div>
            </div>
        </form>
        <p class="ds-muted" style="font-size:13px;margin:13px 0 0">The API key is configured on the server and is not entered or stored in this form.</p>
    </div>

    <h2 style="font-size:21px;font-weight:700;margin:24px 0 10px">Saved widgets</h2>

    @if($widgets->isEmpty())
        <div class="ds-card ds-muted">No widgets saved yet. Add a widget from a registered data source below.</div>
    @else
        <div class="ds-grid" style="margin-bottom:18px">
            @foreach($widgets as $widget)
                @php($config = $widget->configuration ?? [])
                @php($widgetStats = $widgetData[$widget->id] ?? [])
                @php($widgetColor = !empty($config['color']) ? $config['color'] : '#2563eb')
                @php($widgetColor = ltrim($widgetColor, '#'))
                <div class="ds-card" style="margin:0">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                        <div>
                            <div style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#64748b">{{ ucfirst(str_replace('_', ' ', $widget->type)) }}</div>
                            <h3 style="margin:8px 0 6px;font-size:17px">{{ $widget->title }}</h3>
                            <div class="ds-muted" style="font-size:13px">
                                {{ $sources->firstWhere('id', $widget->data_source_id)?->name ?? 'Unknown source' }}
                            </div>
                        </div>
                        @if(!($readOnly ?? false))
                            <div class="ds-actions" style="margin-left:auto;flex-direction:column;align-items:flex-end;gap:6px">
                                <button type="button" class="ds-button" style="padding:7px 10px;font-size:12px" data-toggle-display="widget-edit-{{ $widget->id }}">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('dashboard-studio.widgets.destroy', [$dashboard->id, $widget->id]) }}" data-confirm="Delete this widget?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ds-button ds-button-danger" style="padding:7px 10px;font-size:12px">Delete</button>
                                </form>
                            </div>
                        @endif
                    </div>

                    @if(!($readOnly ?? false))
                        <div id="widget-edit-{{ $widget->id }}" style="display:none;margin-top:14px;padding-top:14px;border-top:1px solid var(--vn-border,#e5e7eb)">
                            <form method="POST" action="{{ route('dashboard-studio.widgets.update', [$dashboard->id, $widget->id]) }}">
                                @csrf
                                @method('PATCH')
                                <div class="ds-form-grid">
                                    <div>
                                        <label for="widget-source-{{ $widget->id }}">Data source</label>
                                        <select class="ds-input" id="widget-source-{{ $widget->id }}" name="data_source_id" required>
                                            @foreach($sources as $source)
                                                <option value="{{ $source->id }}" {{ $source->id == $widget->data_source_id ? 'selected' : '' }}>{{ $source->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="widget-title-{{ $widget->id }}">Widget title</label>
                                        <input class="ds-input" id="widget-title-{{ $widget->id }}" name="title" value="{{ old('title', $widget->title) }}" required maxlength="150">
                                    </div>
                                    <div>
                                        <label for="widget-type-{{ $widget->id }}">Widget type</label>
                                        <select class="ds-input" id="widget-type-{{ $widget->id }}" name="type" required>
                                            <option value="bar_chart" {{ $widget->type === 'bar_chart' ? 'selected' : '' }}>Bar chart</option>
                                            <option value="line_chart" {{ $widget->type === 'line_chart' ? 'selected' : '' }}>Line chart</option>
                                            <option value="table" {{ $widget->type === 'table' ? 'selected' : '' }}>Data table</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="widget-state-column-{{ $widget->id }}">State column</label>
                                        <input class="ds-input" id="widget-state-column-{{ $widget->id }}" name="state_column" value="{{ old('state_column', $widget->configuration['state_column'] ?? '') }}" maxlength="100" placeholder="Optional">
                                    </div>
                                    <div>
                                        <label for="widget-value-column-{{ $widget->id }}">Value column</label>
                                        <input class="ds-input" id="widget-value-column-{{ $widget->id }}" name="value_column" value="{{ old('value_column', $widget->configuration['value_column'] ?? '') }}" maxlength="100" placeholder="Optional">
                                    </div>
                                    <div>
                                        <label for="widget-limit-{{ $widget->id }}">Max rows</label>
                                        <input class="ds-input" id="widget-limit-{{ $widget->id }}" name="limit" type="number" min="1" max="50" value="{{ old('limit', $widget->configuration['limit'] ?? 10) }}">
                                    </div>
                                    <div>
                                        <label for="widget-sort-{{ $widget->id }}">Sort</label>
                                        <select class="ds-input" id="widget-sort-{{ $widget->id }}" name="sort">
                                            <option value="desc" {{ (old('sort', $widget->configuration['sort'] ?? 'desc') === 'desc') ? 'selected' : '' }}>Highest first</option>
                                            <option value="asc" {{ (old('sort', $widget->configuration['sort'] ?? 'desc') === 'asc') ? 'selected' : '' }}>Lowest first</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="widget-color-{{ $widget->id }}">Accent color</label>
                                        <input class="ds-input" id="widget-color-{{ $widget->id }}" name="color" value="{{ old('color', $widget->configuration['color'] ?? '#2563eb') }}" maxlength="20" placeholder="#2563eb">
                                    </div>
                                    <div>
                                        <button type="submit" class="ds-button">Save changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if(!empty($widgetStats['rows']))
                        @if($widget->type === 'table')
                            <div style="margin-top:12px;overflow-x:auto">
                                <table class="ds-table" style="min-width:260px">
                                    <thead>
                                        <tr>
                                            <th>State / UT</th>
                                            <th class="ds-number">Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($widgetStats['rows'] as $row)
                                            <tr>
                                                <td>{{ $row['state'] }}</td>
                                                <td class="ds-number">{{ number_format($row['value']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif($widget->type === 'line_chart')
                            <div style="margin-top:14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px 12px 8px">
                                <svg viewBox="0 0 420 120" width="100%" height="120" preserveAspectRatio="none" aria-label="{{ $widget->title }} line chart">
                                    @php($linePoints = [])
                                    @foreach($widgetStats['rows'] as $index => $row)
                                        @php($x = 20 + ($index * 60))
                                        @php($y = 90 - (($row['value'] / max(1, $widgetStats['max_value'])) * 60))
                                        @php($linePoints[] = $x . ',' . $y)
                                    @endforeach
                                    <polyline fill="none" stroke="{{ $widgetColor }}" stroke-width="3" points="{{ implode(' ', $linePoints) }}" />
                                    @foreach($widgetStats['rows'] as $index => $row)
                                        @php($x = 20 + ($index * 60))
                                        @php($y = 90 - (($row['value'] / max(1, $widgetStats['max_value'])) * 60))
                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="{{ $widgetColor }}" />
                                    @endforeach
                                </svg>
                            </div>
                        @else
                            <div style="margin-top:14px">
                                @foreach($widgetStats['rows'] as $index => $row)
                                    <div class="ds-bar-row" style="margin:11px 0">
                                        <div style="font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row['state'] }}</div>
                                        <div class="ds-bar-track"><div class="ds-bar-fill" style="width:{{ $row['bar_percent'] }}%;background:{{ $widgetColor }}"></div></div>
                                        <div class="ds-bar-value" style="font-size:12px">{{ number_format($row['value']) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="ds-muted" style="font-size:12px;margin-top:12px">No chartable data is available for this widget.</div>
                    @endif

                    @if(!empty($config['state_column']) || !empty($config['value_column']))
                        <div class="ds-muted" style="font-size:12px;margin-top:8px">
                            State column: {{ $config['state_column'] ?? '—' }}<br>
                            Value column: {{ $config['value_column'] ?? '—' }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @endif

    @if(!($readOnly ?? false))
        <div class="ds-card">
            <h2 style="font-size:19px;font-weight:700;margin:0 0 15px">Create a dashboard widget</h2>
            <form method="POST" action="{{ route('dashboard-studio.widgets.store', $dashboard->id) }}">
                @csrf
                <div class="ds-form-grid">
                    <div>
                        <label for="widget-source">Data source</label>
                        <select class="ds-input" id="widget-source" name="data_source_id" required>
                            <option value="">Select a source</option>
                            @foreach($sources as $source)
                                <option value="{{ $source->id }}">{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="widget-title">Widget title</label>
                        <input class="ds-input" id="widget-title" name="title" required maxlength="150" placeholder="e.g. Pending cases by state">
                    </div>
                    <div>
                        <label for="widget-type">Widget type</label>
                        <select class="ds-input" id="widget-type" name="type" required>
                            <option value="bar_chart">Bar chart</option>
                            <option value="line_chart">Line chart</option>
                            <option value="table">Data table</option>
                        </select>
                    </div>
                    <div>
                        <label for="widget-state-column">State column (optional)</label>
                        <input class="ds-input" id="widget-state-column" name="state_column" maxlength="100" placeholder="e.g. state_name">
                    </div>
                    <div>
                        <label for="widget-value-column">Value column (optional)</label>
                        <input class="ds-input" id="widget-value-column" name="value_column" maxlength="100" placeholder="e.g. pending_cases">
                    </div>
                    <div>
                        <label for="widget-limit">Max rows</label>
                        <input class="ds-input" id="widget-limit" name="limit" type="number" min="1" max="50" value="10">
                    </div>
                    <div>
                        <label for="widget-sort">Sort</label>
                        <select class="ds-input" id="widget-sort" name="sort">
                            <option value="desc">Highest first</option>
                            <option value="asc">Lowest first</option>
                        </select>
                    </div>
                    <div>
                        <label for="widget-color">Accent color</label>
                        <input class="ds-input" id="widget-color" name="color" value="#2563eb" maxlength="20" placeholder="#2563eb">
                    </div>
                    <div>
                        <button class="ds-button" type="submit">Save widget</button>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <h2 style="font-size:21px;font-weight:700;margin:24px 0 10px">Registered data sources</h2>

    @forelse($sources as $source)
        @php($data = $sourceData[$source->id] ?? [])
        <section class="ds-card">
            <div style="display:flex;justify-content:space-between;gap:15px;flex-wrap:wrap">
                <div>
                    <h3 style="font-size:18px;font-weight:700;margin:0 0 5px">{{ $source->name }}</h3>
                    <div class="ds-muted">{{ $source->dataset_title }}</div>
                    <div class="ds-muted" style="font-size:13px;margin-top:7px">Provider: {{ $source->provider }} · Resource: {{ $source->resource_id }}</div>
                    <div class="ds-muted" style="font-size:13px;margin-top:5px">
                        Last successful refresh:
                        {{ $data['snapshot']->fetched_at ?? 'Not refreshed yet' }}
                    </div>
                </div>
                @if(!($readOnly ?? false))
                    <div class="ds-actions">
                        <form method="POST" action="{{ route('dashboard-studio.sources.test', [$dashboard->id, $source->id]) }}">
                            @csrf
                            <button class="ds-button" type="submit">Test / Refresh API</button>
                        </form>
                        <form method="POST" action="{{ route('dashboard-studio.sources.destroy', [$dashboard->id, $source->id]) }}" data-confirm="Delete this data source and its saved snapshots?">
                            @csrf
                            @method('DELETE')
                            <button class="ds-button ds-button-danger" type="submit">Delete source</button>
                        </form>
                    </div>
                @endif
            </div>

            @if($source->last_error)
                <div class="ds-error" style="margin-top:14px">{{ $source->last_error }}</div>
            @endif

            @if(!empty($data['snapshot']))
                <div class="ds-alert">
                    <strong>Historical dataset:</strong>
                    figures relate to 31 December 2015. They must not be represented as current pending-case figures.
                </div>

                <div class="ds-grid" style="margin-top:17px">
                    <div class="ds-stat">
                        <div class="ds-muted">Total pending cases</div>
                        <strong>{{ number_format((float)($data['case_total'] ?? 0)) }}</strong>
                        <div class="ds-muted" style="font-size:12px">Historical figure · 31 Dec 2015</div>
                    </div>
                    <div class="ds-stat">
                        <div class="ds-muted">States / UT entries</div>
                        <strong>{{ number_format((int)($data['state_count'] ?? 0)) }}</strong>
                        <div class="ds-muted" style="font-size:12px">Excludes the national Total row</div>
                    </div>
                    <div class="ds-stat">
                        <div class="ds-muted">Records retrieved</div>
                        <strong>{{ number_format((int)($data['total'] ?? 0)) }}</strong>
                        <div class="ds-muted" style="font-size:12px">API-reported record count</div>
                    </div>
                </div>

                @if(!empty($data['has_visual_data']))
                    <div class="ds-card" style="margin-top:18px">
                        <h3 style="font-size:18px;font-weight:700;margin:0 0 5px">Top 10 States / UTs</h3>
                        <p class="ds-muted" style="font-size:13px;margin:0 0 17px">Ranked by pending cases in the historical dataset.</p>
                        @foreach($data['top_states'] as $index => $item)
                            <div class="ds-bar-row">
                                <div style="font-size:14px"><strong>{{ $index + 1 }}.</strong> {{ $item['state'] }}</div>
                                <div class="ds-bar-track">
                                    <div class="ds-bar-fill" style="width:{{ $item['bar_percent'] }}%"></div>
                                </div>
                                <div class="ds-bar-value" style="font-size:13px;font-variant-numeric:tabular-nums;text-align:right">{{ number_format($item['value']) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="ds-card" style="margin-top:18px">
                        <h3 style="font-size:18px;font-weight:700;margin:0 0 12px">State / UT wise pending cases</h3>
                        <input type="search" id="ds-table-search-{{ $source->id }}" class="ds-input" placeholder="Search State / UT..." data-filter-dashboard-table="{{ $source->id }}" style="max-width:380px;margin-bottom:13px">
                        <div class="ds-table-wrap">
                            <table class="ds-table" id="ds-table-{{ $source->id }}">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>State / UT</th>
                                        <th class="ds-number">Pending cases</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($data['chart_rows'] as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $item['state'] }}</td>
                                            <td class="ds-number">{{ number_format($item['value']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">National total (as supplied by dataset)</th>
                                        <th class="ds-number">{{ number_format((float)($data['case_total'] ?? 0)) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="ds-muted" style="font-size:12px;margin-top:10px">The national Total row is displayed separately and is not included as a State/UT entry.</p>
                    </div>
                @else
                    <div class="ds-alert">The API snapshot is saved, but automatic State/UT and numeric-field detection could not identify a suitable pair of columns. The discovered fields are shown below for configuration.</div>
                @endif

                <details style="margin-top:16px">
                    <summary style="cursor:pointer;font-weight:600">Discovered API fields and raw records</summary>
                    <div class="ds-table-wrap" style="margin-top:12px">
                        <table class="ds-table">
                            <thead><tr>
                                @foreach(($data['columns'] ?? []) as $column)
                                    <th>{{ $column }}</th>
                                @endforeach
                            </tr></thead>
                            <tbody>
                                @foreach(($data['records'] ?? []) as $record)
                                    <tr>
                                        @foreach(($data['columns'] ?? []) as $column)
                                            <td>{{ is_array($record) ? ($record[$column] ?? '') : '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @else
                <p class="ds-muted" style="margin-top:15px">
                    @if($readOnly ?? false)
                        No successful API snapshot is available yet for this public dashboard.
                    @else
                        No successful API snapshot is available yet. Use Test / Refresh API to retrieve the dataset.
                    @endif
                </p>
            @endif
        </section>
    @empty
        <div class="ds-card ds-muted">
            @if($readOnly ?? false)
                No data sources are currently available for this public dashboard.
            @else
                No data sources registered. Add a data.gov.in resource above.
            @endif
        </div>
    @endforelse

    <div class="ds-card">
        <h2 style="font-size:18px;font-weight:700;margin:0 0 8px">Dashboard configuration</h2>
    </div>

    @if(!($readOnly ?? false))
        <form method="POST" action="{{ route('dashboard-studio.destroy', $dashboard->id) }}" data-confirm="Permanently delete this dashboard and its registered data sources?">
            @csrf
            @method('DELETE')
            <button class="ds-button ds-button-danger" type="submit">Delete this dashboard</button>
        </form>
    @endif

    <footer class="ds-muted" style="font-size:12px;text-align:center;margin:30px 0">© {{ date('Y') }} VakilNiti. All rights reserved.</footer>
</div>

<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
function filterDashboardTable(sourceId) {
    const input = document.getElementById('ds-table-search-' + sourceId);
    const table = document.getElementById('ds-table-' + sourceId);
    if (!input || !table) return;

    const query = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
        row.style.display = row.cells[1].textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}
</script>
@endsection