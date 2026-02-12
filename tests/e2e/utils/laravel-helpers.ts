import { Page } from '@playwright/test';

export async function csrfToken(page: Page): Promise<string> {
    const response = await page.request.get('/__playwright__/csrf_token', {
        headers: { Accept: 'application/json' },
    });
    return await response.json();
}

export async function login(page: Page, attributes: Record<string, unknown> = {}) {
    const response = await page.request.post('/__playwright__/login', {
        headers: { Accept: 'application/json' },
        data: { attributes },
    });
    return await response.json();
}

export async function logout(page: Page) {
    await page.request.post('/__playwright__/logout', {
        headers: { Accept: 'application/json' },
    });
}

export async function create(
    page: Page,
    model: string,
    attributes: Record<string, unknown> = {},
    load: string[] = [],
) {
    const response = await page.request.post('/__playwright__/factory', {
        headers: { Accept: 'application/json' },
        data: { model, count: 1, attributes, load },
    });
    return await response.json();
}

export async function update(
    page: Page,
    model: string,
    id: number,
    attributes: Record<string, unknown> = {},
) {
    const response = await page.request.patch('/__playwright__/update', {
        headers: { Accept: 'application/json' },
        data: { model, id, attributes },
    });
    return await response.json();
}

export async function loginWithCompany(page: Page) {
    const user = await login(page);
    const company = await create(page, 'App\\Models\\Company', {
        user_id: user.id,
        name: 'Test Company',
    });
    await update(page, 'App\\Models\\User', user.id, {
        company_id: company.id,
        company_role: 'admin',
    });
    return { user, company };
}
