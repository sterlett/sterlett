
import DeserializableObject from '@Deserialization/DeserializableObject.js';

/**
 * Holds data about hardware item efficiency
 */
class Benchmark {
    /**
     * Name of the benchmark source
     *
     * @type {String}
     */
    name;

    /**
     * Benchmark value
     *
     * @type {Number}
     */
    value;

    /**
     * Benchmark constructor.
     *
     * @param {String} name Benchmark provider name
     * @param {Number} value Benchmark result value
     */
    constructor(name, value) {
        this.name = name;
        this.value = value;
    }
}

Object.setPrototypeOf(Benchmark.prototype, DeserializableObject.prototype);

export default Benchmark;
