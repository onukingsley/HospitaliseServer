<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ========== HELPER FUNCTIONS ==========
        $this->generateData();

        $this->command->info('✅ Database seeding completed successfully!');
    }

    private function generateData(): void
    {
        // ========== GET PROPER IDs FROM RESPECTIVE TABLES ==========
        // These will be populated after insertion
        $patientIds = collect();
        $doctorIds = collect();
        $nurseIds = collect();
        $labScientistIds = collect();
        $clerkIds = collect();
        $accountantIds = collect();
        $pharmaIds = collect();
        $categoryIds = collect();
        $rateIds = collect();
        $drugStockIds = collect();
        $labStockIds = collect();
        $diagnosisIds = collect();
        $paymentIds = collect();
        $saleIds = collect();
        $labTestIds = collect();

        // ========== USERS TABLE ==========
        $users = [];
        $userRoles = ['patient', 'doctor', 'nurse', 'labScientist', 'clerk', 'accountant', 'pharmasist', 'admin'];
        $usedEmails = [];
        $usedRegIDs = [];
        $usedPhoneNos = [];

        // Generate multiple users for each role
        for ($i = 1; $i <= 50; $i++) {
            $role = $userRoles[array_rand($userRoles)];
            $name = $this->generateName($role, $i);
            $email = strtolower(str_replace(' ', '.', $name)) . rand(100, 999) . '@hospital.com';
            $regID = $this->generateRegID($role, $i);
            $phone = '080' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);

            // Ensure uniqueness
            while (in_array($email, $usedEmails)) {
                $email = strtolower(str_replace(' ', '.', $name)) . rand(100, 999) . '@hospital.com';
            }
            while (in_array($regID, $usedRegIDs)) {
                $regID = $this->generateRegID($role, $i . rand(1, 99));
            }
            while (in_array($phone, $usedPhoneNos)) {
                $phone = '080' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
            }

            $usedEmails[] = $email;
            $usedRegIDs[] = $regID;
            $usedPhoneNos[] = $phone;

            $users[] = [
                'name' => $name,
                'email' => $email,
                'phone_no' => $phone,
                'regID' => $regID,
                'password' => Hash::make('password'),
                'profile_image' => 'default.png',
                'suspend' => rand(0, 10) > 8 ? '1' : '0',
                'user_role' => $role,
                'email_verified_at' => now(),
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        // Add specific admin user
        $users[] = [
            'name' => 'Administrator',
            'email' => 'admin@hospital.com',
            'phone_no' => '08012345678',
            'regID' => 'ADMIN001',
            'password' => Hash::make('password'),
            'profile_image' => 'default.png',
            'suspend' => '0',
            'user_role' => 'admin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('users')->insert($users);
        $this->command->info('Users seeded: ' . count($users));

        // Get user IDs by role
        $patientUserIds = DB::table('users')->where('user_role', 'patient')->pluck('id');
        $doctorUserIds = DB::table('users')->where('user_role', 'doctor')->pluck('id');
        $nurseUserIds = DB::table('users')->where('user_role', 'nurse')->pluck('id');
        $labUserIds = DB::table('users')->where('user_role', 'labScientist')->pluck('id');
        $clerkUserIds = DB::table('users')->where('user_role', 'clerk')->pluck('id');
        $accountantUserIds = DB::table('users')->where('user_role', 'accountant')->pluck('id');
        $pharmaUserIds = DB::table('users')->where('user_role', 'pharmasist')->pluck('id');
        $allUserIds = DB::table('users')->pluck('id');

        // ========== PATIENTS TABLE ==========
        $patients = [];
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $genotypes = ['AA', 'AS', 'SS', 'AC'];
        $insuranceIds = ['INS001', 'INS002', 'INS003', 'INS004', 'INS005', null];

        foreach ($patientUserIds as $index => $userId) {
            $patients[] = [
                'user_id' => $userId,
                'blood_group' => $bloodGroups[array_rand($bloodGroups)],
                'genotype' => $genotypes[array_rand($genotypes)],
                'nos_name' => $this->generateName('emergency', $index),
                'nos_address' => $this->generateAddress(),
                'nos_phone_no' => '080' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'insurance_id' => $insuranceIds[array_rand($insuranceIds)],
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('patients')->insert($patients);
        $patientIds = DB::table('patients')->pluck('id');
        $this->command->info('Patients seeded: ' . count($patients));

        // ========== DOCTORS TABLE ==========
        $doctors = [];
        $specializations = ['Cardiology', 'Neurology', 'Pediatrics', 'Orthopedics', 'Gynecology', 'Dermatology', 'Psychiatry', 'Ophthalmology', 'ENT', 'Urology'];
        $levels = ['Senior Consultant', 'Consultant', 'Resident', 'Intern', 'Fellow'];

        foreach ($doctorUserIds as $userId) {
            $doctors[] = [
                'user_id' => $userId,
                'license_id' => 'LIC' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'level' => $levels[array_rand($levels)],
                'leave_days' => (string) rand(10, 30),
                'specialization' => $specializations[array_rand($specializations)],
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('doctors')->insert($doctors);
        $doctorIds = DB::table('doctors')->pluck('id');
        $this->command->info('Doctors seeded: ' . count($doctors));

        // ========== NURSES TABLE ==========
        $nurses = [];
        $departments = ['Emergency', 'ICU', 'Surgery', 'Maternity', 'Pediatrics', 'Cardiology', 'Neurology', 'Oncology'];

        foreach ($nurseUserIds as $userId) {
            $nurses[] = [
                'user_id' => $userId,
                'license_id' => 'NUR' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'level' => $levels[array_rand($levels)],
                'leave_days' => (string) rand(10, 25),
                'department' => $departments[array_rand($departments)],
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('nurses')->insert($nurses);
        $nurseIds = DB::table('nurses')->pluck('id');
        $this->command->info('Nurses seeded: ' . count($nurses));

        // ========== LAB SCIENTISTS TABLE ==========
        $labScientists = [];
        $labSpecializations = ['Clinical Pathology', 'Microbiology', 'Hematology', 'Biochemistry', 'Immunology', 'Molecular Biology'];

        foreach ($labUserIds as $userId) {
            $labScientists[] = [
                'user_id' => $userId,
                'license_id' => 'LAB' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'leave_days' => (string) rand(10, 20),
                'specialization' => $labSpecializations[array_rand($labSpecializations)],
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('lab_scientists')->insert($labScientists);
        $labScientistIds = DB::table('lab_scientists')->pluck('id');
        $this->command->info('Lab Scientists seeded: ' . count($labScientists));

        // ========== CLERKS TABLE ==========
        $clerks = [];
        foreach ($clerkUserIds as $userId) {
            $clerks[] = [
                'user_id' => $userId,
                'leave_days' => (string) rand(10, 20),
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('clerks')->insert($clerks);
        $clerkIds = DB::table('clerks')->pluck('id');
        $this->command->info('Clerks seeded: ' . count($clerks));

        // ========== ACCOUNTANTS TABLE ==========
        $accountants = [];
        foreach ($accountantUserIds as $userId) {
            $accountants[] = [
                'user_id' => $userId,
                'leave_days' => (string) rand(10, 20),
                'level' => $levels[array_rand($levels)],
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('accountants')->insert($accountants);
        $accountantIds = DB::table('accountants')->pluck('id');
        $this->command->info('Accountants seeded: ' . count($accountants));

        // ========== PHARMASISTS TABLE ==========
        $pharmasists = [];
        $pharmaSpecializations = ['Clinical Pharmacy', 'Industrial Pharmacy', 'Community Pharmacy', 'Hospital Pharmacy'];

        foreach ($pharmaUserIds as $userId) {
            $pharmasists[] = [
                'user_id' => $userId,
                'license_id' => 'PHA' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT),
                'specialization' => $pharmaSpecializations[array_rand($pharmaSpecializations)],
                'leave_days' => (string) rand(10, 20),
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('pharmasists')->insert($pharmasists);
        $pharmaIds = DB::table('pharmasists')->pluck('id');
        $this->command->info('Pharmasists seeded: ' . count($pharmasists));

        // ========== CATEGORIES TABLE ==========
        $categories = [
            ['name' => 'Antibiotics', 'description' => 'Medications used to treat bacterial infections', 'type' => 'drug'],
            ['name' => 'Pain Relievers', 'description' => 'Medications for pain management', 'type' => 'drug'],
            ['name' => 'Blood Tests', 'description' => 'Laboratory blood analysis tests', 'type' => 'lab'],
            ['name' => 'Medical Equipment', 'description' => 'Medical accessories and equipment', 'type' => 'accessories'],
            ['name' => 'Vitamins', 'description' => 'Dietary supplements and vitamins', 'type' => 'drug'],
            ['name' => 'Imaging Services', 'description' => 'X-ray, MRI, CT scan services', 'type' => 'lab'],
            ['name' => 'Antimalarials', 'description' => 'Medications for malaria treatment', 'type' => 'drug'],
            ['name' => 'Antihypertensives', 'description' => 'Blood pressure medications', 'type' => 'drug'],
            ['name' => 'Antidiabetics', 'description' => 'Diabetes medications', 'type' => 'drug'],
            ['name' => 'Lab Consumables', 'description' => 'Laboratory consumables and supplies', 'type' => 'lab'],
        ];

        DB::table('categories')->insert($categories);
        $categoryIds = DB::table('categories')->pluck('id');
        $this->command->info('Categories seeded: ' . count($categories));

        // ========== RATES TABLE ==========
        $rates = [
            ['title' => 'General Consultation', 'amount' => '5000','rate_type'=>'consultation'],
            ['title' => 'Specialist Consultation', 'amount' => '10000','rate_type'=>'consultation'],
            ['title' => 'Emergency Consultation', 'amount' => '15000','rate_type'=>'consultation'],
            ['title' => 'Ward Admission (per day)', 'amount' => '25000','rate_type'=>'labTest'],
            ['title' => 'ICU Admission (per day)', 'amount' => '50000','rate_type'=>'labTest'],
            ['title' => 'Blood Test - Complete', 'amount' => '8000','rate_type'=>'labTest'],
            ['title' => 'Blood Test - Basic', 'amount' => '4000','rate_type'=>'labTest'],
            ['title' => 'X-Ray', 'amount' => '12000','rate_type'=>'labTest'],
            ['title' => 'patient enrollment', 'amount' => '11500','rate_type'=>'enrollment'],
            ['title' => 'MRI Scan', 'amount' => '50000','rate_type'=>'labTest'],
            ['title' => 'CT Scan', 'amount' => '35000','rate_type'=>'labTest'],
        ];

        DB::table('rates')->insert($rates);
        $rateIds = DB::table('rates')->pluck('id');
        $this->command->info('Rates seeded: ' . count($rates));

        // ========== DRUG STOCKS TABLE ==========
        $drugNames = [
            ['Amoxicillin', 'Amoxicillin Trihydrate', 'Antibiotics'],
            ['Ciprofloxacin', 'Ciprofloxacin HCl', 'Antibiotics'],
            ['Paracetamol', 'Acetaminophen', 'Pain Relievers'],
            ['Ibuprofen', 'Ibuprofen', 'Pain Relievers'],
            ['Artemether/Lumefantrine', 'Artemether and Lumefantrine', 'Antimalarials'],
            ['Amlodipine', 'Amlodipine Besylate', 'Antihypertensives'],
            ['Metformin', 'Metformin Hydrochloride', 'Antidiabetics'],
            ['Vitamin C', 'Ascorbic Acid', 'Vitamins'],
            ['Vitamin D', 'Cholecalciferol', 'Vitamins'],
            ['Omeprazole', 'Omeprazole', 'Antibiotics'],
        ];

        $drugs = [];
        $statuses = ['inStock', 'lowStock', 'outOfStock'];

        foreach ($drugNames as $drug) {
            $category = DB::table('categories')->where('name', $drug[2])->first();
            $drugs[] = [
                'category_id' => $category ? $category->id : 1,
                'name' => $drug[0],
                'generic' => $drug[1],
                'amount' => (string) rand(200, 5000),
                'status' => $statuses[array_rand($statuses)],
                'quantity' => (string) rand(50, 2000),
                'description' => $this->generateDrugDescription($drug[0]),
                'expiry_date_range' => Carbon::now()->addMonths(rand(6, 24))->format('Y-m-d'),
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('drug_stocks')->insert($drugs);
        $drugStockIds = DB::table('drug_stocks')->pluck('id');
        $this->command->info('Drug Stocks seeded: ' . count($drugs));

        // ========== LAB STOCKS TABLE ==========
        $labStockItems = [
            ['Blood Glucose Test Strips', 'For diabetes monitoring'],
            ['COVID-19 Test Kits', 'Rapid antigen test kits'],
            ['X-Ray Films', 'For X-ray imaging'],
            ['Microscope Slides', 'For microscopic examination'],
            ['Centrifuge Tubes', 'For sample processing'],
            ['Pipette Tips', 'For laboratory measurements'],
            ['Reagent Grade Water', 'Ultra pure water for lab use'],
            ['PCR Master Mix', 'For molecular diagnostics'],
        ];

        $labStocks = [];
        $labCategories = DB::table('categories')->where('type', 'lab')->pluck('id');

        foreach ($labStockItems as $item) {
            $labStocks[] = [
                'category_id' => $labCategories->random(),
                'name' => $item[0],
                'amount' => (string) rand(500, 20000),
                'status' => $statuses[array_rand($statuses)],
                'quantity' => (string) rand(50, 500),
                'description' => $item[1],
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('lab_stocks')->insert($labStocks);
        $labStockIds = DB::table('lab_stocks')->pluck('id');
        $this->command->info('Lab Stocks seeded: ' . count($labStocks));

        // ========== DIAGNOSES TABLE ==========
        $diagnoses = [];
        $diagnosisDescriptions = [
            'Patient presents with fever and headache',
            'Patient complains of chest pain and shortness of breath',
            'Patient has persistent cough and fever',
            'Patient reports abdominal pain and nausea',
            'Patient experiencing dizziness and fatigue',
            'Patient has skin rash and itching',
            'Patient complains of joint pain and swelling',
            'Patient has difficulty breathing and wheezing',
        ];

        $wardStatuses = ['inPatient', 'outPatient'];
        $complaints = [
            'Severe headache, fever, body aches',
            'Chest pain radiating to left arm, shortness of breath',
            'Productive cough, fever, fatigue',
            'Abdominal pain, nausea, vomiting',
            'Dizziness, fatigue, palpitations',
            'Red rash, itching, swelling',
            'Joint pain, stiffness, swelling',
            'Wheezing, shortness of breath, chest tightness',
        ];

        $diagnosisNames = [
            'Acute Malaria',
            'Stable Angina',
            'Pneumonia',
            'Acute Gastroenteritis',
            'Hypertension',
            'Allergic Reaction',
            'Rheumatoid Arthritis',
            'Asthma',
        ];

        for ($i = 0; $i < 100; $i++) {
            $index = array_rand($diagnosisDescriptions);
            $diagnoses[] = [
                'patient_id' => $patientIds->random(),
                'doctor_id' => $doctorIds->random(),
                'description' => $diagnosisDescriptions[$index],
                'body_vitals' => json_encode([
                    'bp' => rand(110, 160) . '/' . rand(70, 100),
                    'pulse' => (string) rand(60, 100),
                    'temp' => (string) rand(360, 390) / 10,
                    'respiratory_rate' => (string) rand(12, 24),
                ]),
                'ward_status' => $wardStatuses[array_rand($wardStatuses)],
                'patients_complain' => $complaints[$index],
                'initial_diagnosis' => $diagnosisNames[$index],
                'final_diagnosis' => $index % 3 === 0 ? $diagnosisNames[array_rand($diagnosisNames)] : $diagnosisNames[$index],
                'created_at' => Carbon::now()->subDays(rand(1, 180)),
                'updated_at' => now(),
            ];
        }

        DB::table('diagnoses')->insert($diagnoses);
        $diagnosisIds = DB::table('diagnoses')->pluck('id');
        $this->command->info('Diagnoses seeded: ' . count($diagnoses));

        // ========== LAB TESTS TABLE ==========
        $labTestNames = [
            'Complete Blood Count',
            'Malaria Blood Smear',
            'Blood Glucose Test',
            'Liver Function Test',
            'Kidney Function Test',
            'Thyroid Function Test',
            'Lipid Profile',
            'Urinalysis',
            'Pregnancy Test',
            'COVID-19 PCR Test',
        ];

        $labTests = [];
        $progressStatuses = ['undone', 'inProgress', 'completed'];
        $paymentStatuses = ['unpaid', 'paid'];

        foreach ($diagnosisIds as $diagnosisId) {
            $patientId = DB::table('diagnoses')->where('id', $diagnosisId)->value('patient_id');
            $numTests = rand(1, 3);
            for ($i = 0; $i < $numTests; $i++) {
                $labTests[] = [
                    'diagnosis_id' => $diagnosisId,
                    'lab_test_name' => $labTestNames[array_rand($labTestNames)],
                    'lab_test_description' => 'Laboratory analysis for ' . $labTestNames[array_rand($labTestNames)],
                    'patient_id' => $patientId,
                    'payment_id' => null,
                    'lab_test_amount' => (string) rand(2000, 15000),
                    'lab_test_result' => $i % 2 === 0 ? $this->generateLabResult() : null,
                    'lab_test_result_image' => null,
                    'lab_test_payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                    'lab_test_progress_status' => $progressStatuses[array_rand($progressStatuses)],
                    'lab_scientist_id' => $labScientistIds->random(),
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('lab_tests')->insert($labTests);
        $labTestIds = DB::table('lab_tests')->pluck('id');
        $this->command->info('Lab Tests seeded: ' . count($labTests));

        // ========== AWAITING CONSULTATIONS TABLE ==========
        $consultations = [];
        $attendanceStatuses = ['unseen', 'waiting', 'seen'];

        foreach ($patientIds as $patientId) {
            $consultations[] = [
                'patient_id' => $patientId,
                'doctor_id' => $doctorIds->random(),
                'rates_id' => $rateIds->random(),
                'payment_id' => null,
                'diagnosis_id' => $diagnosisIds->random(),
                'payment_status' => $paymentStatuses[array_rand($paymentStatuses)],
                'amount' => (string) rand(5000, 50000),
                'attendance_status' => $attendanceStatuses[array_rand($attendanceStatuses)],
                'created_at' => Carbon::now()->subDays(rand(1, 180)),
                'updated_at' => now(),
            ];
        }

        DB::table('awaiting_consultations')->insert($consultations);
        $this->command->info('Consultations seeded: ' . count($consultations));

        // ========== PAYMENTS TABLE ==========
        $payments = [];
        $paymentTypes = ['consultation', 'drugSales', 'labTest', 'enrollment','labstock','drugStock'];
        $paymentStatuses = ['credit', 'debit'];

        for ($i = 0; $i < 200; $i++) {
            $payments[] = [
                'title' => $paymentTypes[array_rand($paymentTypes)] . ' payment',
                'description' => 'Payment for ' . $paymentTypes[array_rand($paymentTypes)],
                'payment_type' => $paymentTypes[array_rand($paymentTypes)],
                'rates_id' => $rateIds->random(),
                'patient_user_id' => $patientUserIds->random(),
                'signed_accountant_id' => $accountantIds->random(),
                'invoice_id' => 'INV' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'amount' => (string) rand(1000, 100000),
                'status' => $paymentStatuses[array_rand($paymentStatuses)],
                'created_at' => Carbon::now()->subDays(rand(1, 180)),
                'updated_at' => now(),
            ];
        }

        DB::table('payments')->insert($payments);
        $paymentIds = DB::table('payments')->pluck('id');
        $this->command->info('Payments seeded: ' . count($payments));

        // ========== SALES TABLE ==========
        $sales = [];
        $deliveryStatuses = ['unissued', 'pending', 'delivered'];
        $paymentSalesStatuses = ['paid', 'unpaid'];

        for ($i = 0; $i < 100; $i++) {
            $sales[] = [
                'diagnosis_id' => $diagnosisIds->random(),
                'pharmasist_id' => $pharmaIds->random(),
                'patient_id' => $patientIds->random(),
                'payment_id' => $paymentIds->random(),
                'total_amount' => (string) rand(1000, 50000),
                'payment_status' => $paymentSalesStatuses[array_rand($paymentSalesStatuses)],
                'delivery_status' => $deliveryStatuses[array_rand($deliveryStatuses)],
                'created_at' => Carbon::now()->subDays(rand(1, 180)),
                'updated_at' => now(),
            ];
        }

        DB::table('sales')->insert($sales);
        $saleIds = DB::table('sales')->pluck('id');
        $this->command->info('Sales seeded: ' . count($sales));

        // ========== DRUG SALES TABLE ==========
        $drugSales = [];
        $dosages = ['500mg', '250mg', '1000mg', '750mg', '100mg', '50mg'];
        $drugStatuses = ['active', 'cancelled', 'completed'];

        foreach ($saleIds as $saleId) {
            $numDrugs = rand(1, 4);
            for ($i = 0; $i < $numDrugs; $i++) {
                $drugSales[] = [
                    'sales_id' => $saleId,
                    'drug_stock_id' => $drugStockIds->random(),
                    'quantity' => (string) rand(1, 30),
                    'dosage' => $dosages[array_rand($dosages)],
                    'status' => $drugStatuses[array_rand($drugStatuses)],
                    'unit_price' => (string) rand(100, 5000),
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('drug_sales')->insert($drugSales);
        $this->command->info('Drug Sales seeded: ' . count($drugSales));

        // ========== PATIENT TEST TABLE ==========
        $patientTests = [];

        foreach ($labTestIds as $labTestId) {
            $numRates = rand(1, 3);
            for ($i = 0; $i < $numRates; $i++) {
                $patientTests[] = [
                    'rates_id' => $rateIds->random(),
                    'lab_tests_id' => $labTestId,
                    'remark' => $this->generateRemark(),
                    'amount' => (string) rand(1000, 10000),
                    'status' => $progressStatuses[array_rand($progressStatuses)],
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('patient_test')->insert($patientTests);
        $this->command->info('Patient Tests seeded: ' . count($patientTests));

        // ========== DIAGNOSIS REPORTS TABLE ==========
        $diagnosisReports = [];
        $reportTexts = [
            'Patient shows significant improvement. Continuing current treatment.',
            'Patient responding well to medication. Schedule follow-up in 2 weeks.',
            'Patient condition stable. Continue monitoring vital signs.',
            'Patient experiencing mild side effects. Adjusting medication dosage.',
            'Patient discharged with home care instructions.',
            'Patient requires additional tests for further evaluation.',
        ];

        foreach ($diagnosisIds as $diagnosisId) {
            if (rand(1, 10) <= 7) {
                $diagnosisReports[] = [
                    'diagnosis_id' => $diagnosisId,
                    'user_id' => $doctorUserIds->random(),
                    'diagnosis_report' => $reportTexts[array_rand($reportTexts)],
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('diagnosis_reports')->insert($diagnosisReports);
        $this->command->info('Diagnosis Reports seeded: ' . count($diagnosisReports));

        // ========== STOCK REQUESTS TABLE ==========
        $stockRequests = [];
        $requestStatuses = ['pending', 'approved', 'rejected'];
        $requestTypes = ['drug_stock_id', 'lab_stock_id'];

        for ($i = 0; $i < 50; $i++) {
            $type = $requestTypes[array_rand($requestTypes)];
            $stockRequests[] = [
                'drug_stock_id' => $type === 'drug_stock_id' ? $drugStockIds->random() : null,
                'user_id' => $pharmaUserIds->random(),
                'lab_stock_id' => $type === 'lab_stock_id' ? $labStockIds->random() : null,
                'quantity' => (string) rand(50, 1000),
/*                'payment_id' => (string) rand(1, 100),*/
                'unit_price' => (string) rand(100, 5000),
                'title' => 'Stock request for ' . $this->generateName('product', $i),
                'status' => $requestStatuses[array_rand($requestStatuses)],
                'created_at' => Carbon::now()->subDays(rand(1, 180)),
                'updated_at' => now(),
            ];
        }

        DB::table('stock_requests')->insert($stockRequests);
        $this->command->info('Stock Requests seeded: ' . count($stockRequests));

        // ========== LEAVE APPLICATIONS TABLE ==========
        $leaveApplications = [];
        $leaveStatuses = ['pending', 'approved', 'rejected'];

        foreach ($allUserIds as $userId) {
            if (rand(1, 10) <= 4) {
                $leaveApplications[] = [
                    'user_id' => $userId,
                    'days_requested' => (string) rand(1, 30),
                    'resumption_date' => Carbon::now()->addDays(rand(7, 60))->format('Y-m-d'),
                    'remark' => $this->generateRemark(),
                    'status' => $leaveStatuses[array_rand($leaveStatuses)],
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('leave_applications')->insert($leaveApplications);
        $this->command->info('Leave Applications seeded: ' . count($leaveApplications));

        // ========== PATIENT COMPLAINS TABLE ==========
        $patientComplains = [];
        $complaintTexts = [
            'Severe headache and nausea',
            'Persistent cough and fever',
            'Abdominal pain and diarrhea',
            'Chest pain and difficulty breathing',
            'Joint pain and swelling',
            'Skin rash and itching',
        ];

        foreach ($patientIds as $patientId) {
            if (rand(1, 10) <= 3) {
                $patientComplains[] = [
                    'patient_id' => $patientId,
                    'diagnosis_id' => $diagnosisIds->random(),
                    'complaint' => $complaintTexts[array_rand($complaintTexts)],
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('patient_complains')->insert($patientComplains);
        $this->command->info('Patient Complains seeded: ' . count($patientComplains));

        // ========== SALARY ALLOWANCES TABLE ==========
        $salaryAllowances = [];
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        foreach ($allUserIds as $userId) {
            if (rand(1, 10) <= 3) {
                $salaryAllowances[] = [
                    'user_id' => $userId,
                    'payment_id' => $paymentIds->random(),
                    'month' => $months[array_rand($months)],
                    'year' => (string) rand(2023, 2026),
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('salary_allowances')->insert($salaryAllowances);
        $this->command->info('Salary Allowances seeded: ' . count($salaryAllowances));

        // ========== REPORTS TABLE ==========
        $reports = [];
        $reportUnits = ['Finance Department', 'Pharmacy Department', 'Lab Department', 'HR Department', 'Medical Records'];
        $reportTitles = [
            'Monthly Revenue Report',
            'Pharmacy Stock Report',
            'Lab Test Analysis',
            'Staff Attendance Report',
            'Patient Satisfaction Survey',
            'Annual Financial Statement',
            'Drug Utilization Report',
            'Infection Control Report',
        ];

        for ($i = 0; $i < 20; $i++) {
            $reports[] = [
                'title' => $reportTitles[array_rand($reportTitles)],
                'unit' => $reportUnits[array_rand($reportUnits)],
                'description' => $this->generateReportDescription(),
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('reports')->insert($reports);
        $this->command->info('Reports seeded: ' . count($reports));

        // ========== IP MODELS TABLE ==========
        $ipModels = [];
        for ($i = 0; $i < 10; $i++) {
            $ipModels[] = [
                'ip_address' => rand(10, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255),
                'label' => 'Server ' . ($i + 1) . ' - ' . $this->generateName('server', $i),
                'created_at' => Carbon::now()->subDays(rand(1, 365)),
                'updated_at' => now(),
            ];
        }

        DB::table('i_p_models')->insert($ipModels);
        $this->command->info('IP Models seeded: ' . count($ipModels));
    }

    // ========== HELPER FUNCTIONS ==========

    private function generateName($role, $index): string
    {
        $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'James', 'Mary', 'Robert', 'Patricia', 'William', 'Jennifer', 'Richard', 'Linda', 'Joseph', 'Barbara', 'Thomas', 'Elizabeth', 'Charles', 'Susan'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee'];

        if ($role === 'emergency') {
            return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        }

        if ($role === 'doctor') {
            return 'Dr. ' . $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        }

        if ($role === 'nurse') {
            return 'Nurse ' . $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
        }

        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }

    private function generateRegID($role, $index): string
    {
        $prefixes = [
            'patient' => 'PAT',
            'doctor' => 'DOC',
            'nurse' => 'NRS',
            'labScientist' => 'LAB',
            'clerk' => 'CLK',
            'accountant' => 'ACC',
            'pharmasist' => 'PHA',
            'admin' => 'ADMIN'
        ];

        $prefix = $prefixes[$role] ?? 'USR';
        return $prefix . str_pad($index, 3, '0', STR_PAD_LEFT);
    }

    private function generateAddress(): string
    {
        $streetNames = ['Main', 'Park', 'Oak', 'Pine', 'Maple', 'Cedar', 'Elm', 'Lake', 'Hill', 'Forest'];
        $streetTypes = ['St', 'Ave', 'Rd', 'Blvd', 'Ln', 'Dr'];
        $cities = ['Lagos', 'Abuja', 'Port Harcourt', 'Ibadan', 'Kano', 'Enugu', 'Calabar', 'Warri'];

        return rand(1, 999) . ' ' . $streetNames[array_rand($streetNames)] . ' ' . $streetTypes[array_rand($streetTypes)] . ', ' . $cities[array_rand($cities)] . ', Nigeria';
    }

    private function generateDrugDescription($name): string
    {
        $descriptions = [
            'Amoxicillin' => 'Broad-spectrum antibiotic for bacterial infections',
            'Ciprofloxacin' => 'Fluoroquinolone antibiotic for various infections',
            'Paracetamol' => 'Pain reliever and fever reducer',
            'Ibuprofen' => 'NSAID for pain and inflammation',
            'Artemether/Lumefantrine' => 'Antimalarial medication for uncomplicated malaria',
            'Amlodipine' => 'Calcium channel blocker for hypertension',
            'Metformin' => 'First-line medication for type 2 diabetes',
            'Vitamin C' => 'Immune system support and antioxidant',
            'Vitamin D' => 'Essential for bone health and immune function',
            'Omeprazole' => 'Proton pump inhibitor for acid reflux',
        ];

        return $descriptions[$name] ?? 'Medication for treatment of various conditions';
    }

    private function generateLabResult(): string
    {
        $results = [
            'Normal range - No abnormalities detected',
            'Elevated white blood cell count - Possible infection',
            'Low hemoglobin - Anemia detected',
            'Positive for malaria parasites',
            'Negative for malaria parasites',
            'Elevated glucose levels - Diabetes risk',
            'Normal liver function test results',
            'Elevated cholesterol levels',
            'Normal kidney function test results',
            'Positive for COVID-19 PCR',
            'Negative for COVID-19 PCR',
            'Normal urinalysis results',
        ];

        return $results[array_rand($results)];
    }

    private function generateRemark(): string
    {
        $remarks = [
            'Follow-up required in 2 weeks',
            'Immediate attention needed',
            'Results within normal limits',
            'Slight deviation from normal range',
            'Close monitoring required',
            'Patient to be informed of results',
            'Further testing required',
            'Urgent referral to specialist',
            'Results pending confirmation',
            'Repeat test recommended',
        ];

        return $remarks[array_rand($remarks)];
    }

    private function generateReportDescription(): string
    {
        $descriptions = [
            'Comprehensive analysis of department performance',
            'Detailed breakdown of financial transactions',
            'Statistical analysis of patient data',
            'Inventory management and stock utilization report',
            'Quality assurance and improvement metrics',
            'Staff productivity and performance review',
            'Patient satisfaction survey results',
            'Annual departmental performance summary',
        ];

        return $descriptions[array_rand($descriptions)];
    }
}
