import './style.scss';
import { onMounted, onPageMounted, onLoaded } from '@/js/utils/Hooks.js';
import Router, { useRouterLink } from '@/js/utils/Router.js';
import RouterTransition from '@/js/utils/RouterTransition.js';
import { lazyComponent } from '@/js/utils/LazyComponent.js';
import PrimaryNav from '@/js/components/PrimaryNav.js';
import HoverTitle from '@/js/components/HoverTitle.js';

// Global: runs on every page.
onMounted(() => {
    new RouterTransition();
    useRouterLink();
    new PrimaryNav();

    // Code-split: only downloaded when [data-reveal] exists on the page.
    lazyComponent('[data-reveal]', () => import('@/js/components/Reveal.js'), {
        selector: '[data-reveal]',
    });
    lazyComponent('[data-archive-filter]', () => import('@/js/components/ArchiveFilter.js'));
    lazyComponent('[data-infinite-scroll]', () => import('@/js/components/InfiniteScroll.js'));
    lazyComponent('[data-sticky-center]', () => import('@/js/components/StickyCenter.js'));
    lazyComponent('[data-contact-map]', () => import('@/js/components/ContactMap.js'));
    lazyComponent('[data-media-video]', () => import('@/js/components/AmbientVideo.js'));
    lazyComponent('[data-media-youtube]', () => import('@/js/components/YoutubeVideo.js'));
});

// Page-scoped: runs only when <main id="front-page"> exists.
onPageMounted(Router.FRONT_PAGE.routeId, ({ element }) => {
    element.classList.add('is-mounted');
    new HoverTitle();
});

onLoaded(() => {
    document.documentElement.classList.add('is-loaded');
});
