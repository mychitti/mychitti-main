<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Brian2694\Toastr\Facades\Toastr;


class FileController extends Controller
{
    public function add(Request $request)
    {
        $baseDir = 'uploaded';

        if (!Storage::disk('public')->exists($baseDir)) {
            Storage::disk('public')->makeDirectory($baseDir);
        }

        $data = [];

        // Get all folders inside uploaded/
        $folders = Storage::disk('public')->directories($baseDir);

        foreach ($folders as $folderPath) {

            $folderName = basename($folderPath);

            // Get files inside folder
            $files = Storage::disk('public')->files($folderPath);

            foreach ($files as $file) {
                $host = request()->getHost();

                // staging server (no storage:link usually)
                if ($host === 'staging.mychitti.net') {
                    $url =   asset('storage/app/public/' . $file);
                } else {
                    $url =   asset('storage/' . $file);
                }

                $data[] = (object) [
                    'folder' => $folderName,
                    'file'   => basename($file),
                    'path'   => $file,
                    'url'    => $url,
                    'ext'    => pathinfo($file, PATHINFO_EXTENSION),
                ];
            }
        }
        $folderList = array_map('basename', $folders);
        return view('admin-views.file.add', compact('folders', 'folderList', 'data'));
    }
public function clearUploadedFolder()
{
    $baseDir = 'uploaded';

    if (Storage::disk('public')->exists($baseDir)) {

        Storage::disk('public')->deleteDirectory($baseDir);

        Storage::disk('public')->makeDirectory($baseDir);
    }

    return true;
}
    public function store(Request $request)
    {
        $baseDir = 'uploaded';

        $request->validate([
            'folder' => 'required|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
            'file'   => 'required_without_all:other_file,multiple_files|file',
            'other_file' => 'required_without_all:file,multiple_files|file',
            'multiple_files.*' => 'file'
        ]);

        $folderName = trim($request->folder);
        $fullPath = $baseDir . '/' . $folderName;

        if (!Storage::disk('public')->exists($fullPath)) {
            Storage::disk('public')->makeDirectory($fullPath);
        }

        if ($request->hasFile('multiple_files')) {

            foreach ($request->file('multiple_files') as $file) {

                $extension = $file->getClientOriginalExtension();
                $originalName = $file->getClientOriginalName();

                Helpers::upload(
                    $fullPath,
                    $extension,
                    $file,
                    $originalName
                );
            }
        } elseif ($request->hasFile('other_file')) {

            $file = $request->file('other_file');

            Helpers::upload(
                $fullPath,
                $file->getClientOriginalExtension(),
                $file,
                $file->getClientOriginalName()
            );
        } else {

            $file = $request->file('file');

            Helpers::upload(
                $fullPath,
                $file->getClientOriginalExtension(),
                $file,
                $file->getClientOriginalName()
            );
        }

        Toastr::success(translate('File uploaded successfully!'));
        return back();
    }
}
