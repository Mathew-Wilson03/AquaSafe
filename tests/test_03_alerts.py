from base_test import AquaSafeBaseTest
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import unittest
import time

class TestAlerts(AquaSafeBaseTest):
    def test_view_alerts(self):
        print("\n[Test] View Alerts")
        # Ensure logged in (using the same class-level test account)
        # Note: If running this file standalone, it might fail if user doesn't exist
        # Better practice for E2E: Login first
        self.login_manually(self.test_email, self.test_password)
        
        # Click Alerts in Sidebar
        self.navigate_to_sidebar("section-alerts")
        
        self.save_screenshot("feature_alerts_view")
        print("Alerts section verified!")
        self.logout()

if __name__ == "__main__":
    unittest.main()
