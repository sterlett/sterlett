
// represents entity from the "CPU" hardware category
// defined using ES5-style class notation, with some ES6 features (i.e. arrow functions)

import DeserializableObject from '@Deserialization/DeserializableObject.js';
import Price from '@Hardware/Price.js';

/**
 * A map of prices, keyed by price types (per-item, average, etc.)
 *
 * @typedef {Object} Prices
 *
 * @property {Price} average Average price for the item
 */

/**
 * Cpu constructor
 *
 * @param {String} name CPU model name
 * @param {String} image CPU image source link
 * @param {Prices} prices CPU price entries
 *
 * @constructor
 */
function Cpu(name, image, prices) {
    // defining property on target instance, not prototype
    Object.defineProperty(
        this,
        'name',
        // only data descriptor or access descriptor should be configured at the same time
        {
            // stands for private access modifier, default: false (private property)
            enumerable: false,
            // can be redefined or removed, default: false (final property)
            configurable: false,
            // [data] value can be reassigned, default: false (read-only property)
            writable: true,
            // [data] property value, default: undefined
            value: name,
            // [access] getter, default: undefined
            // get: () => name,
            // [access] setter, default: undefined
            // set: (nameNew) => name = nameNew,
        },
    );

    Object.defineProperty(
        this,
        'image',
        {
            writable: true,
            value: image,
        },
    );

    let _prices;

    if ('object' === typeof prices) {
        _prices = prices;
    } else {
        _prices = {
            average: new Price(),
        };
    }

    Object.defineProperty(
        this,
        'prices',
        {
            get: () => _prices,
            set: function (pricesNew) {
                for (const price of pricesNew) {
                    let priceType;

                    'undefined' !== typeof price.type ? (
                        priceType = price.type
                    ) : (
                        priceType = 'unit'
                    );

                    if (!Object.prototype.hasOwnProperty.call(_prices, priceType)) {
                        throw new TypeError('Unsupported price type: ' + priceType);
                    }

                    let fieldValueNormalized;

                    if (!(price instanceof Price)) {
                        fieldValueNormalized = Price.prototype.fromJson(price);
                    } else {
                        fieldValueNormalized = price;
                    }

                    _prices[priceType] = fieldValueNormalized;
                }
            },
        },
    );

    // restricting addition of new properties by the user-side
    Object.seal(this);
}

Cpu.prototype = Object.create(DeserializableObject.prototype);

Cpu.prototype.constructor = Cpu;

// marking class as "final", i.e. existing properties are not extensible by the user-side after this line,
// forcing to use composition instead of inheritance.
Object.freeze(Cpu.prototype);

export default Cpu;
