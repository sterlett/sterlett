
// will be raised when the target page becomes successfully deployed (onMount)

/**
 * A page, which has been shown by the SPA engine
 *
 * @typedef {Object} PageElement
 *
 * @property {DOMStringMap} dataset Contains page attributes (e.g. title, to enhance the global one)
 */

/**
 * Event name to dispatch
 *
 * @const {String}
 */
const name = 'app.event.page.shown';

/**
 * PageShownEvent constructor
 *
 * @param {PageElement} pageElement A page object (HTML element with data- attributes)
 *
 * @constructor
 */
function PageShownEvent (pageElement) {
    Object.defineProperty(
        this,
        'pageElement',
        {
            writable: false,
            value: pageElement,
        },
    );

    Object.seal(this);
}

Object.freeze(PageShownEvent.prototype);

export { name };
export default PageShownEvent;
