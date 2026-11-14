import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { PopupTriggers } from '../../resources/js/types/announcement';
import {
    armPopupTriggers,
    EXIT_INTENT_FALLBACK_SECONDS,
    type PopupEnvironment,
} from '../../resources/js/utils/popupTriggers';

const desktop: PopupEnvironment = { isMobile: false, canDetectExitIntent: true, pageViews: 1 };
const phone: PopupEnvironment = { isMobile: true, canDetectExitIntent: false, pageViews: 1 };

function triggers(overrides: Partial<PopupTriggers> = {}): PopupTriggers {
    return {
        delay_seconds: null,
        scroll_percent: null,
        exit_intent: false,
        page_views: null,
        ...overrides,
    };
}

function setScrollable(height: number, viewport: number): void {
    Object.defineProperty(document.documentElement, 'scrollHeight', {
        configurable: true,
        value: height,
    });
    Object.defineProperty(window, 'innerHeight', { configurable: true, value: viewport });
}

function scrollTo(y: number): void {
    Object.defineProperty(window, 'scrollY', { configurable: true, value: y });
    window.dispatchEvent(new Event('scroll'));
}

function leaveThroughTheTop(): void {
    document.dispatchEvent(new MouseEvent('mouseout', { clientY: 0, relatedTarget: null }));
}

describe('armPopupTriggers', () => {
    let controller: AbortController;

    beforeEach(() => {
        vi.useFakeTimers();
        controller = new AbortController();
        setScrollable(3000, 1000);
        scrollTo(0);
    });

    afterEach(() => {
        controller.abort();
        vi.useRealTimers();
    });

    it('opens after the delay', () => {
        const open = vi.fn();
        armPopupTriggers(triggers({ delay_seconds: 5 }), desktop, open, controller.signal);

        vi.advanceTimersByTime(4999);
        expect(open).not.toHaveBeenCalled();
        vi.advanceTimersByTime(1);
        expect(open).toHaveBeenCalledTimes(1);
    });

    it('opens once the page is scrolled far enough, and only by scrolling', () => {
        const open = vi.fn();
        armPopupTriggers(triggers({ scroll_percent: 50 }), desktop, open, controller.signal);

        scrollTo(900);
        expect(open).not.toHaveBeenCalled();
        scrollTo(1000);
        expect(open).toHaveBeenCalledTimes(1);
    });

    it('never opens by scrolling on a page that cannot be scrolled', () => {
        setScrollable(800, 1000);
        const open = vi.fn();
        armPopupTriggers(triggers({ scroll_percent: 10 }), desktop, open, controller.signal);

        scrollTo(0);
        vi.advanceTimersByTime(60_000);
        expect(open).not.toHaveBeenCalled();
    });

    it('opens when the mouse leaves through the top on desktop', () => {
        const open = vi.fn();
        armPopupTriggers(triggers({ exit_intent: true }), desktop, open, controller.signal);

        document.dispatchEvent(new MouseEvent('mouseout', { clientY: 300, relatedTarget: null }));
        expect(open).not.toHaveBeenCalled();
        leaveThroughTheTop();
        expect(open).toHaveBeenCalledTimes(1);
    });

    it('uses the delay instead of exit intent on a phone, or the fallback without one', () => {
        const withDelay = vi.fn();
        armPopupTriggers(
            triggers({ exit_intent: true, delay_seconds: 3 }),
            phone,
            withDelay,
            controller.signal
        );
        const withoutDelay = vi.fn();
        armPopupTriggers(triggers({ exit_intent: true }), phone, withoutDelay, controller.signal);

        leaveThroughTheTop();
        expect(withDelay).not.toHaveBeenCalled();
        vi.advanceTimersByTime(3000);
        expect(withDelay).toHaveBeenCalledTimes(1);
        expect(withoutDelay).not.toHaveBeenCalled();
        vi.advanceTimersByTime(EXIT_INTENT_FALLBACK_SECONDS * 1000 - 3000);
        expect(withoutDelay).toHaveBeenCalledTimes(1);
    });

    it('opens at once from the page view that meets the count', () => {
        const early = vi.fn();
        armPopupTriggers(
            triggers({ page_views: 3 }),
            { ...desktop, pageViews: 2 },
            early,
            controller.signal
        );
        const reached = vi.fn();
        armPopupTriggers(
            triggers({ page_views: 3 }),
            { ...desktop, pageViews: 3 },
            reached,
            controller.signal
        );

        vi.advanceTimersByTime(60_000);
        expect(early).not.toHaveBeenCalled();
        expect(reached).toHaveBeenCalledTimes(1);
    });

    it('opens once with the first trigger met, and the others are dropped', () => {
        const open = vi.fn();
        armPopupTriggers(
            triggers({ delay_seconds: 10, scroll_percent: 20, exit_intent: true }),
            desktop,
            open,
            controller.signal
        );

        scrollTo(500);
        leaveThroughTheTop();
        vi.advanceTimersByTime(10_000);
        expect(open).toHaveBeenCalledTimes(1);
    });

    it('drops every timer and listener when the signal aborts', () => {
        const open = vi.fn();
        armPopupTriggers(
            triggers({ delay_seconds: 1, scroll_percent: 20, exit_intent: true }),
            desktop,
            open,
            controller.signal
        );

        controller.abort();
        scrollTo(2000);
        leaveThroughTheTop();
        vi.advanceTimersByTime(60_000);
        expect(open).not.toHaveBeenCalled();
    });
});
