<?php

namespace Database\Seeders;

use App\Models\PoliceDepartmentOffice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerifiedStatePoliceHeadquartersSeeder extends Seeder
{
    public function run(): void
    {
        $officialPortals = [
            'Andhra Pradesh' => ['office' => 'Andhra Pradesh Police Headquarters (DGP Office)', 'url' => 'https://citizen.appolice.gov.in/', 'source' => 'NIA / AP Police Official Portal'],
            'Arunachal Pradesh' => ['office' => 'Arunachal Pradesh Police Headquarters', 'url' => 'https://arunpol.nic.in/', 'source' => 'NIA / Arunachal Police Official Portal'],
            'Assam' => ['office' => 'Assam Police Headquarters', 'url' => 'https://police.assam.gov.in/', 'source' => 'NIA / Assam Police Official Portal'],
            'Bihar' => ['office' => 'Bihar Police Headquarters (Sardar Patel Bhawan)', 'url' => 'https://police.bihar.gov.in/', 'source' => 'NIA / Bihar Police Official Portal'],
            'Chhattisgarh' => ['office' => 'Chhattisgarh Police Headquarters (Nava Raipur)', 'url' => 'https://cgpolice.gov.in/', 'source' => 'NIA / CG Police Official Portal'],
            'Goa' => ['office' => 'Goa Police Headquarters (Panaji)', 'url' => 'https://citizen.goapolice.gov.in/', 'source' => 'NIA / Goa Police Official Portal'],
            'Gujarat' => ['office' => 'Gujarat Police Headquarters (Police Bhavan, Gandhinagar)', 'url' => 'https://police.gujarat.gov.in/', 'source' => 'NIA / Gujarat Police Official Portal'],
            'Haryana' => ['office' => 'Haryana Police Headquarters (Panchkula)', 'url' => 'https://haryanapolice.gov.in/', 'source' => 'NIA / Haryana Police Official Portal'],
            'Himachal Pradesh' => ['office' => 'Himachal Pradesh Police Headquarters (Shimla)', 'url' => 'https://citizenportal.hppolice.gov.in/', 'source' => 'NIA / HP Police Official Portal'],
            'Jharkhand' => ['office' => 'Jharkhand Police Headquarters (Dhurwa, Ranchi)', 'url' => 'https://www.jhpolice.gov.in/', 'source' => 'NIA / Jharkhand Police Official Portal'],
            'Karnataka' => ['office' => 'Karnataka State Police Headquarters (Nrupathunga Road, Bengaluru)', 'url' => 'https://ksp.karnataka.gov.in/', 'source' => 'NIA / Karnataka State Police Portal'],
            'Kerala' => ['office' => 'Kerala Police Headquarters (Thiruvananthapuram)', 'url' => 'https://keralapolice.gov.in/', 'source' => 'NIA / Kerala Police Official Portal'],
            'Madhya Pradesh' => ['office' => 'Madhya Pradesh Police Headquarters (Bhopal)', 'url' => 'https://www.mppolice.gov.in/', 'source' => 'NIA / MP Police Official Portal'],
            'Maharashtra' => ['office' => 'Maharashtra Police Headquarters (DGP Office, Mumbai)', 'url' => 'https://www.mahapolice.gov.in/', 'source' => 'NIA / Maharashtra Police Official Portal'],
            'Manipur' => ['office' => 'Manipur Police Headquarters (Imphal)', 'url' => 'https://manipurpolice.gov.in/', 'source' => 'NIA / Manipur Police Official Portal'],
            'Meghalaya' => ['office' => 'Meghalaya Police Headquarters (Shillong)', 'url' => 'https://megpolice.gov.in/', 'source' => 'NIA / Meghalaya Police Official Portal'],
            'Mizoram' => ['office' => 'Mizoram Police Headquarters (Aizawl)', 'url' => 'https://police.mizoram.gov.in/', 'source' => 'NIA / Mizoram Police Official Portal'],
            'Nagaland' => ['office' => 'Nagaland Police Headquarters (Kohima)', 'url' => 'https://police.nagaland.gov.in/', 'source' => 'NIA / Nagaland Police Official Portal'],
            'Odisha' => ['office' => 'Odisha State Police Headquarters (Cuttack)', 'url' => 'https://odishapolice.gov.in/', 'source' => 'NIA / Odisha Police Official Portal'],
            'Punjab' => ['office' => 'Punjab Police Headquarters (Sector 9, Chandigarh)', 'url' => 'https://www.punjabpolice.gov.in/', 'source' => 'NIA / Punjab Police Official Portal'],
            'Rajasthan' => ['office' => 'Rajasthan Police Headquarters (Jaipur)', 'url' => 'https://www.police.rajasthan.gov.in/', 'source' => 'NIA / Rajasthan Police Official Portal'],
            'Sikkim' => ['office' => 'Sikkim Police Headquarters (Gangtok)', 'url' => 'https://police.sikkim.gov.in/', 'source' => 'NIA / Sikkim Police Official Portal'],
            'Tamil Nadu' => ['office' => 'Tamil Nadu Police Headquarters (Mylapore, Chennai)', 'url' => 'https://eservices.tnpolice.gov.in/', 'source' => 'NIA / Tamil Nadu Police Official Portal'],
            'Telangana' => ['office' => 'Telangana State Police Headquarters (Lakdikapul, Hyderabad)', 'url' => 'https://www.tspolice.gov.in/', 'source' => 'NIA / Telangana Police Official Portal'],
            'Tripura' => ['office' => 'Tripura Police Headquarters (Agartala)', 'url' => 'https://tripurapolice.gov.in/', 'source' => 'NIA / Tripura Police Official Portal'],
            'Uttar Pradesh' => ['office' => 'Uttar Pradesh Police Headquarters (Signature Building, Gomti Nagar, Lucknow)', 'url' => 'https://uppolice.gov.in/', 'source' => 'UP Police Official Portal'],
            'Uttarakhand' => ['office' => 'Uttarakhand Police Headquarters (Dehradun)', 'url' => 'https://uttarakhandpolice.uk.gov.in/', 'source' => 'NIA / Uttarakhand Police Official Portal'],
            'West Bengal' => ['office' => 'West Bengal Police Directorate (Bhabani Bhawan, Kolkata)', 'url' => 'https://wbpolice.gov.in/', 'source' => 'NIA / West Bengal Police Official Portal'],
            'Andaman and Nicobar Islands' => ['office' => 'Andaman & Nicobar Police Headquarters (Sri Vijaya Puram)', 'url' => 'https://police.andaman.gov.in/', 'source' => 'NIA / A&N Police Official Portal'],
            'Chandigarh' => ['office' => 'Chandigarh Police Headquarters (Sector 9-D)', 'url' => 'https://chandigarhpolice.gov.in/', 'source' => 'NIA / Chandigarh Police Official Portal'],
            'Dadra and Nagar Haveli and Daman and Diu' => ['office' => 'DNH & DD Police Headquarters', 'url' => 'https://police.ddd.gov.in/', 'source' => 'NIA / DNH & DD Police Official Portal'],
            'Delhi' => ['office' => 'Delhi Police Headquarters (Jai Singh Road, New Delhi)', 'url' => 'https://www.delhipolice.gov.in/', 'source' => 'Delhi Police Official Portal'],
            'Jammu and Kashmir' => ['office' => 'Jammu & Kashmir Police Headquarters', 'url' => 'https://www.jkpolice.gov.in/', 'source' => 'NIA / J&K Police Official Portal'],
            'Ladakh' => ['office' => 'Ladakh Police Headquarters (Leh)', 'url' => 'https://police.ladakh.gov.in/', 'source' => 'NIA / Ladakh Police Official Portal'],
            'Lakshadweep' => ['office' => 'Lakshadweep Police Headquarters (Kavaratti)', 'url' => 'https://lakshadweeppolice.gov.in/', 'source' => 'NIA / Lakshadweep Police Official Portal'],
            'Puducherry' => ['office' => 'Puducherry Police Headquarters', 'url' => 'https://police.py.gov.in/', 'source' => 'NIA / Puducherry Police Official Portal'],
        ];

        $states = DB::table('states')->get();

        foreach ($states as $state) {
            $matchedKey = null;
            foreach (array_keys($officialPortals) as $key) {
                if (mb_strtolower(trim($key)) === mb_strtolower(trim($state->name))) {
                    $matchedKey = $key;
                    break;
                }
            }

            if ($matchedKey) {
                $info = $officialPortals[$matchedKey];
                PoliceDepartmentOffice::updateOrCreate(
                    [
                        'state_id' => $state->id,
                        'office_name' => $info['office'],
                    ],
                    [
                        'office_type' => 'State/UT Police Headquarters',
                        'designation' => 'Director General of Police / Commissioner of Police',
                        'phone' => '112',
                        'official_url' => $info['url'],
                        'source_name' => $info['source'],
                        'source_url' => 'https://nia.mha.gov.in/en/state-police-organisations',
                        'source_status' => 'verified',
                        'last_verified_at' => now(),
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}