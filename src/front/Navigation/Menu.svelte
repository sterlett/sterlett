
<!-- defines a block with links for navigation between pages -->

<script type="text/javascript">
    import { Router, Link } from 'svelte-routing';

    export let routes = [];

    function onLinkUpdate(linkProperties) {
        return linkProperties.isCurrent ? {class: 'active'} : {};
    }
</script>

<template src="./Menu.spectre.html"></template>

<style src="./Menu.spectre.scss"></style>
