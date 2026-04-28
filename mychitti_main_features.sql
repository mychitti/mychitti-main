-- phpMyAdmin SQL Dump
-- version 5.2.3-1.el9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 28, 2026 at 06:16 AM
-- Server version: 10.11.10-MariaDB
-- PHP Version: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mychitti_main`
--

-- --------------------------------------------------------

--
-- Table structure for table `features`
--

CREATE TABLE `features` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(150) DEFAULT NULL,
  `master_module` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `features`
--

INSERT INTO `features` (`id`, `name`, `display_name`, `master_module`, `created_at`, `updated_at`) VALUES
(1, 'pos_token', 'POS Token', 'pos', NULL, NULL),
(3, 'pos', 'POS', 'pos', NULL, NULL),
(4, 'pos_items', 'POS Items', 'pos', NULL, NULL),
(6, 'pos_branch', 'POS Branch', 'pos', NULL, NULL),
(7, 'inventory', 'Inventory', 'inventory_manage', NULL, NULL),
(8, 'inventory_item_entry', 'Item Entry', 'inventory_manage', NULL, NULL),
(9, 'inventory_item', 'Item', 'inventory_manage', NULL, NULL),
(10, 'inventory_storage_units', 'Storage Units', 'inventory_manage', NULL, NULL),
(12, 'inventory_sale_order', 'Sale Order', 'inventory_manage', NULL, NULL),
(13, 'inventory_report', 'Report', 'inventory_manage', NULL, NULL),
(14, 'inventory_stock_in_out', 'Stock-in / Stock-out', 'inventory_manage', NULL, NULL),
(15, 'inventory_sale_return', 'Sale Return', 'inventory_manage', NULL, NULL),
(16, 'inventory_purchase_order', 'Purchase Order', 'inventory_manage', NULL, NULL),
(17, 'inventory_purchase_return', 'Purchase Return', 'inventory_manage', NULL, NULL),
(18, 'purchase_bill', 'Purchase Bill', 'billing', NULL, NULL),
(19, 'stock_report', 'Stock Report', 'inventory_manage', NULL, NULL),
(20, 'sale_report', 'Sale Report', 'inventory_manage', NULL, NULL),
(21, 'purchase_report', 'Purchase Report', 'inventory_manage', NULL, NULL),
(22, 'profit_loss_summary', 'Profit & Loss Summary', 'inventory_manage', NULL, NULL),
(23, 'task', 'Task', 'task_manage', NULL, NULL),
(24, 'subtask', 'Subtask', 'task_manage', NULL, NULL),
(25, 'subtask_update', 'Subtask Update', 'task_manage', NULL, NULL),
(26, 'task_update', 'Task Update', 'task_manage', NULL, NULL),
(28, 'approvals', 'Approvals', 'account_manage', NULL, NULL),
(29, 'apporval_form_journal_entry', 'Apporval Form (journal entry)', 'account_manage', '2025-11-25 09:51:41', '2025-11-25 09:51:41'),
(38, 'apporval_form_master_ledger', 'Apporval Form (Master Ledger)', 'account_manage', '2025-11-25 09:51:41', '2025-11-25 09:51:41'),
(39, 'boa_journal_entry', 'Book of Accounts (Journal Entry)', 'account_manage', '2025-11-25 09:51:41', '2025-11-25 09:51:41'),
(40, 'boa_day_book', 'Book of Accounts (Day book)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(41, 'boa_petty_cashbook', 'Book of Accounts (Petty Cashbook)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(42, 'boa_monthly_maintenance', 'Book of Accounts (Monthly Maintenance)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(43, 'banking_bank_accounts', 'Banking (Bank Accounts)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(44, 'banking_cash_book', 'Banking (Cash Book)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(45, 'banking_bank_reconciliation', 'Banking (Bank Reconciliation)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(46, 'statements_trial_balance', 'Statements (Trial Balance)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(47, 'statements_balance_sheet', 'Statements (Balance Sheet)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(48, 'rmf_maintenance_requests', 'Recurring Monthly Finance (Maintenance Requests)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(49, 'rmf_bill_payments', 'Recurring Monthly Finance (Bill Payments)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(50, 'rmf_property_valuation', 'Recurring Monthly Finance (Property Valuation)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(51, 'assets_company_assets', 'Assets (Company Assets)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(52, 'assets_chart_of_accounts', 'Assets (Chart of Accounts)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(53, 'reports_account_report', 'Reports (Account Report)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(54, 'reports_tax_report', 'Reports (Tax Report)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(56, 'reports_audit_logs', 'Reports (Audit Logs)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(57, 'for_gst_filing_report', 'For GST Filing Report', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(58, 'settings_account_type', 'Settings (Account Type)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(59, 'settings_common', 'Settings (Common Settings)', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(60, 'boa_master_ledger', 'Book of Accounts (Master Ledger Book)', 'account_manage', '2025-11-25 09:51:41', '2025-11-25 09:51:41'),
(61, 'apporval_form_incoming_requests', 'Apporval Form (incoming requests)', 'account_manage', '2025-11-25 09:51:41', '2025-11-25 09:51:41'),
(69, 'dashboard', 'Dashboard', 'account_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(70, 'project_task', 'Project Task', 'projects_manage', '2025-11-25 10:30:13', '2025-11-25 10:30:13'),
(71, 'project_subtask', 'Project Subtask', 'projects_manage', NULL, NULL),
(72, 'project_task_update', 'Project Task Update', 'projects_manage', NULL, NULL),
(73, 'project_subtask_update', 'Project Subtask Update', 'projects_manage', NULL, NULL),
(74, 'project', 'Project', 'projects_manage', NULL, NULL),
(75, 'project_milestone', 'Project Milestone', 'projects_manage', NULL, NULL),
(76, 'project_internal_note', 'Project Internal Note', 'projects_manage', NULL, NULL),
(77, 'billing', 'Billing', 'billing', NULL, NULL),
(80, 'billing_bank_account', 'Billing Bank Account', 'billing', NULL, NULL),
(81, 'billing_signatures', 'Billing Signature', 'billing', NULL, NULL),
(82, 'billing_tnc', 'Billing T&C', 'billing', NULL, NULL),
(85, 'leads_manage', 'Leads Management', 'leads_manage', NULL, NULL),
(86, 'leads_quotation', 'Leads Quotation', 'leads_manage', NULL, NULL),
(87, 'leads_gatepass', 'Leads Gatepass', 'leads_manage', NULL, NULL),
(89, 'leads_jobcard', 'Leads Jobcard', 'leads_manage', NULL, NULL),
(95, 'leads_receivable_receipt', 'Leads Receivable Receipt', 'leads_manage', NULL, NULL),
(97, 'staff_manage', 'Staff Management', 'hr_manage', NULL, NULL),
(99, 'hr_manage', 'HR Management', 'hr_manage', NULL, NULL),
(100, 'staff_team', 'Staff Team', 'hr_manage', NULL, NULL),
(101, 'staff_department', 'Staff Department', 'hr_manage', NULL, NULL),
(102, 'staff_role', 'Staff Role', 'hr_manage', NULL, NULL),
(103, 'attendance_manage', 'Attendance Manage', 'hr_manage', NULL, NULL),
(106, 'attendance_report', 'Attendance Report', 'hr_manage', NULL, NULL),
(107, 'shift_manage', 'Shift Management', 'hr_manage', NULL, NULL),
(108, 'leave_manage', 'Leave Management', 'hr_manage', NULL, NULL),
(109, 'salary_manage', 'Salary Management', 'hr_manage', NULL, NULL),
(110, 'task_salary_category', 'Task Salary Category', 'hr_manage', NULL, NULL),
(111, 'salary_report', 'Salary Report', 'hr_manage', NULL, NULL),
(112, 'salary_advanced', 'Salary Advanced', 'hr_manage', NULL, NULL),
(113, 'quotaiton_manage', 'Quotation Manage', 'quotaiton_manage', NULL, NULL),
(117, 'quotation_bank_account', 'Quotation Bank Account', 'quotaiton_manage', NULL, NULL),
(118, 'quotation_sign', 'Quotation Signature', 'quotaiton_manage', NULL, NULL),
(119, 'quotation_tnc', 'Quotation T&C', 'quotaiton_manage', NULL, NULL),
(120, 'client_manage', 'Client Manage', 'client_manage', NULL, NULL),
(121, 'action_logs', 'Action Logs', 'logs', '2026-03-25 05:43:53', '2026-03-25 05:43:53'),
(122, 'error_logs', 'Error Logs', 'logs', '2026-03-25 05:43:53', '2026-03-25 05:43:53'),
(123, 'admin_actions', 'Admin Actions', 'logs', '2026-03-25 05:43:53', '2026-03-25 05:43:53'),
(124, 'analytics', 'Analytics', 'logs', '2026-03-25 06:41:53', '2026-03-25 06:41:53'),
(125, 'support_ticket', 'Support Tickets', 'support_ticket', '2026-03-27 05:48:13', '2026-03-27 05:48:13'),
(126, 'ai_agent', 'AI Agent', 'ai_agent', '2026-03-27 05:48:13', '2026-03-27 05:48:13'),
(127, 'store', 'Store', 'store', NULL, NULL),
(128, 'gst_report', 'GST Report', 'inventory_manage', NULL, NULL),
(129, 'restaurant_tables', 'Restaurant Tables', 'pos', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `features`
--
ALTER TABLE `features`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
