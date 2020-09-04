
<script type="text/javascript">
    import { onMount } from 'svelte';
    import { format } from 'svelte-i18n';
    import Cpu from '@Hardware/Cpu.js';
    import Table from '@Hardware/Representation/Table.svelte';

    async function fetchCpuList() {
        try {
            var response = await fetch('/api/cpu/list.json');
        } catch (error) {
            throw new Error('Unable to fetch CPU list. ' + error);
        }

        try {
            var responseJson = await response.json();
        } catch (error) {
            throw new Error('Unable to deserialize CPU list. ' + error);
        }

        if ('undefined' === typeof responseJson.items) {
            throw new Error('Unable to validate CPU list. Invalid data path.');
        }

        let cpus = [];

        for (const item of responseJson.items) {
            const cpu = Cpu.prototype.fromJson(item);

            cpus = [...cpus, cpu];
        }

        return cpus;
    }

    const tableHeader = [
        $format('Image'),
        $format('Name'),
        $format('Price'),
    ];

    const tableEmptyMessage = $format('No CPUs.');

    let cpuListLoadPromise;

    onMount(
        function () {
            cpuListLoadPromise = fetchCpuList();
        },
    );
</script>

<template src="./Viewer.spectre.html"></template>

<style src="./Viewer.spectre.scss"></style>
