-- ============================================================================
-- FIX: "Staff Management" (free-plan basic staff) granular permissions table
-- not opening in the vendor role form.
--
-- The role form checkbox is `modules[] = staff_manage`, the action-table is keyed
-- by feature.master_module, and the save filter (CustomRoleController) only stores
-- permissions whose feature.master_module === the ticked module value. Routes and
-- sidebar enforce `permission:basic_staff_manage,<action>`.
--
-- So the `basic_staff_manage` feature must exist and be grouped under
-- master_module = 'staff_manage'. This script guarantees both. Additive &
-- idempotent — safe to run more than once. Run once per vendor DB.
-- ============================================================================

-- 1) Ensure the feature exists, filed under master_module = 'staff_manage'
INSERT INTO `features` (`name`, `display_name`, `master_module`, `created_at`, `updated_at`)
SELECT 'basic_staff_manage', 'Staff Management', 'staff_manage', NOW(), NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `features` WHERE `name` = 'basic_staff_manage'
);

-- Re-file an existing row (it was originally seeded with master_module = NULL)
UPDATE `features`
SET `master_module` = 'staff_manage', `display_name` = 'Staff Management'
WHERE `name` = 'basic_staff_manage';

-- 2) Ensure its action permissions exist (list, add, edit, delete, role_manage).
--    These MUST be free = 1: basic staff is a free feature shown when HR is NOT
--    subscribed. With master_module = 'staff_manage' and free = 0, hasPermission()
--    falls through to permission_check('staff_manage') — which is false on the free
--    plan — so the vendor owner sees the "Staff Management" header but NO sub-options.
--    free = 1 grants the owner directly while staff employees stay gated by their role.
INSERT INTO `feature_permissions` (`feature_id`, `action`, `free`)
SELECT f.id, a.action, 1
FROM `features` f
JOIN (
    SELECT 'list'        AS action UNION ALL
    SELECT 'add'         UNION ALL
    SELECT 'edit'        UNION ALL
    SELECT 'delete'      UNION ALL
    SELECT 'role_manage'
) a
WHERE f.name = 'basic_staff_manage'
  AND NOT EXISTS (
      SELECT 1 FROM `feature_permissions` fp
      WHERE fp.feature_id = f.id AND fp.action = a.action
  );

-- 2b) Flip any pre-existing rows that were seeded with free = 0 (the bug). This is
--     what actually fixes a vendor DB where the feature was filed under staff_manage
--     but its actions are still free = 0, hiding the menu options from the owner.
UPDATE `feature_permissions` fp
JOIN `features` f ON fp.feature_id = f.id
SET fp.free = 1
WHERE f.name = 'basic_staff_manage'
  AND fp.action IN ('list', 'add', 'edit', 'delete', 'role_manage');
