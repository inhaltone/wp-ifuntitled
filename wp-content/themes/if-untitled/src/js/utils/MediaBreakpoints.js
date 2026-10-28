/**
 * Mirrors $grid-breakpoints in src/styles/abstracts/_variables.scss.
 * Keep both in sync.
 */
export const BREAKPOINTS = Object.freeze({
    xs: 0,
    sm: 576,
    md: 768,
    lg: 992,
    xl: 1200,
    xxl: 1400,
});

const getBreakpoint = (label) => {
    if (!(label in BREAKPOINTS)) throw new Error(`Unknown breakpoint "${label}"`);
    return BREAKPOINTS[label];
};

/** MediaQueryList for media-breakpoint-up(): read .matches or listen for 'change' when crossing the breakpoint. */
export function mediaQueryUp(label = 'lg') {
    return window.matchMedia(`(min-width: ${getBreakpoint(label)}px)`);
}

/** Same semantics as the SCSS mixin media-breakpoint-up(). */
export function mediaBreakpointUp(label = 'lg') {
    return mediaQueryUp(label).matches;
}

/** Same semantics as the SCSS mixin media-breakpoint-down(). */
export function mediaBreakpointDown(label = 'lg') {
    return window.matchMedia(`(max-width: ${getBreakpoint(label) - 0.02}px)`).matches;
}

export function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}
