
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    import { onMount } from 'svelte';
    import { dispatchPageShownEvent } from '@Page/ShowTrigger.js';

    export let location;

    let selfElement;
    const title = $format('Not Found');
    const notFoundMessage = $format('Page is not found.');

    onMount(
        function () {
            dispatchPageShownEvent(selfElement);
        },
    );
</script>

<template src="./NotFoundPage.spectre.html"></template>
