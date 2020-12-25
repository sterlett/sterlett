
<script type="text/javascript">
    import { format } from 'svelte-i18n';

    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';

    const notFoundMessage = $format('Page is not found.');
</script>

<template src="./NotFoundPage.spectre.html"></template>
