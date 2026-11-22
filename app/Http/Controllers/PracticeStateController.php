<?php

namespace App\Http\Controllers;

use App\Models\JudicialDistrict;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PracticeStateController extends Controller
{
    /**
     * @return list<string>
     */
    private function stateList(): array
    {
        return array_keys(State::indianStatesAndUnionTerritories());
    }

    public function index()
    {
        $selectedStates = [];
        $courtCounts = [];
        $stateRecordsByName = [];
        $districtSelectionModes = [];
        $selectedDistrictIds = [];

        $practiceRows = DB::table('firm_practice_states')
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('state_name')
            ->get();

        if ($practiceRows->isNotEmpty()) {

            $selectedStates = $practiceRows->pluck('state_name')->all();

            $stateRecords = State::whereIn('name', $selectedStates)
                ->get()
                ->keyBy('name');

            foreach ($practiceRows as $practiceRow) {
                $stateRecord = $stateRecords->get($practiceRow->state_name);

                if (! $stateRecord) {
                    continue;
                }

                $stateRecordsByName[$practiceRow->state_name] = $stateRecord;

                $districtSelectionModes[$stateRecord->id] =
                    $practiceRow->district_selection_mode ?: 'all';

                $selectedDistrictIds[$stateRecord->id] =
                    DB::table('firm_practice_districts')
                        ->where('user_id', auth()->id())
                        ->where('firm_practice_state_id', $practiceRow->id)
                        ->where('is_active', true)
                        ->pluck('judicial_district_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
            }

            // Catalogue coverage is shown to admins only.
            if (! empty($selectedStates) && auth()->user()->is_admin) {
                $courtCounts = DB::table('courts')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')
                    ->whereIn('state', $selectedStates)
                    ->select('state', DB::raw('COUNT(*) as total'))
                    ->groupBy('state')
                    ->pluck('total', 'state')
                    ->all();
            }
        }

        return view('profile.practice-states', [
            'states' => $this->stateList(),
            'selectedStates' => $selectedStates,
            'courtCounts' => $courtCounts,
            'stateRecordsByName' => $stateRecordsByName,
            'districtSelectionModes' => $districtSelectionModes,
            'selectedDistrictIds' => $selectedDistrictIds,
        ]);
    }

    public function store(Request $request)
    {
        $firm = DB::table('firm_profiles')
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();

        $validator = Validator::make($request->all(), [
            'states' => ['sometimes', 'array'],
            'states.*' => [
                'required',
                'string',
                'distinct',
                'in:'.implode(',', $this->stateList()),
            ],
            'district_selection_mode' => ['sometimes', 'array'],
            'district_selection_mode.*' => ['required', 'in:none,all,selected'],
            'districts' => ['sometimes', 'array'],
            'districts.*' => ['sometimes', 'array'],
            'districts.*.*' => [
                'required',
                'integer',
                'distinct',
                'exists:judicial_districts,id',
            ],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The jurisdiction selection could not be saved.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $selectedStates = array_values(array_unique($validated['states'] ?? []));
        $modes = $validated['district_selection_mode'] ?? [];
        $districtSelections = $validated['districts'] ?? [];

        $stateRecords = State::whereIn('name', $selectedStates)
            ->get()
            ->keyBy('id');

        if ($stateRecords->count() !== count($selectedStates)) {
            $message = 'One or more selected states are not available in the state catalogue.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            throw ValidationException::withMessages(['states' => $message]);
        }

        foreach ($modes as $stateId => $mode) {
            $stateRecord = $stateRecords->get((int) $stateId);

            if (! $stateRecord || ! in_array($stateRecord->name, $selectedStates, true)) {
                $message = 'A district setting was submitted for an unselected state.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                throw ValidationException::withMessages([
                    'district_selection_mode' => $message,
                ]);
            }
        }

        foreach ($districtSelections as $stateId => $districtIds) {
            $stateRecord = $stateRecords->get((int) $stateId);

            if (! $stateRecord || ! in_array($stateRecord->name, $selectedStates, true)) {
                $message = 'A district was submitted for an unselected state.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                throw ValidationException::withMessages(['districts' => $message]);
            }

            $validCount = JudicialDistrict::query()
                ->where('state_id', $stateRecord->id)
                ->where('is_active', true)
                ->whereIn('id', $districtIds)
                ->count();

            if ($validCount !== count($districtIds)) {
                $message = 'One or more districts are inactive or do not belong to the selected state.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                throw ValidationException::withMessages(['districts' => $message]);
            }
        }

        foreach ($stateRecords as $stateId => $stateRecord) {
            $mode = $modes[$stateId] ?? 'none';
            $districtIds = $districtSelections[$stateId] ?? [];

            if ($mode === 'selected' && count($districtIds) === 0) {
                $message = 'Select a district, choose Select All, or use High Court-only practice for '.$stateRecord->name.'.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                throw ValidationException::withMessages(['districts' => $message]);
            }

            if ($mode === 'none' && count($districtIds) > 0) {
                $message = 'District IDs cannot be submitted when district practice is disabled.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                throw ValidationException::withMessages(['districts' => $message]);
            }
        }

        $now = now();

        DB::transaction(function () use (
            $firm,
            $selectedStates,
            $stateRecords,
            $modes,
            $districtSelections,
            $now
        ) {
            foreach ($selectedStates as $stateName) {
                $existing = DB::table('firm_practice_states')
                    ->where('user_id', auth()->id())
                    ->where('state_name', $stateName)
                    ->first();

                if ($existing) {
                    DB::table('firm_practice_states')
                        ->where('user_id', auth()->id())
                        ->where('id', $existing->id)
                        ->update([
                            'is_active' => true,
                            'updated_at' => $now,
                        ]);
                } else {
                    DB::table('firm_practice_states')
                        ->where('user_id', auth()->id())->insert([
                            'user_id' => auth()->id(),
                            'firm_profile_id' => $firm?->id,
                            'state_name' => $stateName,
                            'is_active' => true,
                            'district_selection_mode' => 'none',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            }

            // Deactivate deselected states without deleting their records.
            DB::table('firm_practice_states')
                ->where('user_id', auth()->id())
                ->whereNotIn('state_name', $selectedStates)
                ->update([
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

            foreach ($stateRecords as $stateId => $stateRecord) {
                $practiceState = DB::table('firm_practice_states')
                    ->where('user_id', auth()->id())
                    ->where('state_name', $stateRecord->name)
                    ->first();

                if (! $practiceState) {
                    continue;
                }

                $mode = $modes[$stateId] ?? 'none';

                DB::table('firm_practice_states')
                    ->where('user_id', auth()->id())
                    ->where('id', $practiceState->id)
                    ->update([
                        'district_selection_mode' => $mode,
                        'updated_at' => $now,
                    ]);

                // Keep historical associations but deactivate them first.
                DB::table('firm_practice_districts')
                    ->where('user_id', auth()->id())
                    ->where('firm_practice_state_id', $practiceState->id)
                    ->update([
                        'is_active' => false,
                        'updated_at' => $now,
                    ]);

                if ($mode !== 'selected') {
                    continue;
                }

                foreach ($districtSelections[$stateId] ?? [] as $districtId) {
                    $existingDistrictSelection = DB::table('firm_practice_districts')
                        ->where('user_id', auth()->id())
                        ->where('firm_practice_state_id', $practiceState->id)
                        ->where('judicial_district_id', $districtId)
                        ->first();

                    if ($existingDistrictSelection) {
                        DB::table('firm_practice_districts')
                            ->where('user_id', auth()->id())
                            ->where('id', $existingDistrictSelection->id)
                            ->update([
                                'is_active' => true,
                                'updated_at' => $now,
                            ]);
                    } else {
                        DB::table('firm_practice_districts')
                            ->where('user_id', auth()->id())->insert([
                                'user_id' => auth()->id(),
                                'firm_practice_state_id' => $practiceState->id,
                                'judicial_district_id' => $districtId,
                                'is_active' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                    }
                }
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Practice jurisdictions saved successfully.',
                'selected_states' => $selectedStates,
            ]);
        }

        return redirect()
            ->route('profile.practice-states.index')
            ->with('success', 'Practice states and district selections updated successfully.');
    }
}
