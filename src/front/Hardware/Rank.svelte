
<script type="text/javascript">
    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';
    import { onMount } from 'svelte';
    import avatar from 'avatar-initials';

    export let elementPrefix = 'rank-';
    export let name;

    const elementId = `${elementPrefix}${name}`;

    onMount(
        function () {
            const elementImage = document.getElementById(elementId);

            const avatarOptions = {
                useGravatar: false,
                initials: name
            };

            avatar.from(elementImage, avatarOptions);
        },
    );
</script>

<template src="./Rank.spectre.html"></template>

<style src="./Rank.spectre.scss"></style>
