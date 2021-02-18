
<!-- defines a common skeleton for the site pages and core client-side logic -->

<script type="text/javascript">
    import '@Translation/i18nLoader.js';
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    import { pages } from './Navigation/RouteBook.svelte';
    import Menu from './Navigation/Menu.svelte';
    import { Router, Route } from 'svelte-routing';

    const routes = [
        {
            title: $format('CPUs'),
            url: '/',
            path: '/',
            component: pages.CpuListPage,
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
            title: $format('About'),
            url: '/about',
            path: 'about',
            component: pages.AboutPage,
        },
        {
            title: '',
            url: '',
            path: '',
            component: pages.NotFoundPage,
        },
    ];

    const title = $format('sterlett/sterlett');
    let pageTitle;

    function onPageShown (event) {
        const pageElement = event.detail.pageElement;

        pageTitle = pageElement.dataset.title;
    }
</script>

<template src="./Application.spectre.html"></template>

<!-- Component.CSS-framework.CSS-preprocessor-or-native -->
<style src="./Application.spectre.scss"></style>
