from base_test import AquaSafeBaseTest
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import unittest

class TestSettings(AquaSafeBaseTest):
    def test_profile_access(self):
        print("\n[Test] Profile/Settings Access")
        self.login_manually(self.test_email, self.test_password)
        
        # Click Settings
        self.navigate_to_sidebar("section-settings")
        
        # Check for profile name input or similar
        body_text = self.driver.find_element(By.TAG_NAME, "body").text
        self.assertIn("Settings", body_text)
        
        self.save_screenshot("feature_settings_view")
        print("Settings section verified!")
        self.logout()

if __name__ == "__main__":
    unittest.main()
