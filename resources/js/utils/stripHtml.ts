// Tags that start or end a line of text: a space goes in their place, so neighbouring blocks stay apart
const BLOCK_TAG =
    /<\/?(?:p|div|br|hr|li|ul|ol|h[1-6]|blockquote|pre|table|thead|tbody|tr|td|th)\b[^>]*>/gi;

/**
 * Plain text of an HTML fragment for the banner's single line.
 */
export function stripHtml(html: string): string {
    return html
        .replace(BLOCK_TAG, ' ')
        .replace(/<[^>]*>/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}
