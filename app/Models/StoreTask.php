<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class StoreTask extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'parent_id', 'form_submission_id'];

    public function user()
    {
        return $this->belongsTo(StoreCustomer::class);
    }
    public function employee()
    {
        return $this->belongsTo(VendorEmployee::class);
    }
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function offeredTo()
    {
        return $this->belongsTo(VendorEmployee::class, 'offered_to');
    }
    public function taskCategory()
    {
        return $this->belongsTo(TaskSalaryCategory::class, 'task_salary_category');
    }

    public function children()
    {
        return $this->hasMany(StoreTask::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(StoreTask::class, 'parent_id');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }
    public function jobcard()
    {
        return $this->hasOne(JobCard::class, 'task_id', 'id');
    }
    public function recievableReciept()
    {
        return $this->hasOne(ReceivableReceipt::class, 'task_id', 'id');
    }
    public function invoice()
    {
        return $this->hasOne(ManualInvoice::class, 'task_id', 'id');
    }
    public function quotation()
    {
        return $this->hasOne(Quotation::class, 'task_id', 'id');
    }

    public function formSubmission()
    {
        return $this->belongsTo(FormSubmission::class, 'form_submission_id');
    }

    public function getDynamicFieldsWithLabels()
    {
        if (!$this->formSubmission || !$this->formSubmission->form) {
            return [];
        }

        $submissionData = json_decode($this->formSubmission->data, true);
        $form = $this->formSubmission->form;
        $formFields = json_decode($form->fields, true);
        $formFieldsById = collect($formFields)->keyBy('id');

        $result = [];

        foreach ($submissionData as $key => $value) {
            if (preg_match('/^field_(\d+)$/', $key, $matches)) {
                $fieldId = $matches[1];

                if (isset($formFieldsById[$fieldId])) {
                    $field = $formFieldsById[$fieldId];

                    $result[] = [
                        'label' => $field['label'] ?? $field['name'] ?? $key,
                        'value' => $value,
                        'type' => $field['type'] ?? 'text',
                    ];
                }
            }
        }

        return $result;
    }
}
