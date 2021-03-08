
<!-- defines a block with links for navigation between pages -->

<script type="text/javascript">
    import { format } from 'svelte-i18n';
    import { Router, Link } from 'svelte-routing';
    import MenuDivider from './Menu/Divider.svelte';

    export let items = [];

    function onLinkUpdate(linkProperties) {
        return linkProperties.isCurrent ? {class: 'active'} : {};
    }
</script>

<template src="./Menu.spectre.html"></template>

<style src="./Menu.spectre.scss"></style>
