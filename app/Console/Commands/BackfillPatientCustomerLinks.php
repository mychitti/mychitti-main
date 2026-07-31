<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\StoreCustomer;
use App\Services\PatientCustomerLink;
use Illuminate\Console\Command;

class BackfillPatientCustomerLinks extends Command
{
    protected $signature = 'hmis:link-patients
                            {--store= : Limit to one store id}
                            {--clients : Also give existing hospital clients a patient record}';

    protected $description = 'Link every patient to a store client record (and optionally the reverse for hospital stores)';

    public function handle(): int
    {
        if (!PatientCustomerLink::ensureSchema()) {
            $this->error('patients / store_customers not available — nothing to link.');
            return self::FAILURE;
        }

        // A patient row saved through the link fires the WhatsApp welcome on the client it
        // creates. These people registered long ago; a backfill must stay silent.
        StoreCustomer::$welcomeOnCreate = false;

        try {
            $this->linkPatients();

            if ($this->option('clients')) {
                $this->linkClients();
            }
        } finally {
            StoreCustomer::$welcomeOnCreate = true;
        }

        return self::SUCCESS;
    }

    private function linkPatients(): void
    {
        $query = Patient::whereNull('store_customer_id')->whereNotNull('store_id');
        if ($store = $this->option('store')) {
            $query->where('store_id', $store);
        }

        $total = (clone $query)->count();
        $this->info("Patients to link: {$total}");
        if (!$total) {
            return;
        }

        $bar    = $this->output->createProgressBar($total);
        $linked = 0;

        $query->orderBy('id')->chunkById(200, function ($patients) use (&$linked, $bar) {
            foreach ($patients as $patient) {
                if (PatientCustomerLink::fromPatient($patient)) {
                    $linked++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Linked {$linked} patient(s) to a client record.");
    }

    private function linkClients(): void
    {
        $query = StoreCustomer::where('user_type', 'customer')->whereNotNull('store_id');
        if ($store = $this->option('store')) {
            $query->where('store_id', $store);
        }

        $created = 0;
        $query->orderBy('id')->chunkById(200, function ($customers) use (&$created) {
            foreach ($customers as $customer) {
                if (!PatientCustomerLink::isHospitalStore($customer->store_id)) {
                    continue;
                }
                if (PatientCustomerLink::fromCustomer($customer)) {
                    $created++;
                }
            }
        });

        $this->info("Reconciled {$created} hospital client(s) into patient records.");
    }
}
