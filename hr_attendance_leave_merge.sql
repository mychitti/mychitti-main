-- ============================================================================
-- HR: merge Attendance + Leave into one feature `attendance_manage`.
-- Additive & idempotent. Existing `leave_manage` / `attendance_report` grants
-- keep working (routes OR-accept both), so NO role loses access.
-- Run once per server.
-- ============================================================================

-- 1) Add the unified actions to attendance_manage (covers attendance + leave + report)
INSERT INTO feature_permissions (feature_id, action, free)
SELECT f.id, a.action, 0
FROM features f
JOIN (
    SELECT 'list'          AS action UNION
    SELECT 'view'          UNION
    SELECT 'edit'          UNION
    SELECT 'export'        UNION
    SELECT 'add'           UNION
    SELECT 'status_change'
) a
WHERE f.name = 'attendance_manage' AND f.master_module = 'hr_manage'
  AND NOT EXISTS (
      SELECT 1 FROM feature_permissions fp WHERE fp.feature_id = f.id AND fp.action = a.action
  );

-- 2) Friendlier label in the role permission grid
UPDATE features SET display_name = 'Attendance & Leave'
WHERE name = 'attendance_manage' AND master_module = 'hr_manage';

-- NOTE: `leave_manage` and `attendance_report` are intentionally left in place so
-- existing role assignments still grant access (the routes accept either). New roles
-- only need to tick "Attendance & Leave". Once no role references the old two, you may
-- optionally retire them:
--   DELETE fp FROM feature_permissions fp JOIN features f ON fp.feature_id=f.id
--     WHERE f.master_module='hr_manage' AND f.name IN ('leave_manage','attendance_report');
--   DELETE FROM features WHERE master_module='hr_manage' AND name IN ('leave_manage','attendance_report');
