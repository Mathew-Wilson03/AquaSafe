from base_test import AquaSafeBaseTest
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
import unittest

class TestRegistration(AquaSafeBaseTest):
    def test_signup(self):
        print(f"\n[Test] Registration - Email: {self.test_email}")
        self.driver.get(f"{self.base_url}/Login/signup.php?role=user")
        
        WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.NAME, "signup_btn")))
        
        self.driver.find_element(By.NAME, "name").send_keys("Test User")
        self.driver.find_element(By.NAME, "email").send_keys(self.test_email)
        self.driver.find_element(By.NAME, "password").send_keys(self.test_password)
        self.driver.find_element(By.NAME, "confirm_password").send_keys(self.test_password)
        
        self.driver.find_element(By.NAME, "signup_btn").click()
        
        WebDriverWait(self.driver, 10).until(EC.url_contains("login.php"))
        self.assertIn("signup=success", self.driver.current_url)
        self.save_screenshot("modular_signup_success")
        print("Registration Successful! Now logging in to verify and logout.")
        
        # Verify login and logout
        self.login_manually(self.test_email, self.test_password)
        WebDriverWait(self.driver, 10).until(EC.url_contains("dashboard"))
        self.logout()

if __name__ == "__main__":
    unittest.main()
