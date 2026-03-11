-- AquaSafe Database Dump for Railway
-- Generated to migrate from Azure/Local to Railway MySQL

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('administrator','user','admin') DEFAULT 'user',
  `reset_token` varchar(10) DEFAULT NULL,
  `location` varchar(100) DEFAULT 'Central City',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user` LIKE `users`;

-- 2. Sensor Status Table
CREATE TABLE IF NOT EXISTS `sensor_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor_id` varchar(50) NOT NULL UNIQUE,
  `location_name` varchar(100) NOT NULL,
  `status` enum('Active','Offline','Maintenance') DEFAULT 'Active',
  `water_level` decimal(5,2) DEFAULT 0.00,
  `battery_level` int(11) DEFAULT 100,
  `last_ping` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Sensor Alerts Table
CREATE TABLE IF NOT EXISTS `sensor_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor_id` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `severity` enum('Safe','Warning','Critical') DEFAULT 'Safe',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Flood Data History Table
CREATE TABLE IF NOT EXISTS `flood_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sensor_id` varchar(50) NOT NULL,
  `location` varchar(255) NOT NULL,
  `level` decimal(5,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Notification Settings Table
CREATE TABLE IF NOT EXISTS `notification_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `master_enabled` tinyint(1) DEFAULT 1,
  `warning_threshold` int(11) DEFAULT 75,
  `critical_threshold` int(11) DEFAULT 90,
  `sms_enabled` tinyint(1) DEFAULT 1,
  `email_enabled` tinyint(1) DEFAULT 1,
  `push_enabled` tinyint(1) DEFAULT 0,
  `siren_enabled` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `notification_settings` (`id`, `master_enabled`, `warning_threshold`, `critical_threshold`, `sms_enabled`, `email_enabled`, `push_enabled`, `siren_enabled`)
VALUES (1, 1, 75, 90, 1, 1, 0, 0)
ON DUPLICATE KEY UPDATE id=id;

-- 6. Helpdesk Requests Table
CREATE TABLE IF NOT EXISTS `helpdesk_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `details` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `status` enum('Pending','In Progress','Resolved') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Emergency Signals Table
CREATE TABLE IF NOT EXISTS `emergency_signals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `status` enum('Active','Cleared') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Evacuation Points Table
CREATE TABLE IF NOT EXISTS `evacuation_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT 0.00,
  `longitude` decimal(11,8) DEFAULT 0.00,
  `capacity` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'Available',
  `assigned_sensor` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `sensor_status` (`sensor_id`, `location_name`, `status`, `water_level`, `battery_level`) VALUES 
('SNS-001', 'Nellimala Cluster', 'Active', 0.00, 100),
('SNS-002', 'Churakullam Cluster', 'Active', 0.00, 100),
('SNS-003', 'Kakkikavala Cluster', 'Active', 0.00, 100);

SET FOREIGN_KEY_CHECKS = 1;
