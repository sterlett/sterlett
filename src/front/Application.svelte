
<!-- defines a common skeleton for the site pages and core client-side logic -->

<script type="text/javascript">
    import '@Translation/i18nLoader.js';
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    import { APPLICATION_PAGE_TITLE } from '@./Context.js';
    import { setContext } from 'svelte';
    import { writable } from 'svelte/store';

    import { pages } from './Navigation/RouteBook.svelte';
    import Menu from './Navigation/Menu.svelte';
    import MenuDivider from './Navigation/Menu/Divider.svelte';
    import { Router, Route } from 'svelte-routing';

    const menuItems = [
        {
            title: $format('HARDWARE'),
            component: MenuDivider,
        },
        {
            title: $format('CPUs'),
            url: '/',
            path: '/',
            component: pages.CpuListPage,
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
