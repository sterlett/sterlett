
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
            title: $format('About'),
            url: '/about',
            path: 'about',
            component: pages.AboutPage,
        },
        {
            title: $format('Not found'),
            url: '',
            path: '',
            component: pages.NotFoundPage,
        },
    ];
</script>

<template src="./Application.spectre.html"></template>

<!-- Component.CSS-framework.CSS-preprocessor-or-native -->
<style src="./Application.spectre.scss"></style>
