<?php

namespace App\Services;

use App\CentralLogics\Helpers;
use App\Traits\FileManagerTrait;

class EmployeeService
{
    use FileManagerTrait;

    private function buildEducation($request): ?string 
    {
        $education = [];
        if ($request->has('school_name') && $request->school_name[0]) {
            foreach ($request->school_name as $key => $value) {
                $education[] = [
                    'school_name' => $request->school_name[$key],
                    'degree_diploma' => $request->degree_diploma[$key], 
                    'field_of_study' => $request->field_of_study[$key],
                    'start_month' => $request->start_month[$key], 
                    'end_month' => $request->end_month[$key],
                    'additional_notes' => $request->additional_notes[$key],
                ];
            }
        }
        return $education ? json_encode($education) : null;
    }

    private function buildExperience($request, $isUpdate = false): ?string
    {
        $experience = [];
        if ($request->has('occupation') && $request->occupation[0]) {
            foreach ($request->occupation as $key => $value) {
                $experience_letter = null;
                if ($request->hasFile("experience_letter.$key")) {
                    $file = $request->file("experience_letter.$key");
                    $extension = $file->getClientOriginalExtension();
                    $experience_letter = Helpers::upload('admin/emp_documents/', $extension, $file);
                } elseif ($isUpdate && $request->has('existing_exp_letter') && isset($request->existing_exp_letter[$key])) {
                    $experience_letter = $request->existing_exp_letter[$key];
                }
                $experience[] = [
                    'occupation' => $request->occupation[$key],
                    'company_name' => $request->company_name[$key],
                    'summary' => $request->summary[$key],
                    'exp_start_date' => $request->exp_start_date[$key],
                    'currently_working' => $request->currently_working[$key + 1] ?? 0,
                    'exp_end_date' => $request->exp_end_date[$key] ?? null,
                    'exp_letter' => $experience_letter,
                ];
            }
        }
        return $experience ? json_encode($experience) : null;
    }

    public function getAddData(Object $request): array
    {
        $documents = [];
        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $file) {
                $extension = $file->getClientOriginalExtension();
                $doc_name = $this->upload('admin/emp_documents/', $extension, $file);
                $documents[]=$doc_name;
            }
        }
        return [
            'employee_id' => _newEmpId(true),
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'zone_id' => $request->zone_id,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'salary_type' => $request->salary_type,
            'base_salary' => $request->salary_type == 'Task-Wise' ? 0 : ($request->base_salary ?? 0),
            'skills' => ($request->has('skills') && count($request->skills)) ? implode(',', $request->skills) : null,
            'store_shift_id' => $request->shift,
            'education' => $this->buildEducation($request),
            'experience' => $this->buildExperience($request),
            'password' => bcrypt($request->password),
            'image' => $this->upload('admin/', 'png', $request->file('image')),
            'documents' => json_encode($documents),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    public function getUpdateData(Object $request, object $employee): array
    {
        $updated_docs =  json_decode($employee->documents);
        if (!empty($request->file('documents'))) {
            foreach ($request->documents as $file) {
                $extension = $file->getClientOriginalExtension();
                $doc_name = $this->upload('vendor/documents/', $extension, $file);
                $documents[]=$doc_name;
            }
          $updated_docs =  array_merge(json_decode($employee->documents), $documents);
        }

        if ($request['password'] == null) {
            $pass = $employee['password'];
        } else {
            $pass = bcrypt($request['password']);
            $employee->remember_token=null;
        }

        if ($request->has('image')) {
            $employee['image'] = $this->updateAndUpload('admin/', $employee->image, 'png', $request->file('image'));
        }
        return [
            'employee_id' => $employee->employee_id ?: _newEmpId(true),
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'zone_id' => $request->zone_id,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'salary_type' => $request->salary_type,
            'base_salary' => $request->salary_type == 'Task-Wise' ? null : $request->base_salary,
            'skills' => ($request->has('skills') && count($request->skills)) ? implode(',', $request->skills) : null,
            'store_shift_id' => $request->shift,
            'education' => $this->buildEducation($request) ?? $employee['education'],
            'experience' => $this->buildExperience($request, true) ?? $employee['experience'],
            'password' => $pass,
            'image' => $employee['image'],
            'documents' => json_encode($updated_docs),
            'updated_at' => now(),
            'is_logged_in' => 0,
        ];
    }
    public function adminCheck(Object $employee): array
    {
        if (auth('admin')->id()  != $employee['id']){
            return ['flag' => 'unauthorized'];
        }
        return ['flag' => 'authorized'];
    }

}
