import './style.scss';
import { onMounted, onPageMounted, onLoaded } from '@/js/utils/Hooks.js';
import Router, { useRouterLink } from '@/js/utils/Router.js';
import RouterTransition from '@/js/utils/RouterTransition.js';
import { lazyComponent } from '@/js/utils/LazyComponent.js';
import MenuToggle from '@/js/components/MenuToggle.js';

// Global: runs on every page.
onMounted(() => {
    new RouterTransition();
    useRouterLink();
    new MenuToggle({
        buttonSelector: '.menu-toggle',
        targetSelector: '#app-header',
    });

    // Code-split: only downloaded when [data-reveal] exists on the page.
    lazyComponent('[data-reveal]', () => import('@/js/components/Reveal.js'), {
        selector: '[data-reveal]',
    });
});

// Page-scoped: runs only when <main id="front-page"> exists.
onPageMounted(Router.FRONT_PAGE.routeId, ({ element }) => {
    element.classList.add('is-mounted');
});

onLoaded(() => {
    document.documentElement.classList.add('is-loaded');
});
