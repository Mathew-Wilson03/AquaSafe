from base_test import AquaSafeBaseTest
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import unittest

class TestLogin(AquaSafeBaseTest):
    def test_login(self):
        # We assume the user exists from previous test or create one
        print(f"\n[Test] Login - Email: {self.test_email}")
        
        # In modular tests, we might need a fresh registration if running in isolation
        # but for this flow we can just login with the class-level email
        self.login_manually(self.test_email, self.test_password)
        
        WebDriverWait(self.driver, 20).until(
            lambda d: "user_dashboard.php" in d.current_url or "dashboard_selector.php" in d.current_url
        )
        self.save_screenshot("modular_login_success")
        self.assertTrue("dashboard" in self.driver.current_url or "selector" in self.driver.current_url)
        print(f"Login Successful! Dashboard reached.")
        self.logout()

if __name__ == "__main__":
    unittest.main()
