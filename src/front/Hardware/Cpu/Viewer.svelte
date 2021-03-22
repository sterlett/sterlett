
<script type="text/javascript">
    import { onMount } from 'svelte';
    import { format } from 'svelte-i18n';
    import { fetchCpuList } from './Fetcher';
    import Table from '@Hardware/Representation/Table';

    const tableHeader = [
        {name: $format('Name')},
        {name: $format('V/B ratio')},
        {name: $format('Benchmarks'), tooltip: 'multiple core'},
        {name: $format('Price')},
    ];

    const tableEmptyMessage = $format('No CPUs.');
    const tableIsStriped = false;

    const tableSortEnable = true;
    const tableSortDefaultHeaderIndex = 1;
    const tableSortDefaultModifier = -1;

    const resolveComparisonValue = function (item, headerIndex) {
        if (0 === headerIndex) {
            return item?.name;
        } else if (1 === headerIndex) {
            return parseFloat(item?.vbRatio);
        } else if (2 === headerIndex) {
            return parseFloat(item?.['benchmarks']?.[0]?.['value']);
        } else {
            const priceAverage = item.prices?.average?.toString();

            if ("string" !== typeof priceAverage) {
                return 0;
            }

            const priceAverageForComp = priceAverage.replace(/[^\d\.]/g, "");

            return parseFloat(priceAverageForComp);
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
