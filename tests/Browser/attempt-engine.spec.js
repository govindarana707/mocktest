import { expect, test } from '@playwright/test';

test('guests cannot open protected student examination routes', async ({ page }) => {
    await page.goto('/student/exams');

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole('heading', { name: /welcome back/i })).toBeVisible();
});

test('private environment and database paths are not served', async ({ page }) => {
    for (const path of ['/.env', '/database/database.sqlite']) {
        const response = await page.goto(path);

        expect(response?.status()).toBe(404);
    }
});
