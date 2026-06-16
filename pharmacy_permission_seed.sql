-- ============================================================================
-- Basic Pharmacy (FREE) permission. Adds a `pharmacy` feature with free=1 so the
-- hospital store OWNER can use it without the premium plan; staff get it via roles.
-- Grouped under master_module='hospital_manage' for the role-permission grid.
-- Additive & idempotent. Run once per hospital vendor DB.
-- ============================================================================

INSERT INTO `features` (`name`, `display_name`, `master_module`, `created_at`, `updated_at`)
SELECT 'pharmacy', 'Pharmacy (Basic)', 'hospital_manage', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `features` WHERE `name` = 'pharmacy');

INSERT INTO `feature_permissions` (`feature_id`, `action`, `free`)
SELECT f.id, a.action, 1
FROM `features` f
JOIN (
    SELECT 'list'     AS action UNION ALL
    SELECT 'add'      UNION ALL
    SELECT 'edit'     UNION ALL
    SELECT 'delete'   UNION ALL
    SELECT 'dispense'
) a
WHERE f.name = 'pharmacy'
  AND NOT EXISTS (
      SELECT 1 FROM `feature_permissions` fp WHERE fp.feature_id = f.id AND fp.action = a.action
  );
