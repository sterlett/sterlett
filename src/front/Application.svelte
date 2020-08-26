
<!-- defines a common skeleton for the site pages and core client-side logic -->

<script type="text/javascript">
    import { pages } from './Navigation/RouteBook.svelte';
    import Menu from './Navigation/Menu.svelte';
    import { Router, Route } from 'svelte-routing';

    let routes = [
        {
            title: 'CPUs',
            url: '/',
            path: '/',
            component: pages.home,
        },
        {
            title: 'About',
            url: '/about',
            path: 'about',
            component: pages.about,
        },
    ];
</script>

<template src="./Application.spectre.html"></template>

<!-- Component.CSS-framework.CSS-preprocessor-or-native -->
<style src="./Application.spectre.scss"></style>
