
// represents entity from the "CPU" hardware category
// defined using ES5-style class notation, with some ES6 features (i.e. arrow functions)

/**
 * Cpu constructor
 *
 * @param {string} name CPU model name
 * @param {string} image CPU image source link
 *
 * @constructor
 */
export default function Cpu(name, image) {
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

    // restricting addition of new properties by the user-side
    Object.seal(this);
}

/**
 * Returns new CPU instance created from the specified json object
 *
 * @param {Object} json Json data object
 *
 * @return {Cpu}
 */
Cpu.prototype.fromJson = function (json) {
    const instance = new Cpu();

    const fields = Object.entries(json);

    for (const [fieldName, fieldValue] of fields) {
        if (!instance.hasOwnProperty(fieldName)) {
            continue;
        }

        instance[fieldName] = fieldValue;
    }

    return instance;
};

// marking class as "final", i.e. existing properties are not extensible by the user-side after this line,
// forcing to use composition instead of inheritance.
Object.freeze(Cpu.prototype);
