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
