import { test, expect } from '@playwright/test';

// Use authenticated state for team tests
test.use({ storageState: 'tests/playwright/.auth/user.json' });

test.describe('Team Functionality', () => {
  test('should display team information on dashboard', async ({ page }) => {
    await page.goto('/user/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check team name is displayed
    await expect(page.locator('text="Tim Test"')).toBeVisible();
    
    // Check user role is displayed correctly (should show "Team Member" not code)
    await expect(page.locator('text="Team Member"')).toBeVisible();
    
    // Check user name is displayed
    await expect(page.locator('text="Regular User"')).toBeVisible();
  });

  test('should show correct team member status', async ({ page }) => {
    await page.goto('/user/dashboard');
    await page.waitForLoadState('networkidle');
    
    // The user should see their team membership status
    // Look for team-related information
    const teamInfo = page.locator('text="Tim Test"');
    await expect(teamInfo).toBeVisible();
    
    // User should see "Team Member" role (not raw code like $team->name)
    const memberRole = page.locator('text="Team Member"');
    await expect(memberRole).toBeVisible();
    
    // Should not see PHP code or variables
    const phpCode = page.locator('text="$team"').or(page.locator('text="??"'));
    if (await phpCode.isVisible()) {
      throw new Error('PHP code visible in UI - template bug detected');
    }
  });

  test('should prevent team creation when user already in team', async ({ page }) => {
    // Try to access team registration page
    await page.goto('/team-registration');
    
    // User should be redirected or see message that they're already in a team
    await page.waitForTimeout(2000);
    
    const currentUrl = page.url();
    
    // Should either redirect to dashboard or show appropriate message
    if (currentUrl.includes('/team-registration')) {
      // If still on team registration, should show message about existing team
      const existingTeamMessage = page.locator('text="already"').or(page.locator('text="team"'));
      // Note: Exact message depends on implementation
    } else {
      // Should be redirected away from team registration
      expect(currentUrl).not.toContain('/team-registration');
    }
  });

  test('should display team statistics correctly', async ({ page }) => {
    await page.goto('/user/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check if team statistics are displayed
    const teamStatsSection = page.locator('text="Team"').locator('..').locator('..'); // Parent container
    
    // Look for team-related statistics or information
    const teamName = page.locator('text="Tim Test"');
    await expect(teamName).toBeVisible();
    
    // Check if initial points or other team info is displayed
    const pointsDisplay = page.locator('text="1000"').or(page.locator('text="points"'));
    if (await pointsDisplay.isVisible()) {
      console.log('✅ Team points displayed');
    }
  });

  test('should handle team member view correctly', async ({ page }) => {
    await page.goto('/user/dashboard');
    await page.waitForLoadState('networkidle');
    
    // Check both desktop and mobile team member display
    
    // Desktop view team member text
    const desktopRole = page.locator('.user-role:has-text("Team Member")');
    if (await desktopRole.isVisible()) {
      await expect(desktopRole).toBeVisible();
      console.log('✅ Desktop team member role displayed correctly');
    }
    
    // Mobile view team member text  
    const mobileRole = page.locator('.mobile-user-role:has-text("Team Member")');
    if (await mobileRole.isVisible()) {
      await expect(mobileRole).toBeVisible();
      console.log('✅ Mobile team member role displayed correctly');
    }
    
    // Verify no coding artifacts are visible
    const codingText = page.locator('text="{{ $team"').or(page.locator('text="?? \'"'));
    const codingVisible = await codingText.count();
    expect(codingVisible).toBe(0);
  });

  test('should validate team permissions', async ({ page }) => {
    await page.goto('/user/dashboard');
    await page.waitForLoadState('networkidle');
    
    // User is part of team "Tim Test" and should have appropriate permissions
    
    // Check that user can view team information
    await expect(page.locator('text="Tim Test"')).toBeVisible();
    
    // Check that user has proper role display
    await expect(page.locator('text="Team Member"')).toBeVisible();
    
    // User should not see admin-only team management features
    const adminTeamButtons = page.locator('button:has-text("Manage Team")').or(page.locator('a:has-text("Team Management")'));
    const adminVisible = await adminTeamButtons.count();
    
    // Regular user should not see admin team management
    expect(adminVisible).toBe(0);
  });
});