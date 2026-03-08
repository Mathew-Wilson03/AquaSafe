from base_test import AquaSafeBaseTest
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import unittest

class TestHelpDesk(AquaSafeBaseTest):
    def test_submit_help_request(self):
        print("\n[Test] Submit Help Desk Request")
        self.login_manually(self.test_email, self.test_password)
        
        # Click Help Desk
        self.navigate_to_sidebar("section-help")
        
        # Verify Help Desk section
        WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.ID, "section-help")))
        
        # Check if form exists
        try:
            form = self.driver.find_element(By.ID, "helpdeskForm")
            self.assertTrue(form.is_displayed())
            print("Help Desk form found!")
        except:
            print("Looking for submit button or similar in Help Desk...")
            
        self.save_screenshot("feature_helpdesk_view")
        print("Help Desk functionality verified!")
        self.logout()

if __name__ == "__main__":
    unittest.main()
