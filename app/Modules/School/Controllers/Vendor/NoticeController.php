<?php

namespace App\Modules\School\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolNotice;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NoticeController extends Controller
{
    private function ensureSchema(): void
    {
        if (!Schema::hasTable('school_notices')) {
            DB::statement("CREATE TABLE IF NOT EXISTS school_notices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                store_id BIGINT UNSIGNED NOT NULL, branch_id BIGINT UNSIGNED NULL,
                title VARCHAR(190) NOT NULL, body TEXT NULL, notice_date DATE NULL,
                audience VARCHAR(20) DEFAULT 'all', school_class_id BIGINT UNSIGNED NULL,
                is_published TINYINT(1) DEFAULT 1, is_pinned TINYINT(1) DEFAULT 0,
                expires_on DATE NULL, created_by VARCHAR(150) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id), KEY idx_store (store_id, branch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }

    public function index(Request $request)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        $audience = $request->query('audience');
        $search   = trim($request->query('search', ''));

        // Show store-wide notices plus the active branch's notices (owner in "All" sees everything).
        $activeBranch = school_active_branch_id();

        $notices = SchoolNotice::where('store_id', $store_id)
            ->with(['schoolClass', 'branch'])
            ->when($activeBranch, fn($q) => $q->where(fn($w) => $w->whereNull('branch_id')->orWhere('branch_id', $activeBranch)))
            ->when($audience, fn($q) => $q->where('audience', $audience))
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->orderByDesc('is_pinned')->orderByDesc('notice_date')->orderByDesc('id')
            ->paginate(config('default_pagination'))->withQueryString();

        return view('school::vendor.notices.index', [
            'notices'   => $notices,
            'audiences' => SchoolNotice::AUDIENCES,
            'audience'  => $audience,
            'search'    => $search,
        ]);
    }

    public function create()
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        return view('school::vendor.notices.form', [
            'notice'    => null,
            'audiences' => SchoolNotice::AUDIENCES,
            'classes'   => SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get(),
            'branches'  => school_branches(),
            'canChooseBranch' => school_can_switch_branch() && school_branches()->count(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureSchema();
        $data = $this->validateNotice($request);
        $data['store_id']   = Helpers::get_store_id();
        $data['branch_id']  = $this->resolveNoticeBranch($request);
        $data['created_by'] = $this->currentUserName();
        $notice = SchoolNotice::create($data);

        if ($notice->is_published && $notice->audience !== 'staff') {
            $this->sendNoticeNotifications($notice);
        }

        Toastr::success('Notice published.');
        return redirect()->route('vendor.school.notices.index');
    }

    public function edit($id)
    {
        $this->ensureSchema();
        $store_id = Helpers::get_store_id();
        return view('school::vendor.notices.form', [
            'notice'    => SchoolNotice::where('store_id', $store_id)->findOrFail($id),
            'audiences' => SchoolNotice::AUDIENCES,
            'classes'   => SchoolClass::where('store_id', $store_id)->orderBy('numeric_order')->orderBy('id')->get(),
            'branches'  => school_branches(),
            'canChooseBranch' => school_can_switch_branch() && school_branches()->count(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->ensureSchema();
        $notice = SchoolNotice::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $notice->update($this->validateNotice($request) + ['branch_id' => $this->resolveNoticeBranch($request)]);
        Toastr::success('Notice updated.');
        return redirect()->route('vendor.school.notices.index');
    }

    public function toggle($id)
    {
        $this->ensureSchema();
        $notice = SchoolNotice::where('store_id', Helpers::get_store_id())->findOrFail($id);
        $notice->update(['is_published' => !$notice->is_published]);

        if ($notice->is_published && $notice->audience !== 'staff') {
            $this->sendNoticeNotifications($notice);
        }

        Toastr::success($notice->is_published ? 'Notice published.' : 'Notice unpublished.');
        return back();
    }

    public function delete($id)
    {
        SchoolNotice::where('store_id', Helpers::get_store_id())->where('id', $id)->delete();
        Toastr::success('Notice removed.');
        return back();
    }

    private function validateNotice(Request $request): array
    {
        $data = $request->validate([
            'title'           => 'required|string|max:190',
            'body'            => 'nullable|string',
            'notice_date'     => 'nullable|date',
            'audience'        => 'required|in:' . implode(',', array_keys(SchoolNotice::AUDIENCES)),
            'school_class_id' => 'nullable|integer',
            'expires_on'      => 'nullable|date',
        ]);
        $data['notice_date']  = $data['notice_date'] ?? now()->toDateString();
        $data['school_class_id'] = in_array($data['audience'], ['students', 'parents']) ? ($data['school_class_id'] ?: null) : null;
        $data['is_published'] = $request->boolean('is_published');
        $data['is_pinned']    = $request->boolean('is_pinned');
        return $data;
    }

    /** Store-wide (null) or a specific branch. Staff are pinned to their own branch. */
    private function resolveNoticeBranch(Request $request)
    {
        if (auth('vendor_employee')->check()) {
            return auth('vendor_employee')->user()->branch_id ?: null;
        }
        if ($request->filled('branch_id') && (int) $request->branch_id > 0) {
            return \App\Models\Branch::where('store_id', Helpers::get_store_id())->where('id', $request->branch_id)->value('id');
        }
        return null;
    }
 
    private function currentUserName(): string
    {
        if (auth('vendor_employee')->check()) {
            $u = auth('vendor_employee')->user();
            return trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')) ?: 'Staff';
        }
        $v = auth('vendor')->user();
        return trim(($v->f_name ?? '') . ' ' . ($v->l_name ?? '')) ?: 'Admin';
    }
 
    private function sendNoticeNotifications($notice)
    {
        $query = \App\Models\Student::where('store_id', $notice->store_id)->where('status', 1);
        if ($notice->branch_id) {
            $query->where('branch_id', $notice->branch_id);
        }
        if ($notice->school_class_id) {
            $studentIds = \App\Models\StudentEnrollment::where('store_id', $notice->store_id)
                ->where('status', 1)
                ->where('school_class_id', $notice->school_class_id)
                ->pluck('student_id');
            $query->whereIn('id', $studentIds);
        }
        $students = $query->get();
        if ($students->isNotEmpty()) {
            $msg = "Notice: {$notice->title}\n" . strip_tags($notice->body);
            $push = [
                'title' => "New Notice: {$notice->title}",
                'description' => strip_tags($notice->body)
            ];
            _sendSchoolNotificationBulk($students, 'notice', $msg, $push);
        }
    }
}
