
// a shortcut to trigger "page shown" event (is shared across all page components)

import { createEventDispatcher } from 'svelte';
import { default as PageShownEvent, name as pageShownEventName } from '@Event/PageShownEvent.js';

/**
 * Will trigger a "page shown" event
 *
 * @param {PageElement} pageElement An html element, responsible for the page contents
 *
 * @return {void}
 */
async function dispatchPageShownEvent(pageElement) {
    const dispatch = createEventDispatcher();
    const eventDetail = new PageShownEvent(pageElement);

    dispatch(pageShownEventName, eventDetail);
}

export { dispatchPageShownEvent };
