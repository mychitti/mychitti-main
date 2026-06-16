<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$storeId = \App\CentralLogics\Helpers::get_store_id() ?: 1; // Fallback to 1 if null
echo "Store ID: $storeId\n";

try {
    $appointmentsCount = \App\Models\Appointment::count();
    echo "Appointments: $appointmentsCount\n";
} catch (\Exception $e) {
    echo "Appointments Error: " . $e->getMessage() . "\n";
}
 
try {
    $doctorCount = \App\Models\DoctorProfile::count();
    echo "Doctors: $doctorCount\n";
} catch (\Exception $e) {
    echo "Doctors Error: " . $e->getMessage() . "\n";
}
 
try {
    $bedsCount = \App\Models\Bed::count();
    echo "Beds: $bedsCount\n";
} catch (\Exception $e) {
    echo "Beds Error: " . $e->getMessage() . "\n";
}

try {
    $consentTemplatesCount = \App\Models\ConsentTemplate::count();
    echo "Consent Templates: $consentTemplatesCount\n";
} catch (\Exception $e) {
    echo "Consent Templates Error: " . $e->getMessage() . "\n";
}
