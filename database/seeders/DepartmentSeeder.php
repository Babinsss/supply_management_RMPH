<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            // --- ADMINISTRATIVE DEPARTMENTS ---
            ['group_name' => 'Administrative Department', 'name' => 'Admitting', 'head_name' => 'JULIO MEDINA'],
            ['group_name' => 'Administrative Department', 'name' => 'Accounting', 'head_name' => 'JAHZEILE SALISTRE'],
            ['group_name' => 'Administrative Department', 'name' => 'Building & Maintenance', 'head_name' => 'MARY JANE BALDISMO'],
            ['group_name' => 'Administrative Department', 'name' => 'Credit & Collection', 'head_name' => 'MEMIA BUGTONG'],
            ['group_name' => 'Administrative Department', 'name' => 'HR', 'head_name' => 'FRANCES THERESE MIRANDA'],
            ['group_name' => 'Administrative Department', 'name' => 'Supply', 'head_name' => 'LADY ORTALEZ LUCES'],
            ['group_name' => 'Administrative Department', 'name' => 'ICT', 'head_name' => 'AIZA OBLIGAR'],
            ['group_name' => 'Administrative Department', 'name' => 'Medical Records', 'head_name' => 'SHERYL ABLAO'],
            ['group_name' => 'Administrative Department', 'name' => 'Cashier', 'head_name' => 'ROSELA FERNANDO'],
            ['group_name' => 'Administrative Department', 'name' => 'Billing & Claims', 'head_name' => 'REGNER BRILLO'],
            ['group_name' => 'Administrative Department', 'name' => 'Malasakit', 'head_name' => 'LIZAMAE BERANO'],
            ['group_name' => 'Administrative Department', 'name' => 'Dietary', 'head_name' => 'ROBENIA DAYALO'],
            ['group_name' => 'Administrative Department', 'name' => 'Consignment', 'head_name' => 'ALIANA MARIE DULA/KRISTINE MAE BATAN'],
            ['group_name' => 'Administrative Department', 'name' => 'Quality Management Office', 'head_name' => 'JHOANNA CRUZ-AM'],
            ['group_name' => 'Administrative Department', 'name' => 'Chief of Hospital II', 'head_name' => 'DR. FLORENCIO LUCHING'],
            ['group_name' => 'Administrative Department', 'name' => 'Chief of Clinics', 'head_name' => 'DR. VINCENT JURY LAURON'],

            // --- WARDS & ANCILLARY UNITS ---
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'CSR', 'head_name' => 'MIA BUENVENIDA'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'LAB', 'head_name' => 'MARIJOE ARTATES'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'RADIO', 'head_name' => 'SOCRATES BERCADEZ'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'PHARMA', 'head_name' => 'SHARA PATRIA SANTOS'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'CARDIO PULMONARY', 'head_name' => 'SONIA FLORENCIO'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'WCPU', 'head_name' => 'ANNIELEE ARIEL'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'IW', 'head_name' => 'ANABELLE DENAGA'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'Laundry', 'head_name' => 'LENNIE TOCONG'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'REHAB', 'head_name' => 'ANABELLE GARCIA'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'NSO', 'head_name' => 'GLENA PIMENTEL'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'ORTHO', 'head_name' => 'SUSIE ARMIZA'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'OB', 'head_name' => 'WENDY MARTINEZ'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'ER', 'head_name' => 'CHRISTINE ESQUILLO'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'FMW', 'head_name' => 'MARY GRACE BUARON'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'MMW', 'head_name' => 'JOSETTH LYNANNE TAN'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'ICU', 'head_name' => 'DREXCY JHOY SAN ANTONIO'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'NICU', 'head_name' => 'MAY RACILLE JOY LANTORIA'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'SURGICAL', 'head_name' => 'LOUIE ANN AJERA'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'PEDIA', 'head_name' => 'EVELYN AMBROSIO'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'OR', 'head_name' => 'JADD LOUIE UVAS'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'PAYWARD', 'head_name' => 'MARIA VICTORIA ESCUTIN'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'HEMO', 'head_name' => 'EVANGELINE DETANOY'],
            ['group_name' => 'Wards / Ancillary Units', 'name' => 'GUGMA DIALYSIS', 'head_name' => 'STEPHEN ESPENOCILLA'],
        ];

        foreach ($departments as $dept) {
            // updateOrCreate prevents duplicate entries if you run the seeder twice
            Department::updateOrCreate(
                ['name' => $dept['name']], // Check if department already exists by name
                [
                    'group_name' => $dept['group_name'],
                    'head_name' => $dept['head_name'],
                    'is_active' => true
                ]
            );
        }
    }
}