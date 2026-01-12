CREATE TABLE IF NOT EXISTS notification_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    master_enabled BOOLEAN DEFAULT TRUE,
    warning_threshold INT DEFAULT 75,
    critical_threshold INT DEFAULT 90,
    sms_enabled BOOLEAN DEFAULT TRUE,
    email_enabled BOOLEAN DEFAULT TRUE,
    push_enabled BOOLEAN DEFAULT FALSE,
    siren_enabled BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO notification_settings (id, master_enabled, warning_threshold, critical_threshold, sms_enabled, email_enabled, push_enabled, siren_enabled)
VALUES (1, TRUE, 75, 90, TRUE, TRUE, FALSE, FALSE)
ON DUPLICATE KEY UPDATE id=id;
