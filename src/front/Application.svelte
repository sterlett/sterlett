
<!-- defines a common skeleton for the site pages and core client-side logic -->

<script type="text/javascript">
    import '@Translation/i18nLoader';
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    import { APPLICATION_PAGE_TITLE } from '@./Context';
    import { setContext } from 'svelte';
    import { writable } from 'svelte/store';

    import { pages } from './Navigation/RouteBook';
    import Menu from './Navigation/Menu';
    import MenuDivider from './Navigation/Menu/Divider';
    import { Router, Route } from 'svelte-routing';

    const menuItems = [
        {
            title: $format('CPUs'),
            component: MenuDivider,
        },
        {
            title: $format('Ratio'),
            url: '/',
            path: '/',
            component: pages.CpuListPage,
        },
        {
            title: $format('Deals'),
            url: '/cpu/deals',
            path: '/cpu/deals',
            component: pages.CpuDealListPage,
        },
        {
            component: MenuDivider,
        },
        {
            title: $format('HTTP API'),
            url: '/http',
            path: '/http',
            component: pages.ApiHttpPage,
        },
        {
            title: $format('Console API'),
            url: '/console',
            path: '/console',
            component: pages.ApiConsolePage,
        },
        {
            component: MenuDivider,
        },
        {
            title: $format('About'),
            url: '/about',
            path: 'about',
            component: pages.AboutPage,
        },
        {
            component: pages.NotFoundPage,
        },
    ];

    const title = $format('sterlett/sterlett');

    let pageTitleStore = writable('');
    setContext(APPLICATION_PAGE_TITLE, pageTitleStore);
</script>

<template src="./Application.spectre.html"></template>

<!-- Component.CSS-framework.CSS-preprocessor-or-native -->
<style src="./Application.spectre.scss"></style>
