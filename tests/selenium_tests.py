import unittest
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager
import time
import random
import string
import os

class AquaSafeTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        chrome_options = Options()
        # chrome_options.add_argument("--headless")  # Commented out to show the browser
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1920,1080")
        
        try:
            cls.driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
        except Exception as e:
            print(f"Failed to initialize Chrome: {e}")
            raise e
            
        cls.driver.implicitly_wait(10)
        cls.base_url = "http://localhost:8000"
        cls.test_email = f"test_{''.join(random.choices(string.ascii_lowercase + string.digits, k=8))}@example.com"
        cls.test_password = "Password123!"

    @classmethod
    def tearDownClass(cls):
        if hasattr(cls, 'driver'):
            cls.driver.quit()

    def save_screenshot(self, name):
        artifact_dir = r"C:\Users\mathe\.gemini\antigravity\brain\f1a2d25b-3e23-4d22-99c2-88063e4aeb5b"
        path = os.path.join(artifact_dir, f"{name}.png")
        self.driver.save_screenshot(path)
        print(f"Screenshot saved to: {path}")

    def test_01_signup(self):
        """Test the registration functionality."""
        print(f"\nRunning Signup Test with email: {self.test_email}")
        try:
            self.driver.get(f"{self.base_url}/Login/signup.php?role=user")
            
            # Wait for form
            WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.NAME, "signup_btn")))
            
            # Fill signup form
            self.driver.find_element(By.NAME, "name").send_keys("Test User")
            self.driver.find_element(By.NAME, "email").send_keys(self.test_email)
            self.driver.find_element(By.NAME, "password").send_keys(self.test_password)
            self.driver.find_element(By.NAME, "confirm_password").send_keys(self.test_password)
            
            # Submit
            signup_btn = self.driver.find_element(By.NAME, "signup_btn")
            signup_btn.click()
            
            # Verify redirection to login with success message
            WebDriverWait(self.driver, 10).until(EC.url_contains("login.php"))
            self.save_screenshot("signup_success")
            self.assertIn("signup=success", self.driver.current_url)
            print("Signup Success!")
        except Exception as e:
            self.save_screenshot("signup_failed")
            raise e

    def test_02_login(self):
        """Test the login functionality with the newly created user."""
        print(f"Running Login Test for: {self.test_email}")
        try:
            self.driver.get(f"{self.base_url}/Login/login.php?role=user")
            
            # Wait for form
            WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.NAME, "login_btn")))
            
            # Fill login form
            self.driver.find_element(By.NAME, "email").send_keys(self.test_email)
            self.driver.find_element(By.NAME, "password").send_keys(self.test_password)
            
            # Submit
            self.driver.find_element(By.NAME, "login_btn").click()
            
            # Verify redirection to dashboard
            WebDriverWait(self.driver, 20).until(
                lambda d: "user_dashboard.php" in d.current_url or "dashboard_selector.php" in d.current_url
            )
            self.save_screenshot("login_success")
            self.assertTrue("dashboard" in self.driver.current_url or "selector" in self.driver.current_url)
            print(f"Login Success! Current URL: {self.driver.current_url}")
        except Exception as e:
            self.save_screenshot("login_failed")
            print(f"Current URL at failure: {self.driver.current_url}")
            raise e

    def test_03_dashboard_verification(self):
        """Verify dashboard UI elements."""
        print("Verifying Dashboard UI...")
        try:
            # Check title
            self.assertIn("AquaSafe", self.driver.title)
            
            # Check for content
            body_text = self.driver.find_element(By.TAG_NAME, "body").text
            # Print a bit of body text to debug
            print(f"Dashboard body text snippet: {body_text[:200]}")
            
            # Success if we find something descriptive
            self.assertTrue(len(body_text) > 100)
            self.save_screenshot("dashboard_view")
            print("Dashboard Verification Success!")
        except Exception as e:
            self.save_screenshot("dashboard_failed")
            print(f"Dashboard element verification failed: {e}")
            self.fail(f"Could not verify dashboard: {e}")

if __name__ == "__main__":
    unittest.main()
