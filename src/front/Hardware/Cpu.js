
// represents entity from the "CPU" hardware category
// defined using ES5-style class notation, with some ES6 features (i.e. arrow functions)

import DeserializableObject from '@Deserialization/DeserializableObject.js';
import Price from '@Hardware/Price.js';
import Benchmark from '@Hardware/Benchmark.js';

/**
 * A map of prices, keyed by price types (per-item, average, etc.)
 *
 * @typedef {Object} Prices
 *
 * @property {Price} average Average price for the item
 */

/**
 * List of benchmarks for the hardware model
 *
 * @typedef {Benchmark[]} Benchmarks
 */

/**
 * Cpu constructor
 *
 * @param {String} name CPU model name
 * @param {String} image CPU image source link
 * @param {Prices} prices CPU price entries
 * @param {Benchmarks} benchmarks List of CPU model benchmarks
 * @param {Number} vbRatio Value to benchmark rating
 *
 * @constructor
 */
function Cpu(name, image, prices, benchmarks, vbRatio) {
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
                for (const priceNew of pricesNew) {
                    let priceType;

                    'undefined' !== typeof priceNew.type ? (
                        priceType = priceNew.type
                    ) : (
                        priceType = 'unit'
                    );

                    if (!Object.prototype.hasOwnProperty.call(_prices, priceType)) {
                        throw new TypeError('Unsupported price type: ' + priceType);
                    }

                    let priceNewNormalized;

                    if (!(
                        priceNew instanceof Price
                    )) {
                        priceNewNormalized = Price.prototype.fromJson(priceNew);
                    } else {
                        priceNewNormalized = priceNew;
                    }

                    _prices[priceType] = priceNewNormalized;
                }
            },
        },
    );

    let _benchmarks = benchmarks ?? [];

    Object.defineProperty(
        this,
        'benchmarks',
        {
            get: () => _benchmarks,
            set: function (benchmarksNew) {
                for (const benchmarkNew of benchmarksNew) {
                    const benchmarkNewNormalized = Benchmark.prototype.fromJson(benchmarkNew);

                    _benchmarks = [..._benchmarks, benchmarkNewNormalized];
                }
            },
        },
    );

    Object.defineProperty(
        this,
        'vbRatio',
        {
            writable: true,
            value: vbRatio,
        },
    );

    // restricting addition of new properties by the user-side
    Object.seal(this);
}

Cpu.prototype = Object.create(DeserializableObject.prototype);

Cpu.prototype.constructor = Cpu;

/**
 * @inheritdoc
 */
Cpu.prototype.fromJson = function (json) {
    const parentReference = Object.getPrototypeOf(this);

    let instance = parentReference.fromJson.call(this, json);
    instance.vbRatio = json?.vb_ratio ?? 0;

    return instance;
};

// marking class as "final", i.e. existing properties are not extensible by the user-side after this line,
// forcing to use composition instead of inheritance.
Object.freeze(Cpu.prototype);

export default Cpu;

// todo: adopt object mapper
