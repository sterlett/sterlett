
<script type="text/javascript">
    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';
    import { onMount } from 'svelte';

    export let header = [];
    export let items = [];

    export let emptyMessage = 'No items.';
    export let isStriped = false;

    // formatters.
    export let ratioFormatter = function (ratioValue) {
        return `<kbd>${ratioValue}</kbd>`;
    };

    export let benchmarkValueFormatter = function (benchmarkValue) {
        return numberFormatter.format(benchmarkValue);
    };

    // default cellIndex for the sort function.
    export let sortDefaultHeaderIndex = 0;
    // default modifier for the sort function (asc/desc).
    export let sortDefaultModifier = -1;
    // provides a sorting function, based on the given state.
    export let sortFunctionFactory = function (headerIndex, sortModifier) {
        return (left, right) => {
            if (left?.[headerIndex] > right?.[headerIndex]) {
                return sortModifier;
            }

            if (left?.[headerIndex] < right?.[headerIndex]) {
                return -1 * sortModifier;
            }

            return 0;
        };
    };

    let sortState = {headerIndex: sortDefaultHeaderIndex, sortModifier: sortDefaultModifier};
    let sortFunction;

    const numberFormatter = new Intl.NumberFormat();

    const onHeaderSort = function () {
        sortFunction = sortFunctionFactory(sortState.headerIndex, sortState.sortModifier);

        items = items.sort(sortFunction);
    };

    const onHeaderClick = function (event) {
        const sortHeaderIndexNew = event.target.cellIndex;

        if (sortHeaderIndexNew === sortState.headerIndex) {
            sortState.sortModifier = -1 * sortState.sortModifier;
        } else {
            sortState.headerIndex = event.target.cellIndex;
            sortState.sortModifier = 1;
        }

        onHeaderSort();
    };

    onMount(
        function () {
            onHeaderSort();
        },
    );
</script>

<template src="./Table.spectre.html"></template>

<style src="./Table.spectre.scss"></style>
