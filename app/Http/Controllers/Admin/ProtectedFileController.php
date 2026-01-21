<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\SecureFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class ProtectedFileController extends Controller
{
    public function download_file($file)
    {
        $dir = 'app/apis/';
        if (!Storage::disk('secure')->exists($dir . $file)) {
            abort(404);
        }
        return Storage::disk('secure')->download($dir .$file);
    }
    public function upload_file(Request $request)
    {
        $request->validate([
            'file' => 'required',
        ]);
        $extension = $request->file->getClientOriginalExtension();
        $file = Helpers::upload_to_secure('app/apis/', $extension, $request->file);

        $sFile = new SecureFile();
        $sFile->file_type = 'api';
        $sFile->name = $file ;
        $sFile->save();

         $data = [
            'user_id' => auth('admin')->id(),
            'user_type' => 'admin',
            'action' => 'uploaded secure file',
             'model_type' => 'SecureFile', 
                'model_id' =>  $sFile->id,
            'description' => 'Uploaded secure file (Id: ' . $sFile->id . ')',
        ];
        _actionLog($data);

        Toastr::success('File uploaded successfully');
        return back();
    }
}
