
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { onMount } from 'svelte';
    import { dispatchPageShownEvent } from '@Page/ShowTrigger.js';

    import CpuViewer from '@Hardware/Cpu/Viewer.svelte';

    export let location;

    let selfElement;
    const title = $format('CPUs');

    onMount(
        function () {
            dispatchPageShownEvent(selfElement);
        },
    );
</script>

<template src="./ListPage.spectre.html"></template>
