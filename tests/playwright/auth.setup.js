import { test as setup, expect } from '@playwright/test';

const authFile = 'tests/playwright/.auth/user.json';

setup('authenticate', async ({ page }) => {
  // Navigate to login page
  await page.goto('/login');
  
  // Wait for login page to load
  await page.waitForLoadState('networkidle');
  
  // Fill in login form
  await page.fill('input[type="email"]', 'user@gmail.com');
  await page.fill('input[type="password"]', '123');
  
  // Click sign in button
  await page.click('button[type="submit"]:has-text("Sign In")');
  
  // Wait for redirect to dashboard
  await page.waitForURL('**/user/dashboard');
  
  // Verify we're logged in by checking for user name or dashboard elements
  await expect(page.locator('text="Regular User"')).toBeVisible();
  
  // Save authentication state
  await page.context().storageState({ path: authFile });
});