import unittest
import os
import sys

# Add the current directory to sys.path to find base_test
sys.path.append(os.path.dirname(os.path.abspath(__file__)))

def run_suite():
    loader = unittest.TestLoader()
    suite = loader.discover(start_dir='tests', pattern='test_*.py')
    
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)
    
    if result.wasSuccessful():
        print("\n✅ ALL TESTS PASSED!")
    else:
        print("\n❌ SOME TESTS FAILED. Check logs above.")

if __name__ == "__main__":
    run_suite()
