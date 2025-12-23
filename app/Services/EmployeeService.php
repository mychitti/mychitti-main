<?php

namespace App\Services;

use App\Traits\FileManagerTrait;

class EmployeeService
{
    use FileManagerTrait;

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
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'zone_id' => $request->zone_id,
            'email' => $request->email,
            'role_id' => $request->role_id,
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
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'zone_id' => $request->zone_id,
            'email' => $request->email,
            'role_id' => $request->role_id,
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
