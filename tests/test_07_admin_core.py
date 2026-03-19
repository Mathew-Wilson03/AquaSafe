from base_test import AquaSafeBaseTest
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import unittest
import time

class TestAdminCore(AquaSafeBaseTest):
    admin_email = "admin_test@example.com"
    admin_password = "Password123!"

    def test_admin_core(self):
        print("\n[Test] Admin Core Functionality")
        
        # Note: We assume the admin user exists and has roles
        # I'll try to login as admin
        self.driver.get(f"{self.base_url}/Login/login.php?role=admin")
        self.driver.find_element(By.NAME, "email").send_keys(self.admin_email)
        self.driver.find_element(By.NAME, "password").send_keys(self.admin_password)
        self.driver.find_element(By.NAME, "login_btn").click()
        
        # Verify Admin Dashboard
        WebDriverWait(self.driver, 20).until(EC.url_contains("admin_dashboard.php"))
        print("Admin Dashboard reached.")
        
        # Verify System Health Widgets
        health_widget = WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.ID, "overallSafetyValue"))
        )
        self.assertTrue(health_widget.is_displayed())
        print(f"System Health: {health_widget.text}")
        
        # Navigate to Alerts/Broadcast
        print("Navigating to Emergency Broadcast...")
        link = WebDriverWait(self.driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, "a[data-target='alerts']"))
        )
        link.click()
        
        # Verify Broadcast Section
        WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.ID, "alertMessage")))
        
        # Create a Broadcast Alert
        print("Creating Broadcast Alert...")
        msg = "TEST BROADCAST: This is a system-wide test."
        self.driver.find_element(By.ID, "alertMessage").send_keys(msg)
        
        # Select Severity
        from selenium.webdriver.support.ui import Select
        severity = Select(self.driver.find_element(By.ID, "alertSeverity"))
        severity.select_by_value("Warning")
        
        # Send
        self.driver.find_element(By.ID, "btnBroadcast").click()
        
        # Verify Success Swal
        WebDriverWait(self.driver, 15).until(
            EC.presence_of_element_located((By.CLASS_NAME, "swal2-success"))
        )
        print("Broadcast Alert sent successfully!")
        self.save_screenshot("admin_broadcast_success")
        
        self.driver.find_element(By.CLASS_NAME, "swal2-confirm").click()
        
        self.logout()

if __name__ == "__main__":
    unittest.main()
