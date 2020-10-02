
import { addMessages as translationAdd } from 'svelte-i18n';
import { init as translationInit } from 'svelte-i18n';
import { getLocaleFromNavigator } from 'svelte-i18n';

// Navigation/Menu
import NavigationMenuEnUs from '@_translations/en-US/Navigation/Menu.json';
import NavigationMenuRuRu from '@_translations/ru-RU/Navigation/Menu.json';

translationAdd('en-US', NavigationMenuEnUs);
translationAdd('ru-RU', NavigationMenuRuRu);

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

translationInit(
    {
        fallbackLocale: 'en-US',
        initialLocale: getLocaleFromNavigator(),
    },
);
