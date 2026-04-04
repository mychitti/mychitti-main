<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\Form;

class FormBuilderController extends Controller
{
    public function saveFields(Request $request)
    {
        // prx($request->all());;
        try {
            // Validate the request
            $request->validate([
                'structure' => 'required|json',
                'form_id' => 'nullable|integer|exists:forms,id',
                'name' => 'nullable|string|max:255'
            ]);

            $storeId = Helpers::get_store_id();
            $formName = $request->form_name == 'project_task_form' ? 'Project Task Form' : 'Task Form';
            $formType = $request->form_name;

            // Update existing form
            if ($request->filled('form_id')) {
                $form = Form::where('id', $request->form_id)
                    ->where('store_id', $storeId)
                    ->first();

                if ($form) {
                    $form->update([
                        'structure' => $request->structure,
                        'updated_at' => now()
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => 'Form updated successfully',
                        'form_id' => $form->id
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Form not found'
                ], 404);
            }

            // Create or update form
            $form = Form::updateOrCreate(
                [
                    'form_type' => $formType,
                    'store_id' => $storeId
                ],
                [
                    'name' => $formName,
                    'structure' => $request->structure
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Form saved successfully',
                'form_id' => $form->id
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Form save error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving the form'
            ], 500);
        }
    }

    public function getForm(Request $request, $formType = null)
    {
        try {
            $storeId = Helpers::get_store_id();
            // In admin context get_store_id() returns 0 — use store_id from query param if provided
            if (!$storeId && $request->filled('store_id')) {
                $storeId = (int) $request->store_id;
            }
            $formName = $formType == 'project_task_form' ? 'Project Task Form' : 'Task Form';

            $form = Form::where('form_type', $formType)
                ->where('store_id', $storeId)
                ->first();

            if ($form) {
                // Decode structure if it's stored as JSON string
                $structure = is_string($form->structure)
                    ? json_decode($form->structure, true)
                    : $form->structure;

                return response()->json([
                    'success' => true,
                    'structure' => $structure,
                    'form_id' => $form->id,
                    'name' => $formName,
                    'form_type' => $formType,
                    'created_at' => $form->created_at,
                    'updated_at' => $form->updated_at
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No form found',
                'structure' => [],
                'form_id' => null
            ]);
        } catch (\Exception $e) {
            \Log::error('Form get error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the form',
                'structure' => [],
                'form_id' => null
            ], 500);
        }
    }


    public function deleteForm($formId)
    {
        try {
            $storeId = Helpers::get_store_id();

            $form = Form::where('id', $formId)
                ->where('store_id', $storeId)
                ->first();

            if (!$form) {
                return response()->json([
                    'success' => false,
                    'message' => 'Form not found'
                ], 404);
            }

            $form->delete();

            return response()->json([
                'success' => true,
                'message' => 'Form deleted successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Form delete error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the form'
            ], 500);
        }
    }
}
