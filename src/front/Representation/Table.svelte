
<script type="text/javascript">
    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    export let header = [];
    export let items = [];
    export let emptyMessage = 'No items.';
    export let isStriped = false;
</script>

<template src="./Table.spectre.html"></template>

<style src="./Table.spectre.scss"></style>
