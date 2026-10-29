/** Laravel's CSRF token, which it also sets as the XSRF-TOKEN cookie. */
export function xsrfToken(): string {
    const cookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=')[1]) : '';
}

export type RequestErrors = Record<string, string>;

export class HttpError extends Error {
    constructor(
        public status: number,
        public errors: RequestErrors = {},
    ) {
        super(
            status === 429
                ? 'Too many requests — please wait a minute and try again.'
                : (Object.values(errors)[0] ??
                      'Something went wrong. Please try again.'),
        );
    }
}

/**
 * fetch() with the headers Laravel needs for a same-origin JSON/form POST.
 * Sends `Accept: application/json` so validation failures come back as 422 JSON
 * (rather than a redirect), and turns them into an HttpError.
 */
export async function post(
    url: string,
    body: unknown,
    init: RequestInit = {},
): Promise<Response> {
    const isForm = body instanceof FormData;
    const headers = new Headers(init.headers);

    headers.set('Accept', 'application/json');
    headers.set('X-XSRF-TOKEN', xsrfToken());

    // For FormData the browser sets the multipart Content-Type (with boundary) itself.
    if (!isForm) {
        headers.set('Content-Type', 'application/json');
    }

    const response = await fetch(url, {
        ...init,
        method: 'POST',
        credentials: 'same-origin',
        headers,
        body: isForm ? body : JSON.stringify(body),
    });

    if (!response.ok) {
        const json = await response.json().catch(() => ({}));
        const errors = Object.fromEntries(
            Object.entries(json.errors ?? {}).map(([field, messages]) => [
                field,
                (messages as string[])[0],
            ]),
        );

        throw new HttpError(response.status, errors);
    }

    return response;
}

/** POST, then save the response body as a file using the server's filename. */
export async function downloadPost(url: string, body: unknown): Promise<void> {
    const response = await post(url, body);
    const disposition = response.headers.get('Content-Disposition') ?? '';
    const filename =
        /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(disposition)?.[1] ??
        'resume';

    const link = document.createElement('a');
    link.href = URL.createObjectURL(await response.blob());
    link.download = decodeURIComponent(filename);
    link.click();

    setTimeout(() => URL.revokeObjectURL(link.href), 1000);
}
