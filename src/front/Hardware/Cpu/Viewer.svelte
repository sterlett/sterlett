
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

    const tableSortDefaultHeaderIndex = 2;
    const tableSortDefaultModifier = -1;

    const resolveComparisonValue = function (item, headerIndex) {
        if (1 === headerIndex) {
            return item?.name;
        } else if (2 === headerIndex) {
            return parseFloat(item?.vbRatio);
        } else if (3 === headerIndex) {
            return parseFloat(item?.['benchmarks']?.[0]?.['value']);
        } else {
            return parseFloat(item?.prices?.average);
        }
    };

    const tableSortFunctionFactory = function (headerIndex, sortModifier) {
        return (left, right) => {
            const leftValue = resolveComparisonValue(left, headerIndex);
            const rightValue = resolveComparisonValue(right, headerIndex);

            if (leftValue > rightValue) {
                return sortModifier;
            }

            if (leftValue < rightValue) {
                return -1 * sortModifier;
            }

            return 0;
        };
    };

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
