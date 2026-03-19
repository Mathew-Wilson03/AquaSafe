import unittest
import os
import sys

# Add current and tests directory to sys.path
root_dir = os.path.dirname(os.path.abspath(__file__))
sys.path.append(root_dir)
sys.path.append(os.path.join(root_dir, 'tests'))

def run_suite():
    loader = unittest.TestLoader()
    # Ordered list of core tests
    test_files = [
        'test_01_registration.py',
        'test_02_login.py',
        'test_06_dashboard_core.py',
        'test_07_admin_core.py'
    ]
    
    suite = unittest.TestSuite()
    for t in test_files:
        full_path = os.path.join('tests', t)
        if os.path.exists(full_path):
            suite.addTests(loader.loadTestsFromName(f"tests.{t[:-3]}"))
    
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    if result.wasSuccessful():
        print("\n✅ CORE FUNCTIONALITY TESTS PASSED!")
    else:
        print("\n❌ SOME CORE TESTS FAILED. Check logs above.")

if __name__ == "__main__":
    run_suite()
