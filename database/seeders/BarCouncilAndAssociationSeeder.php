<?php

namespace Database\Seeders;

use App\Models\BarAssociation;
use App\Models\BarCouncil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bar councils and bar associations for the Bar directory.
 *
 * Councils: the Bar Council of India and every State Bar Council, exactly as listed in the BCI's own
 * directory (barcouncilofindia.org/info/sbc), with each website checked on 24.11.2026. Associations:
 * names, courts and addresses only; their phone numbers, emails and websites could not be confirmed.
 * Safe to re-run: rows are matched by name, and councils no longer in the BCI list are removed.
 */
class BarCouncilAndAssociationSeeder extends Seeder
{
    public function run(): void
    {
        $verifiedAt = now();
        $sourceName = 'Bar Council of India: State Bar Council directory';
        $sourceUrl = 'https://www.barcouncilofindia.org/info/sbc';

        $councils = [
            ['state_name' => null, 'council_name' => 'Bar Council of India', 'jurisdiction_type' => 'national', 'office_address' => '21, Rouse Avenue Institutional Area, Near Bal Bhawan, New Delhi 110002', 'contact_phone' => '011-49225000', 'email' => 'bciinfo21@gmail.com', 'official_url' => 'https://www.barcouncilofindia.org/'],
            ['state_name' => 'Andhra Pradesh', 'council_name' => 'Bar Council of Andhra Pradesh', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Premises Hyderabad 500066', 'contact_phone' => '040-24524116', 'email' => 'secapbc@gmail.com', 'official_url' => 'https://barcouncilap.org/'],
            ['state_name' => 'Assam', 'council_name' => 'Bar Council of Assam', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Building, Post Box No. 182 Guwahati 781001', 'contact_phone' => '0361-2544663', 'email' => 'barcouncilofassametc@yahoo.in', 'official_url' => null],
            ['state_name' => 'Bihar', 'council_name' => 'Bihar State Bar Council', 'jurisdiction_type' => 'state', 'office_address' => 'Bar Council Bhawan, East of Patna High Court, Patna 800001', 'contact_phone' => '0612-2504125, 2505583', 'email' => 'bsbcpatna@gmail.com', 'official_url' => 'https://biharstatebarcouncil.com/'],
            ['state_name' => 'Chhattisgarh', 'council_name' => 'Bar Council of Chhattisgarh', 'jurisdiction_type' => 'state', 'office_address' => 'New High Court Premises Upside Advocate Room, Bodri, Bilaspur, Chhattisgarh 495001', 'contact_phone' => '07752-241331, 241005, 211354', 'email' => 'sbccgbilaspur@gmail.com', 'official_url' => 'http://www.sbcchhattisgarh.in/'],
            ['state_name' => 'Delhi', 'council_name' => 'Bar Council of Delhi', 'jurisdiction_type' => 'state', 'office_address' => '2/6, Sirifort Institutional Area Khel Gaon Marg Delhi 110049', 'contact_phone' => '011-26495195, 26495196, 26495356, 23387701, 23385702', 'email' => 'barcouncilofdelhi@rediffmail.com', 'official_url' => 'https://delhibarcouncil.com/'],
            ['state_name' => 'Gujarat', 'council_name' => 'Bar Council of Gujarat', 'jurisdiction_type' => 'state', 'office_address' => '3rd Floor, Satyamev Complex, Opp: High Court of Gujarat, Sola, Ahmedabad 380060', 'contact_phone' => '079-65226272, 079-27663460', 'email' => 'mail@barcouncilofgujarat.org', 'official_url' => 'https://www.barcouncilofgujarat.org/'],
            ['state_name' => 'Himachal Pradesh', 'council_name' => 'Bar Council of Himachal Pradesh', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Complex Ravenswood Shimla, Himachal Pradesh 171001', 'contact_phone' => '0177-2659071, 2658071', 'email' => 'bchp1971@gmail.com', 'official_url' => 'https://hpbarcouncil.in'],
            ['state_name' => 'Jammu and Kashmir', 'council_name' => 'High Court of Jammu & Kashmir (enrolment)', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Campus Jammu, (J&K), the Registrar High Court of Jammu & Kashmir High Court Campus Srinagar (J&K) 190001', 'contact_phone' => null, 'email' => null, 'official_url' => 'https://jkhighcourt.nic.in/'],
            ['state_name' => 'Jharkhand', 'council_name' => 'Jharkhand State Bar Council', 'jurisdiction_type' => 'state', 'office_address' => 'Bungalow No. 8. Near Kendriya Vidyalay, North Office Para, Doranda, Ranchi, Jharkhand 834002', 'contact_phone' => '0651-2410008, 2482769', 'email' => 'info@jharkhandstatebarcouncil.org', 'official_url' => 'https://jharkhandstatebarcouncil.in/'],
            ['state_name' => 'Karnataka', 'council_name' => 'Bar Council of Karnataka', 'jurisdiction_type' => 'state', 'office_address' => 'Old K.G.I.D. Building, Dr. Ambedkar Veedhi, Bangalore, Karnataka 560001', 'contact_phone' => '080-22868712, 22868561', 'email' => 'kar_barcouncil@yahoo.com', 'official_url' => 'https://ksbc.org.in/'],
            ['state_name' => 'Kerala', 'council_name' => 'Bar Council of Kerala', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Campus Ernakulam, Kerala 682031', 'contact_phone' => '0484-2394195, 2393810', 'email' => 'barcouncilofkerala@hotmail.com', 'official_url' => 'https://www.barcouncilkerala.org/'],
            ['state_name' => 'Madhya Pradesh', 'council_name' => 'Bar Council of Madhya Pradesh', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Campus Jabalpur, MP 482007', 'contact_phone' => '0761-2678877', 'email' => 'bar_council_mp@yahoo.in', 'official_url' => 'http://www.sbcofmp.org.in/'],
            ['state_name' => 'Maharashtra', 'council_name' => 'Bar Council of Maharashtra & Goa', 'jurisdiction_type' => 'state', 'office_address' => '2nd Floor, High Court Extn., Fort Mumbai, Maharashtra 400032', 'contact_phone' => '022-2267331, 22656567, 22677507, 22677508', 'email' => 'barcouncilmahgoa@gmail.com', 'official_url' => 'https://www.barcouncilmahgoa.org/'],
            ['state_name' => 'Manipur', 'council_name' => 'Bar Council of Manipur', 'jurisdiction_type' => 'state', 'office_address' => 'High Court of Manipur, at Imphal Mantripukhri 795002', 'contact_phone' => null, 'email' => null, 'official_url' => null],
            ['state_name' => 'Meghalaya', 'council_name' => 'High Court of Meghalaya (enrolment)', 'jurisdiction_type' => 'state', 'office_address' => 'Mg Road, Shillong, Meghalaya 793001', 'contact_phone' => null, 'email' => null, 'official_url' => null],
            ['state_name' => 'Odisha', 'council_name' => 'Odisha State Bar Council', 'jurisdiction_type' => 'state', 'office_address' => 'Near High Court of Odisha, Chandini Chowk, Cuttack Odisha 753002', 'contact_phone' => '0671-2508453, 2508622, 2508422', 'email' => 'orissastatebarcouncil@gmail.com', 'official_url' => 'https://barcouncilofodisha.org/'],
            ['state_name' => 'Punjab', 'council_name' => 'Bar Council of Punjab & Haryana', 'jurisdiction_type' => 'state', 'office_address' => 'Law Bhawan, Sector-37-A, Dakshin Marg, Chandigarh 160036', 'contact_phone' => '0172-2688519, 2688507', 'email' => 'lawbhawan37@gmail.com', 'official_url' => 'https://bcph.co.in/'],
            ['state_name' => 'Rajasthan', 'council_name' => 'Bar Council of Rajasthan', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Building Jodhpur, Rajasthan 342001', 'contact_phone' => '0291-2545066, 2545251, 2364084', 'email' => 'secretary@barcouncilofrajasthan.org', 'official_url' => 'https://barcouncilofrajasthan.org'],
            ['state_name' => 'Tamil Nadu', 'council_name' => 'Bar Council of Tamil Nadu & Puducherry', 'jurisdiction_type' => 'state', 'office_address' => 'Bar Council Buildings, High Court Campus Chennai, Tamil Nadu 600104', 'contact_phone' => '044-25342738, 25352728, 25352595', 'email' => 'tnbarcouncil@yahoo.com', 'official_url' => 'https://www.bctnpy.org/'],
            ['state_name' => 'Telangana', 'council_name' => 'Bar Council of Telangana', 'jurisdiction_type' => 'state', 'office_address' => '9f9f+q93, High Court, Ghansi Bazaar, Hyderabad, Telangana 500066', 'contact_phone' => '040-24520443, 24524116', 'email' => 'sectsbc@gmail.com', 'official_url' => 'https://www.telanganabarcouncil.org/'],
            ['state_name' => 'Tripura', 'council_name' => 'Bar Council of Tripura', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Complex New Capital Complex, Lichubagan, Agartala, Tripura 799010', 'contact_phone' => null, 'email' => null, 'official_url' => null],
            ['state_name' => 'Uttar Pradesh', 'council_name' => 'Bar Council of Uttar Pradesh', 'jurisdiction_type' => 'state', 'office_address' => '19, Maharishi Dayanand Marg Allahabad, UP 211001', 'contact_phone' => '0532-2623501', 'email' => 'barcouncilup@gmail.com', 'official_url' => 'http://upbarcouncil.com/'],
            ['state_name' => 'Uttarakhand', 'council_name' => 'Bar Council of Uttarakhand', 'jurisdiction_type' => 'state', 'office_address' => 'High Court Campus, Nainital, Uttarakhand 263001', 'contact_phone' => '05942-233525', 'email' => 'info@barcouncilofuttarakhand.org', 'official_url' => 'https://www.barcouncilofuttarakhand.org/'],
            ['state_name' => 'West Bengal', 'council_name' => 'Bar Council of West Bengal', 'jurisdiction_type' => 'state', 'office_address' => '2&3, Kiran Sankar Roy Road. City Civil Court Building, 7th Floor, Kolkata 700001', 'contact_phone' => '033-22488956, 22487233, 23377997', 'email' => 'barcouncilwestbengal@gmail.com', 'official_url' => null],
        ];

        foreach ($councils as $council) {
            $stateId = $council['state_name'] ? DB::table('states')->where('name', $council['state_name'])->value('id') : null;

            BarCouncil::updateOrCreate(
                ['council_name' => $council['council_name']],
                [
                    'state_id' => $stateId,
                    'jurisdiction_type' => $council['jurisdiction_type'],
                    'office_address' => $council['office_address'],
                    'contact_phone' => $council['contact_phone'],
                    'email' => $council['email'],
                    'official_url' => $council['official_url'],
                    'source_name' => $sourceName,
                    'source_url' => $sourceUrl,
                    'source_status' => 'verified',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        BarCouncil::query()->whereNotIn('council_name', array_column($councils, 'council_name'))->delete();

        $associations = [
            ['state_name' => 'Delhi', 'association_name' => 'Supreme Court Bar Association (SCBA)', 'court_type' => 'Supreme Court', 'office_address' => 'Supreme Court of India, Tilak Marg, New Delhi 110001'],
            ['state_name' => 'Delhi', 'association_name' => 'Delhi High Court Bar Association (DHCBA)', 'court_type' => 'High Court', 'office_address' => 'Delhi High Court, Sher Shah Road, New Delhi 110003'],
            ['state_name' => 'Delhi', 'association_name' => 'New Delhi Bar Association (NDBA)', 'court_type' => 'District Court', 'office_address' => 'Patiala House Courts, New Delhi 110001'],
            ['state_name' => 'Uttar Pradesh', 'association_name' => 'High Court Bar Association, Prayagraj', 'court_type' => 'High Court', 'office_address' => 'High Court Campus, Prayagraj, Uttar Pradesh 211001'],
            ['state_name' => 'Maharashtra', 'association_name' => 'Bombay High Court Bar Association', 'court_type' => 'High Court', 'office_address' => 'Bombay High Court, Fort, Mumbai 400032'],
        ];

        foreach ($associations as $association) {
            $stateId = DB::table('states')->where('name', $association['state_name'])->value('id');

            if (! $stateId) {
                continue;
            }

            BarAssociation::updateOrCreate(
                ['association_name' => $association['association_name']],
                [
                    'state_id' => $stateId,
                    'court_type' => $association['court_type'],
                    'office_address' => $association['office_address'],
                    'contact_phone' => null,
                    'email' => null,
                    'official_url' => null,
                    'source_name' => 'Name and court only',
                    'source_url' => null,
                    'source_status' => 'unconfirmed_contacts',
                    'last_verified_at' => $verifiedAt,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Bar councils ('.count($councils).') and bar associations seeded.');
    }
}
