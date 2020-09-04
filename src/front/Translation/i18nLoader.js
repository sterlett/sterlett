
import { addMessages as translationAdd } from 'svelte-i18n';
import { init as translationInit } from 'svelte-i18n';
import { getLocaleFromNavigator } from 'svelte-i18n';

// Navigation/Menu
import NavigationMenuEnUs from '@_translations/en-US/Navigation/Menu.json';
import NavigationMenuRuRu from '@_translations/ru-RU/Navigation/Menu.json';

translationAdd('en-US', NavigationMenuEnUs);
translationAdd('ru-RU', NavigationMenuRuRu);

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
