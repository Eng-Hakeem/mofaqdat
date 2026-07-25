-- =============================================
-- تحديث جدول الإشعارات - شغّله في phpMyAdmin
-- =============================================
USE mofaqdat;

ALTER TABLE notifications ADD COLUMN IF NOT EXISTS matched_report_id INT NULL AFTER report_id;
ALTER TABLE notifications ADD COLUMN IF NOT EXISTS match_id INT NULL AFTER matched_report_id;
ALTER TABLE notifications MODIFY COLUMN type ENUM('match', 'new_found', 'status', 'system') DEFAULT 'system';

DESCRIBE notifications;
