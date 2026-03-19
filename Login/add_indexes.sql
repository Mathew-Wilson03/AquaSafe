-- ============================================================
-- AquaSafe - Performance Indexes
-- Run once on your Railway (or local) MySQL database.
-- These transform full-table-scan ORDER BY queries into fast
-- indexed seeks, which is the single biggest DB-level win.
-- ============================================================

-- 1. flood_data: all queries order by created_at DESC
--    Also filters by location in get_user_safety_data.php
ALTER TABLE flood_data
    ADD INDEX IF NOT EXISTS idx_fd_created_at (created_at DESC),
    ADD INDEX IF NOT EXISTS idx_fd_location_time (location(64), created_at DESC);

-- 2. sensor_alerts: ordered by timestamp; filtered by location + alert_type
ALTER TABLE sensor_alerts
    ADD INDEX IF NOT EXISTS idx_sa_timestamp (timestamp DESC),
    ADD INDEX IF NOT EXISTS idx_sa_location_type (location(64), alert_type(16), timestamp DESC);

-- 3. notification_history: ordered by created_at; filtered by sensor_id + location
ALTER TABLE notification_history
    ADD INDEX IF NOT EXISTS idx_nh_created_at (created_at DESC),
    ADD INDEX IF NOT EXISTS idx_nh_sensor_time (sensor_id(32), created_at DESC),
    ADD INDEX IF NOT EXISTS idx_nh_location_time (location(64), created_at DESC);

-- 4. evacuation_points: ordered by proximity calculation on lat/lng; filtered by status
ALTER TABLE evacuation_points
    ADD INDEX IF NOT EXISTS idx_ep_status (status(16)),
    ADD INDEX IF NOT EXISTS idx_ep_lat_lng (latitude, longitude);

-- 5. sensor_status: looked up by sensor_id in alert_utils.php
ALTER TABLE sensor_status
    ADD INDEX IF NOT EXISTS idx_ss_sensor_id (sensor_id(32));

-- 6. alert_history: checked for cooldown in handleIoTTrigger (sensor_id + status + sent_at)
ALTER TABLE alert_history
    ADD INDEX IF NOT EXISTS idx_ah_sensor_status_time (sensor_id, status(16), sent_at DESC);

-- 7. system_notifications: debounce check in logSystemNotification
ALTER TABLE system_notifications
    ADD INDEX IF NOT EXISTS idx_sn_dedup (type(16), location(64), timestamp DESC);

-- Verify indexes were created
SHOW INDEX FROM flood_data;
SHOW INDEX FROM sensor_alerts;
SHOW INDEX FROM notification_history;
