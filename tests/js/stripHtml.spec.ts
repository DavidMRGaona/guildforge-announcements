import { describe, expect, it } from 'vitest';
import { stripHtml } from '../../resources/js/utils/stripHtml';

describe('stripHtml', () => {
    it('separates blocks with a space', () => {
        expect(stripHtml('<p>a</p><p>b</p>')).toBe('a b');
        expect(stripHtml('<ul><li>uno</li><li>dos</li></ul>')).toBe('uno dos');
    });

    it('does not split words around inline tags', () => {
        expect(stripHtml('<p>Hola <strong>mundo</strong>!</p>')).toBe('Hola mundo!');
    });

    it('collapses repeated whitespace and trims', () => {
        expect(stripHtml('  <p>a</p>\n\n<p>  b   c </p>  ')).toBe('a b c');
    });

    it('returns an empty string for markup without text', () => {
        expect(stripHtml('<p></p><br>')).toBe('');
    });
});
