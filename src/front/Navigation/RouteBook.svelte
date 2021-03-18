
<!-- holds a list with routes for the application,
     each possible route must be registered in this component -->

<script type="text/javascript" context="module">
    import CpuListPage from '@Page/Cpu/ListPage';
    import CpuDealListPage from '@Page/Cpu/Deal/ListPage';
    import ApiHttpPage from '@Page/Api/HttpPage';
    import ApiConsolePage from '@Page/Api/ConsolePage';
    import AboutPage from '@Page/AboutPage';
    import NotFoundPage from '@Page/NotFoundPage';

    // export const variable from the module context doesn't mark it as a component state property
    export const pages = {
        CpuListPage,
        CpuDealListPage,
        ApiHttpPage,
        ApiConsolePage,
        AboutPage,
        NotFoundPage,
    };
</script>
