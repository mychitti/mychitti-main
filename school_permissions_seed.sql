-- ============================================================================
-- SCHOOL MODULE — granular features & feature_permissions  (master_module = 'school_manage')
-- Additive & idempotent: inserts only missing rows; existing rows are untouched.
-- Safe to run multiple times. Run once per server (admin / vendor / shop).
-- ============================================================================

-- 1) FEATURES (insert only if not already present)
INSERT INTO `features` (`name`, `display_name`, `master_module`, `created_at`, `updated_at`)
SELECT t.name, t.display_name, 'school_manage', NOW(), NOW()
FROM (
    SELECT 'school_dashboard'   AS name, 'School Dashboard'        AS display_name UNION ALL
    SELECT 'academic_setup',          'Academic Setup'            UNION ALL
    SELECT 'students',                'Students'                  UNION ALL
    SELECT 'admissions',              'Admissions'                UNION ALL
    SELECT 'student_promotion',       'Student Promotion'         UNION ALL
    SELECT 'student_attendance',      'Student Attendance'        UNION ALL
    SELECT 'student_leave',           'Student Leave'             UNION ALL
    SELECT 'short_leave',             'Short Leave / Gate Pass'   UNION ALL
    SELECT 'certificates',            'Certificates'              UNION ALL
    SELECT 'fee_dues',                'Fee Dues'                  UNION ALL
    SELECT 'fee_collection',          'Fee Collection'            UNION ALL
    SELECT 'fee_heads',               'Fee Heads'                 UNION ALL
    SELECT 'fee_structure',           'Fee Structure'             UNION ALL
    SELECT 'scholarship',             'Scholarships'              UNION ALL
    SELECT 'exams',                   'Exams & Results'           UNION ALL
    SELECT 'question_bank',           'Question Bank'             UNION ALL
    SELECT 'timetable',               'Timetable'                 UNION ALL
    SELECT 'homework',                'Homework'                  UNION ALL
    SELECT 'transport',               'Transport'                 UNION ALL
    SELECT 'hostel',                  'Hostel'                    UNION ALL
    SELECT 'notices',                 'Notice Board'              UNION ALL
    SELECT 'school_reports',          'School Reports'            UNION ALL
    SELECT 'school_settings',         'School Settings'
) t
WHERE NOT EXISTS (
    SELECT 1 FROM `features` f WHERE f.name = t.name AND f.master_module = 'school_manage'
);

-- 2) FEATURE PERMISSIONS (insert only the (feature, action) pairs that don't exist yet)
INSERT INTO `feature_permissions` (`feature_id`, `action`, `free`)
SELECT f.id, a.action, 0
FROM `features` f
JOIN (
    SELECT 'school_dashboard'  AS name, 'view'        AS action UNION ALL

    SELECT 'academic_setup', 'view' UNION ALL SELECT 'academic_setup','add' UNION ALL
    SELECT 'academic_setup', 'edit' UNION ALL SELECT 'academic_setup','delete' UNION ALL

    SELECT 'students','view' UNION ALL SELECT 'students','add' UNION ALL
    SELECT 'students','edit' UNION ALL SELECT 'students','delete' UNION ALL SELECT 'students','import' UNION ALL

    SELECT 'admissions','view' UNION ALL SELECT 'admissions','add' UNION ALL
    SELECT 'admissions','edit' UNION ALL SELECT 'admissions','delete' UNION ALL

    SELECT 'student_promotion','view' UNION ALL SELECT 'student_promotion','promote' UNION ALL

    SELECT 'student_attendance','view' UNION ALL SELECT 'student_attendance','add' UNION ALL

    SELECT 'student_leave','view' UNION ALL SELECT 'student_leave','add' UNION ALL
    SELECT 'student_leave','approve' UNION ALL SELECT 'student_leave','reject' UNION ALL SELECT 'student_leave','delete' UNION ALL

    SELECT 'short_leave','view' UNION ALL SELECT 'short_leave','add' UNION ALL
    SELECT 'short_leave','return' UNION ALL SELECT 'short_leave','delete' UNION ALL

    SELECT 'certificates','view' UNION ALL SELECT 'certificates','add' UNION ALL
    SELECT 'certificates','edit' UNION ALL SELECT 'certificates','delete' UNION ALL

    SELECT 'fee_dues','view' UNION ALL

    SELECT 'fee_collection','view' UNION ALL SELECT 'fee_collection','collect' UNION ALL

    SELECT 'fee_heads','view' UNION ALL SELECT 'fee_heads','add' UNION ALL SELECT 'fee_heads','delete' UNION ALL

    SELECT 'fee_structure','view' UNION ALL SELECT 'fee_structure','add' UNION ALL

    SELECT 'scholarship','view' UNION ALL SELECT 'scholarship','add' UNION ALL SELECT 'scholarship','delete' UNION ALL

    SELECT 'exams','view' UNION ALL SELECT 'exams','add' UNION ALL
    SELECT 'exams','edit' UNION ALL SELECT 'exams','enter_marks' UNION ALL

    SELECT 'question_bank','view' UNION ALL SELECT 'question_bank','add' UNION ALL SELECT 'question_bank','delete' UNION ALL

    SELECT 'timetable','view' UNION ALL SELECT 'timetable','add' UNION ALL
    SELECT 'timetable','edit' UNION ALL SELECT 'timetable','delete' UNION ALL

    SELECT 'homework','view' UNION ALL SELECT 'homework','add' UNION ALL
    SELECT 'homework','edit' UNION ALL SELECT 'homework','delete' UNION ALL SELECT 'homework','evaluate' UNION ALL

    SELECT 'transport','view' UNION ALL SELECT 'transport','add' UNION ALL
    SELECT 'transport','edit' UNION ALL SELECT 'transport','delete' UNION ALL

    SELECT 'hostel','view' UNION ALL SELECT 'hostel','add' UNION ALL
    SELECT 'hostel','edit' UNION ALL SELECT 'hostel','delete' UNION ALL

    SELECT 'notices','view' UNION ALL SELECT 'notices','add' UNION ALL
    SELECT 'notices','edit' UNION ALL SELECT 'notices','delete' UNION ALL

    SELECT 'school_reports','view' UNION ALL SELECT 'school_reports','export' UNION ALL

    SELECT 'school_settings','view' UNION ALL SELECT 'school_settings','edit'
) a ON a.name = f.name
WHERE f.master_module = 'school_manage'
  AND NOT EXISTS (
      SELECT 1 FROM `feature_permissions` fp WHERE fp.feature_id = f.id AND fp.action = a.action
  );

-- NOTE: the old coarse `fees` feature (view/add/edit/delete/collect) is now superseded by
-- fee_dues / fee_collection / fee_heads / fee_structure / scholarship. It is left in place
-- (harmless). To remove it after confirming no role still references it:
--   DELETE FROM feature_permissions WHERE feature_id IN (SELECT id FROM features WHERE name='fees' AND master_module='school_manage');
--   DELETE FROM features WHERE name='fees' AND master_module='school_manage';
