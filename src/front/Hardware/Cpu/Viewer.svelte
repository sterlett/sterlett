
<script type="text/javascript">
    import { onMount } from 'svelte';
    import { format } from 'svelte-i18n';
    import { fetchCpuList } from './Fetcher.js';
    import Table from '@Hardware/Representation/Table.svelte';

    const tableHeader = [
        $format('Image'),
        $format('Name'),
        $format('V/B ratio'),
        $format('Benchmarks'),
        $format('Price'),
    ];
    const tableEmptyMessage = $format('No CPUs.');
    const tableIsStriped = true;

    let cpus = [];
    let cpuListLoadPromise;

    onMount(
        function () {
            cpuListLoadPromise = fetchCpuList();

            cpuListLoadPromise.then(
                function (cpuList) {
                    cpus = cpuList;
                },
            );
        },
    );
</script>

<template src="./Viewer.spectre.html"></template>

<style src="./Viewer.spectre.scss"></style>
