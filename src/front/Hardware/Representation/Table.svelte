
<script type="text/javascript">
    import { fly } from 'svelte/transition';
    import { expoOut } from 'svelte/easing';
    import { onMount } from 'svelte';

    // [{name: '', tooltip: ''}, ...]
    export let headerItems = [];

    export let items = [];

    export let emptyMessage = 'No items.';
    export let isStriped = false;

    // formatter: item name.
    export let nameFormatter = function (item) {
        return item.name;
    };

    // formatter: v/b ratio.
    export let ratioFormatter = function (item) {
        return `<kbd>${item.vbRatio}</kbd>`;
    };

    // formatter: benchmark value.
    export let benchmarkValueFormatter = function (benchmark) {
        return numberFormatter.format(benchmark.value);
    };

    // formatter: price tag.
    export let priceFormatter = function (item) {
        return item.prices.average;
    };

    // enable/disable table sorting.
    export let sortEnable = false;
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

    const numberFormatter = new Intl.NumberFormat();

    let sortState = {headerIndex: sortDefaultHeaderIndex, sortModifier: sortDefaultModifier};
    let sortFunction;

    const onHeaderSort = function () {
        sortFunction = sortFunctionFactory(sortState.headerIndex, sortState.sortModifier);

        items = items.sort(sortFunction);
    };

    const onHeaderClick = function (event) {
        if (!sortEnable) {
            return;
        }

        const sortHeaderIndexNew = event.target.closest('th').cellIndex;

        if (sortHeaderIndexNew === sortState.headerIndex) {
            sortState.sortModifier = -1 * sortState.sortModifier;
        } else {
            sortState.headerIndex = sortHeaderIndexNew;
            sortState.sortModifier = 1;
        }

        onHeaderSort();
    };

    onMount(
        function () {
            if (sortEnable) {
                onHeaderSort();
            }
        },
    );
</script>

<template src="./Table.spectre.html"></template>

<style src="./Table.spectre.scss"></style>
