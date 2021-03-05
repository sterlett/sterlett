
<!-- holds a list with routes for the application,
     each possible route must be registered in this component -->

<script type="text/javascript" context="module">
    import CpuListPage from '@Page/Cpu/ListPage.svelte';
    import ApiHttpPage from '@Page/Api/HttpPage.svelte';
    import ApiConsolePage from '@Page/Api/ConsolePage.svelte';
    import AboutPage from '@Page/AboutPage.svelte';
    import NotFoundPage from '@Page/NotFoundPage.svelte';

    // export const variable from the module context doesn't mark it as a component state property
    export const pages = {
        CpuListPage,
        ApiHttpPage,
        ApiConsolePage,
        AboutPage,
        NotFoundPage,
    };
</script>
