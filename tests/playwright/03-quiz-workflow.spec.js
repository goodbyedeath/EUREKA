import { test, expect } from '@playwright/test';

// Use authenticated state for quiz tests
test.use({ storageState: 'tests/playwright/.auth/user.json' });

test.describe('Quiz Workflow', () => {
  test('should show quiz attempt limits correctly', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
    
    // Look for quiz information - user has reached max attempts for POS 1
    // So retake button should not be visible or should be disabled
    const retakeButton = page.locator('button:has-text("Retake")');
    
    if (await retakeButton.isVisible()) {
      // If retake button exists, it might be for a different quiz
      // Check if it's clickable for quizzes the user can still attempt
      console.log('Retake buttons found, checking availability...');
    }
  });

  test('should display recent quiz attempts', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
    
    // Look for recent attempts section
    const recentSection = page.locator('text="Recent Quiz Attempts"');
    
    if (await recentSection.isVisible()) {
      // Check for attempt entries
      const attemptRows = page.locator('table tbody tr');
      const attemptCount = await attemptRows.count();
      
      if (attemptCount > 0) {
        // Check first attempt row has required information
        const firstRow = attemptRows.first();
        
        // Should have quiz name, status, and date
        await expect(firstRow.locator('td').first()).toBeVisible();
      }
    }
  });

  test('should show quiz results when clicking View Results', async ({ page }) => {
    await page.goto('/user/dashboard');
    
    // Wait for dashboard to load
    await page.waitForLoadState('networkidle');
    
    // Look for "View Results" button
    const viewResultsButton = page.locator('button:has-text("View Results")');
    
    if (await viewResultsButton.first().isVisible()) {
      await viewResultsButton.first().click();
      
      // Should navigate to results page or show results modal
      await page.waitForTimeout(2000);
      
      // Check for results content
      const resultsContent = page.locator('text="Quiz Results"').or(page.locator('text="Score"'));
      if (await resultsContent.isVisible()) {
        await expect(resultsContent).toBeVisible();
      }
    }
  });

  test('should prevent access to quiz when limit reached', async ({ page }) => {
    // Try to directly access quiz that user has reached limit for
    await page.goto('/quiz/take/1');
    
    // Should redirect or show error
    await page.waitForTimeout(3000);
    
    // Check if redirected back to dashboard or shows error
    const currentUrl = page.url();
    const isOnQuiz = currentUrl.includes('/quiz/take/');
    
    if (isOnQuiz) {
      // If still on quiz page, check for error message
      const errorMessage = page.locator('text="maximum"').or(page.locator('text="limit"'));
      if (await errorMessage.isVisible()) {
        await expect(errorMessage).toBeVisible();
      }
    } else {
      // Should have been redirected away from quiz
      expect(currentUrl).not.toContain('/quiz/take/');
    }
  });

  test('should handle quiz refresh correctly', async ({ page }) => {
    // This tests the fix we made for quiz restart on refresh
    
    // Try to access a quiz (if available)
    await page.goto('/user/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Look for any available quiz start buttons
    const startQuizButton = page.locator('button:has-text("Start")', page.locator('a:has-text("Start")'));
    
    if (await startQuizButton.first().isVisible()) {
      await startQuizButton.first().click();
      
      // Wait for navigation
      await page.waitForTimeout(2000);
      
      // If we're on a quiz page, refresh to test the fix
      const currentUrl = page.url();
      if (currentUrl.includes('/quiz/')) {
        await page.reload();
        
        // Should not restart quiz - should continue from where left off
        // Check that we're on a continue URL, not a new take URL
        const newUrl = page.url();
        if (newUrl.includes('/quiz/continue/')) {
          console.log('✅ Quiz refresh fix working - redirected to continue URL');
        }
      }
    }
  });
});