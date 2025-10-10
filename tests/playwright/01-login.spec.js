import { test, expect } from '@playwright/test';

test.describe('Login Functionality', () => {
  test('should display login page correctly', async ({ page }) => {
    await page.goto('/login');
    
    // Check page title
    await expect(page).toHaveTitle(/EUREKA/);
    
    // Check form elements exist
    await expect(page.locator('input[type="email"]')).toBeVisible();
    await expect(page.locator('input[type="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]:has-text("Sign In")')).toBeVisible();
    
    // Check language switcher
    await expect(page.locator('text="English"')).toBeVisible();
  });

  test('should successfully login with valid credentials', async ({ page }) => {
    await page.goto('/login');
    
    // Fill login form
    await page.fill('input[type="email"]', 'user@gmail.com');
    await page.fill('input[type="password"]', '123');
    
    // Submit form
    await page.click('button[type="submit"]:has-text("Sign In")');
    
    // Wait for redirect and check URL
    await page.waitForURL('**/user/dashboard', { timeout: 10000 });
    
    // Verify we're on dashboard
    await expect(page.locator('text="Regular User"')).toBeVisible();
    await expect(page.locator('text="Team Member"')).toBeVisible();
  });

  test('should show error for invalid credentials', async ({ page }) => {
    await page.goto('/login');
    
    // Fill login form with wrong credentials
    await page.fill('input[type="email"]', 'user@gmail.com');
    await page.fill('input[type="password"]', 'wrongpassword');
    
    // Submit form
    await page.click('button[type="submit"]:has-text("Sign In")');
    
    // Should stay on login page or show error
    // Wait a moment for any error messages to appear
    await page.waitForTimeout(2000);
    
    // Check if we're still on login page or if error message appears
    const currentUrl = page.url();
    expect(currentUrl).toContain('/login');
  });

  test('should require email and password fields', async ({ page }) => {
    await page.goto('/login');
    
    // Try to submit empty form
    await page.click('button[type="submit"]:has-text("Sign In")');
    
    // Check for HTML5 validation or custom validation
    const emailField = page.locator('input[type="email"]');
    const passwordField = page.locator('input[type="password"]');
    
    // These fields should be required
    await expect(emailField).toHaveAttribute('required');
    await expect(passwordField).toHaveAttribute('required');
  });

  test('should toggle password visibility', async ({ page }) => {
    await page.goto('/login');
    
    const passwordField = page.locator('input[type="password"]');
    const toggleButton = page.locator('button:has(i.fa-eye)');
    
    // Fill password field
    await passwordField.fill('testpassword');
    
    // Check if password is hidden (type="password")
    await expect(passwordField).toHaveAttribute('type', 'password');
    
    // Click toggle button if it exists
    if (await toggleButton.isVisible()) {
      await toggleButton.click();
      
      // Check if password is now visible (type="text")
      await expect(passwordField).toHaveAttribute('type', 'text');
      
      // Click again to hide
      await toggleButton.click();
      await expect(passwordField).toHaveAttribute('type', 'password');
    }
  });
});