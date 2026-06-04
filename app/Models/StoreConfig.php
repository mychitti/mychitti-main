<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StoreConfig extends Model
{
    use HasFactory;
    protected $fillable = [
        'paid_unpaid_options',
        'token_prefix',
        'pos_token_template',
        'invoice_template',
        'store_id',
        'jurisdiction_statement', 
        'signature', 
        'invoice_sign_status',
        'jurisdiction_statement_status', 
        'tnc_quotation_status',
        'tnc_invoice_status', 
        'default_invoice_tnc_id',
        'bank_details_status',
        'quotation_footer_line',
        'jrsdctn_quote_status',
        'jrsdctn_quote_statement',
        'jurisdiction_statement_status', 
        'returnable_rr_tnc',
        'non_returnable_rr_tnc',
        'returnable_rr_tnc_content',
        'non_returnable_rr_tnc_content',
        'token_footer_line',
        'token_footer_line_status',
        'token_det_in_account',
        'delivery_charges_gst_pos',
        'delivery_charges_gst_percent',
        'pos_daybook_entry',
        'pos_account_entry',
        'address_on_token',
        'kitchen_token',
        'auto_invoice_pos',
        'pos_menu_design',
        'main_card',
        'branch_1_color',
        'branch_2_color',
        'branch_3_color',
        'code',
        'resubmit_per_req_form',
        'task_id_serial',
        'task_id_format',
        'close_task_with_otp',
        'task_quotation',
        'task_invoice',
        'task_recievable_receipt',
        'task_service_reports',
        'reminder_day_before',
        'monthly_maintnnce_req',
        'webpage_name',
        'webpage_email',
        'webpage_address',
        'webpage_phones',
        'webpage_latitude',
        'webpage_longitude',
        'category_position',
        'printer_type',
        'printer_ip',
        'printer_port',
        'printer_name',
        'printer_paper_width',
        'printer_auto_print',
        'leads_guide_dismissed',
        'default_dashboard',
        'patient_uid_prefix',
        'patient_uid_padding',
        'patient_uid_serial',
        'opd_consultation_count',
        'opd_consultation_validity_days',
    ];

    protected $table; 
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Schema::hasTable('storeConfigs') ? 'storeConfigs' : 'store_configs';
    }

    protected $guarded = ['id'];

    protected $casts = [
        'store_id' => 'integer',
        'is_recommended' => 'boolean',
        'is_recommended_deleted' => 'boolean',
    ];

    public function Store()
    {
        return $this->belongsTo(Store::class);
    }
}
