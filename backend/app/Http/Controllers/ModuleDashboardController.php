<?php

namespace App\Http\Controllers;

use App\Models\PoliceStation;
use App\Models\PoliceDistrict;
use App\Models\PoliceRange;
use App\Models\PoliceSubDivision;
use App\Models\PoliceDepartmentOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ModuleDashboardController extends Controller
{
    public function policeAdministration(): View
    {
        $allStates = DB::table('states')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // Read the existing firm Profile practice jurisdictions.
        $activeFirm = auth()->check()
            ? DB::table('firm_profiles')
                ->where('user_id', auth()->id())
                ->where('is_active', true)
                ->first()
            : null;

        $practiceRows = collect();

        if ($activeFirm) {
            $practiceRows = DB::table('firm_practice_states')
                ->where('user_id', auth()->id())
                ->where('firm_profile_id', $activeFirm->id)
                ->where('is_active', true)
                ->orderBy('state_name')
                ->get();
        }

        $profileStateIds = [];
        $practiceStateRows = [];

        foreach ($practiceRows as $practiceRow) {
            $matchedState = $allStates->first(function ($state) use ($practiceRow) {
                return mb_strtolower(trim($state->name)) ===
                    mb_strtolower(trim($practiceRow->state_name));
            });

            if ($matchedState) {
                $profileStateIds[] = (int) $matchedState->id;
                $practiceStateRows[(int) $matchedState->id] = $practiceRow;
            }
        }

        $profileStateIds = array_values(array_unique($profileStateIds));

        $availableStates = $allStates
            ->sortBy(function ($state) use ($profileStateIds) {
                $position = array_search((int) $state->id, $profileStateIds, true);

                return $position === false
                    ? 10000
                    : $position;
            })
            ->values();

        $requestedStateId = request()->integer('state_id');
        $stateId = $requestedStateId ?: ($profileStateIds[0] ?? 0);
        $selectedState = $availableStates->firstWhere('id', $stateId);

        $districtId = request()->integer('police_district_id');
        $directoryDistrictId = request()->integer('district_id');
        $subDivisionId = request()->integer('police_sub_division_id');
        $rangeId = request()->integer('police_range_id');
        $specialistCategory = trim((string) request()->query('specialist_category', ''));
        $search = trim((string) request()->query('q', ''));

        $policeDistricts = collect();
        $policeRanges = collect();
        $policeSubDivisions = collect();
        $directoryDistricts = collect();
        $selectedDirectoryDistrict = null;
        $selectedDistrict = null;
        $stations = collect();
        $departmentOffices = collect();

        if ($selectedState) {
            $directoryDistricts = DB::table('judicial_districts')
                ->where('state_id', $selectedState->id)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->orderBy('district_name')
                ->get(['id', 'district_name', 'district_code']);

            if ($directoryDistrictId) {
                $selectedDirectoryDistrict = $directoryDistricts->firstWhere(
                    'id',
                    $directoryDistrictId
                );
            }

            $policeRanges = DB::table('police_ranges')
                ->where('state_id', $selectedState->id)
                ->where('is_active', true)
                ->orderBy('range_name')
                ->get();

            $policeDistrictsQuery = DB::table('police_districts')
                ->where('state_id', $selectedState->id)
                ->where('is_active', true);

            if ($rangeId) {
                $policeDistrictsQuery->where('police_range_id', $rangeId);
            }

            $policeDistricts = $policeDistrictsQuery->orderBy('district_name')->get();

            $subDivisionsQuery = DB::table('police_sub_divisions')
                ->where('state_id', $selectedState->id)
                ->where('is_active', true);

            if ($districtId) {
                $subDivisionsQuery->where('police_district_id', $districtId);
            }
            $policeSubDivisions = $subDivisionsQuery->orderBy('sub_division_name')->get();

            if ($districtId) {
                $selectedDistrict = $policeDistricts->firstWhere('id', $districtId);
            }

            $stationsQuery = PoliceStation::with(['policeDistrict', 'subDivision', 'state'])
                ->where('state_id', $selectedState->id)
                ->where('is_active', true);

            if ($districtId) {
                $stationsQuery->where('police_district_id', $districtId);
            }

            if ($subDivisionId) {
                $stationsQuery->where('police_sub_division_id', $subDivisionId);
            }

            if ($rangeId) {
                $stationsQuery->whereHas('policeDistrict', function ($q) use ($rangeId) {
                    $q->where('police_range_id', $rangeId);
                });
            }

            if ($specialistCategory !== '') {
                if ($specialistCategory === 'all') {
                    $stationsQuery->where('is_specialist_unit', true);
                } else {
                    $stationsQuery->where('is_specialist_unit', true)
                        ->where('specialist_unit_category', $specialistCategory);
                }
            }

            if ($search !== '') {
                $stationsQuery->where(function ($q) use ($search) {
                    $q->where('station_name', 'like', "%{$search}%")
                      ->orWhere('station_code', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('locality', 'like', "%{$search}%")
                      ->orWhere('pincode', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('sho_name', 'like', "%{$search}%");
                });
            }

            $stations = $stationsQuery->orderBy('station_name')->paginate(25)->withQueryString();

            $departmentOffices = DB::table('police_department_offices')
                ->where('state_id', $selectedState->id)
                ->where('is_active', true)
                ->when($districtId, function ($q) use ($districtId) {
                    $q->where('police_district_id', $districtId);
                })
                ->orderBy('office_name')
                ->get();
        }

        return view('modules.police-administration', compact(
            'availableStates',
            'selectedState',
            'policeRanges',
            'policeDistricts',
            'policeSubDivisions',
            'directoryDistricts',
            'selectedDirectoryDistrict',
            'selectedDistrict',
            'stations',
            'departmentOffices',
            'search'
        ));
    }

    public function policeStationDetails($stationId): View
    {
        $station = PoliceStation::with(['policeDistrict', 'subDivision', 'state'])->findOrFail($stationId);

        return view('modules.police-station-details', compact('station'));
    }

    public function barAssociations(): View
    {
        return view('modules.bar-associations');
    }

    public function miscellaneousForms(): View
    {
        return view('modules.miscellaneous-forms');
    }

    public function newCriminalLaws(): View
    {
        return view('modules.new-criminal-laws');
    }

    public function courtFee(): View
    {
        return view('modules.court-fee');
    }

    public function bareActs(): View
    {
        $actGroups = [
            [
                'id' => 'civil-laws',
                'title' => 'Civil procedure and remedies',
                'acts' => [
                    ['title' => 'Code of Civil Procedure', 'year' => '1908', 'topic' => 'Civil procedure'],
                    ['title' => 'Limitation Act', 'year' => '1963', 'topic' => 'Limitation periods'],
                    ['title' => 'Specific Relief Act', 'year' => '1963', 'topic' => 'Civil remedies'],
                    ['title' => 'Indian Contract Act', 'year' => '1872', 'topic' => 'Contracts'],
                    ['title' => 'Transfer of Property Act', 'year' => '1882', 'topic' => 'Property'],
                    ['title' => 'Registration Act', 'year' => '1908', 'topic' => 'Registration'],
                ],
            ],
            [
                'id' => 'criminal-laws',
                'title' => 'Criminal law codes',
                'acts' => [
                    ['title' => 'Bharatiya Nyaya Sanhita', 'year' => '2023', 'topic' => 'Substantive criminal law'],
                    ['title' => 'Bharatiya Nagarik Suraksha Sanhita', 'year' => '2023', 'topic' => 'Criminal procedure'],
                    ['title' => 'Bharatiya Sakshya Adhiniyam', 'year' => '2023', 'topic' => 'Evidence'],
                    ['title' => 'Indian Penal Code', 'year' => '1860', 'topic' => 'Legacy criminal law reference', 'legacy' => true],
                    ['title' => 'Code of Criminal Procedure', 'year' => '1973', 'topic' => 'Legacy procedure reference', 'legacy' => true],
                    ['title' => 'Indian Evidence Act', 'year' => '1872', 'topic' => 'Legacy evidence reference', 'legacy' => true],
                ],
            ],
            [
                'id' => 'other-central-laws',
                'title' => 'Constitution and legal profession',
                'acts' => [
                    ['title' => 'Constitution of India', 'year' => '', 'topic' => 'Constitutional law'],
                    ['title' => 'Advocates Act', 'year' => '1961', 'topic' => 'Legal profession'],
                ],
            ],
        ];

        return view('modules.bare-acts', compact('actGroups'));
    }

    public function pdfAnnotator(): View
    {
        return view('modules.pdf-annotator');
    }
}