import unittest
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from webdriver_manager.chrome import ChromeDriverManager
import os
import random
import string

class AquaSafeBaseTest(unittest.TestCase):
    driver = None
    base_url = "https://aquasafe-production-703c.up.railway.app"
    artifact_dir = r"C:\Users\mathe\.gemini\antigravity\brain\1e8b2663-1195-4109-9191-e6835c21e35a"
    
    # Shared test data (static to persist across files if needed, or re-generated)
    test_email = f"test_{''.join(random.choices(string.ascii_lowercase + string.digits, k=8))}@example.com"
    test_password = "Password123!"

    @classmethod
    def setUpClass(cls):
        chrome_options = Options()
        # Headless mode is OFF by default as per user request
        # chrome_options.add_argument("--headless")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--window-size=1920,1080")
        
        cls.driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()), options=chrome_options)
        cls.driver.implicitly_wait(10)

    @classmethod
    def tearDownClass(cls):
        if cls.driver:
            cls.driver.quit()

    def save_screenshot(self, name):
        if not os.path.exists(self.artifact_dir):
            os.makedirs(self.artifact_dir)
        path = os.path.join(self.artifact_dir, f"{name}.png")
        self.driver.save_screenshot(path)
        print(f"Screenshot saved to: {path}")

    def login_manually(self, email, password):
        self.driver.get(f"{self.base_url}/Login/login.php?role=user")
        self.driver.find_element("name", "email").send_keys(email)
        self.driver.find_element("name", "password").send_keys(password)
        self.driver.find_element("name", "login_btn").click()

    def logout(self):
        print("Logging out...")
        try:
            from selenium.webdriver.common.by import By
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            
            # Find the Sign Out link in the sidebar footer
            logout_btn = WebDriverWait(self.driver, 10).until(
                EC.element_to_be_clickable((By.XPATH, "//a[contains(text(), 'Sign Out')]"))
            )
            logout_btn.click()
            
            # Verify redirection back to login or home
            WebDriverWait(self.driver, 10).until(EC.url_contains("login.php"))
            print("Logout successful.")
        except Exception as e:
            self.save_screenshot("logout_failed")
            print(f"Logout failed: {e}")

    def navigate_to_sidebar(self, data_target):
        """Clicks a sidebar link based on its data-target attribute."""
        from selenium.webdriver.common.by import By
        from selenium.webdriver.support.ui import WebDriverWait
        from selenium.webdriver.support import expected_conditions as EC
        
        print(f"Navigating to sidebar item: {data_target}")
        link = WebDriverWait(self.driver, 10).until(
            EC.element_to_be_clickable((By.CSS_SELECTOR, f"a[data-target='{data_target}']"))
        )
        link.click()
        WebDriverWait(self.driver, 10).until(EC.presence_of_element_located((By.ID, data_target)))
