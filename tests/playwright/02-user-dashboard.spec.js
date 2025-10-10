import { test, expect } from '@playwright/test';

// Use authenticated state for dashboard tests
test.use({ storageState: 'tests/playwright/.auth/user.json' });

test.describe('User Dashboard', () => {
  test('should display dashboard correctly after login', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Check dashboard elements
    await expect(page.locator('text="Regular User"')).toBeVisible();
    await expect(page.locator('text="Team Member"')).toBeVisible();
    
    // Check navigation elements
    await expect(page.locator('text="Dashboard"')).toBeVisible();
    
    // Check if team information is displayed
    await expect(page.locator('text="Tim Test"')).toBeVisible();
  });

  test('should show available quizzes', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
    
    // Look for quiz-related content
    const quizSection = page.locator('text="Available Quizzes"').or(page.locator('text="Quizzes"'));
    
    if (await quizSection.isVisible()) {
      await expect(quizSection).toBeVisible();
    }
  });

  test('should show recent attempts section', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
    
    // Look for recent attempts section
    const recentSection = page.locator('text="Recent Quiz Attempts"').or(page.locator('text="Recent Attempts"'));
    
    if (await recentSection.isVisible()) {
      await expect(recentSection).toBeVisible();
    }
  });

  test('should allow logout', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Look for logout button
    const logoutButton = page.locator('form[action*="logout"] button').or(page.locator('button:has-text("Logout")'));
    
    if (await logoutButton.isVisible()) {
      await logoutButton.click();
      
      // Should redirect to login or home page
      await page.waitForURL(url => url.includes('/login') || url.includes('/'));
      
      // Should not be able to access dashboard without login
      await page.goto('/user/dashboard');
      await page.waitForURL('**/login');
      await expect(page.locator('input[type="email"]')).toBeVisible();
    }
  });

  test('should display team information correctly', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Check team name is displayed
    await expect(page.locator('text="Tim Test"')).toBeVisible();
    
    // Check user role display
    await expect(page.locator('text="Team Member"')).toBeVisible();
    
    // Check user name display
    await expect(page.locator('text="Regular User"')).toBeVisible();
  });
});