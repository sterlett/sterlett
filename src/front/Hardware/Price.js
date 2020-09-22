
// represents price entity for the different hardware types
// defined using ES6-style class notation

import Dinero from 'dinero.js';

/**
 * Price entity for the hardware items
 */
class Price {
    // no hoisting for class declarations
    // always strict mode

    /**
     * Encapsulates floating-point arithmetic implementation for the price entity
     *
     * @type {Dinero}
     */
    #priceModel;

    /**
     * Price constructor.
     *
     * @param {Number} value Amount of money as number, without . or ,
     * @param {String} currency Currency symbol
     * @param {Number} precision Precision number
     */
    constructor(value, currency, precision) {
        this.#priceModel = Dinero(
            {
                amount: value ?? Dinero.defaultAmount,
                currency: currency ?? Dinero.defaultCurrency,
                precision: precision ?? Dinero.defaultPrecision,
            },
        );
    }

    /**
     * Returns price instance based on the specified data object
     *
     * @param {Object} json Json data object
     *
     * @return {Price}
     */
    static fromJson(json) {
        return new this(json?.value, json?.currency, json?.precision);
    }

    /**
     * Returns price amount, formatted as a string
     *
     * @return {String}
     */
    toString() {
        return this.#priceModel.toFormat('0,0 dollar');
    }
}

// alias for ES5 prototype chain resolving calls.
Price.prototype.fromJson = function (json) {
    return this.constructor.fromJson(json);
};

export default Price;
