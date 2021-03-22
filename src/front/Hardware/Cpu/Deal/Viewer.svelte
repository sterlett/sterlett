
<script type="text/javascript">
    import { onMount } from 'svelte';
    import { format } from 'svelte-i18n';
    import { fetchCpuDealList } from './Fetcher';
    import { DEAL_VIEWER_SESSION } from '@Page/Cpu/Deal/Context';
    import { getContext } from 'svelte';
    import Table from '@Hardware/Representation/Table';

    // defines a range (e.g. "0-10000") to traverse a deal dataset.
    export let benchmarkFrom = 0;
    export let benchmarkTo   = 10000;

    const numberFormatter = new Intl.NumberFormat();

    const rankingKey = `${benchmarkFrom}-${benchmarkTo}`;

    let cpuDeals = [];
    let dealListLoadPromise;

    // parent context: a viewer session
    const sessionStore = getContext(DEAL_VIEWER_SESSION);

    async function getDeals() {
        if ('undefined' !== typeof $sessionStore.deals) {
            return $sessionStore.deals;
        }

        const cpuDealList = await fetchCpuDealList();
        sessionStore.set({deals: cpuDealList});

        if ('object' !== typeof cpuDealList[rankingKey]) {
            throw new Error(`Deal data for the specified benchmark range '${rankingKey}' doesn't exist.`);
        }

        return cpuDealList[rankingKey];
    }

    const tableHeader = [
        {name: $format('Name')},
        {name: $format('V/B ratio')},
        {name: $format('Benchmarks'), tooltip: 'multiple core'},
        {name: $format('Price')},
    ];

    const tableEmptyMessage = $format('No CPU deals.');
    const tableIsStriped = false;

    const tableNameFormatter = function (item) {
        return `${item.name} <br /> <span class="label label-secondary">${item.prices[0].type}</span>`;
    };

    const tableRatioFormatter = function (item) {
        return `<kbd>${item.vb_ratio}</kbd>`;
    };

    const tablePriceFormatter = function (item) {
        const priceAmount = numberFormatter.format(item.prices[0].value);

        return `${priceAmount} ${item.prices[0].currency}`;
    };

    onMount(
        function () {
            dealListLoadPromise = getDeals();

            dealListLoadPromise.then(
                function (deals) {
                    // updating a set of deals for reactive rendering
                    cpuDeals = deals;
                },
            );
        },
    );
</script>

<template src="./Viewer.spectre.html"></template>

<style src="./Viewer.spectre.scss"></style>
