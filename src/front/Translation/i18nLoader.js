
import { addMessages as translationAdd } from 'svelte-i18n';
import { init as translationInit } from 'svelte-i18n';
import { getLocaleFromNavigator } from 'svelte-i18n';

// Application
import ApplicationEnUs from '@_translations/en-US/Application.json';
import ApplicationRuRu from '@_translations/ru-RU/Application.json';

translationAdd('en-US', ApplicationEnUs);
translationAdd('ru-RU', ApplicationRuRu);

// Navigation/Menu
import NavigationMenuEnUs from '@_translations/en-US/Navigation/Menu.json';
import NavigationMenuRuRu from '@_translations/ru-RU/Navigation/Menu.json';

translationAdd('en-US', NavigationMenuEnUs);
translationAdd('ru-RU', NavigationMenuRuRu);

// Page/Cpu/ListPage
import PageCpuListPageEnUs from '@_translations/en-US/Page/Cpu/ListPage.json';
import PageCpuListPageRuRu from '@_translations/ru-RU/Page/Cpu/ListPage.json';

translationAdd('en-US', PageCpuListPageEnUs);
translationAdd('ru-RU', PageCpuListPageRuRu);

// Page/Cpu/Deal/ListPage
import PageCpuDealListPageEnUs from '@_translations/en-US/Page/Cpu/Deal/ListPage.json';
import PageCpuDealListPageRuRu from '@_translations/ru-RU/Page/Cpu/Deal/ListPage.json';

translationAdd('en-US', PageCpuDealListPageEnUs);
translationAdd('ru-RU', PageCpuDealListPageRuRu);

// Page/NotFoundPage
import PageNotFoundPageEnUs from '@_translations/en-US/Page/NotFoundPage.json';
import PageNotFoundPageRuRu from '@_translations/ru-RU/Page/NotFoundPage.json';

translationAdd('en-US', PageNotFoundPageEnUs);
translationAdd('ru-RU', PageNotFoundPageRuRu);

// Hardware/Cpu/Viewer
import HardwareCpuViewerEnUs from '@_translations/en-US/Hardware/Cpu/Viewer.json';
import HardwareCpuViewerRuRu from '@_translations/ru-RU/Hardware/Cpu/Viewer.json';

translationAdd('en-US', HardwareCpuViewerEnUs);
translationAdd('ru-RU', HardwareCpuViewerRuRu);

// Hardware/Cpu/Deal/Viewer
import HardwareCpuDealViewerEnUs from '@_translations/en-US/Hardware/Cpu/Deal/Viewer.json';
import HardwareCpuDealViewerRuRu from '@_translations/ru-RU/Hardware/Cpu/Deal/Viewer.json';

translationAdd('en-US', HardwareCpuDealViewerEnUs);
translationAdd('ru-RU', HardwareCpuDealViewerRuRu);

translationInit(
    {
        fallbackLocale: 'en-US',
        initialLocale: getLocaleFromNavigator(),
    },
);
