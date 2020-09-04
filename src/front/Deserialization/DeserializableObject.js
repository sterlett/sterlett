
/**
 * Defines methods for deserialization from the different sources
 *
 * @constructor
 */
function DeserializableObject() {}

/**
 * Returns new domain-related object instance, created from the specified json object
 *
 * @param {Object} json Json data object
 *
 * @return {DeserializableObject}
 */
DeserializableObject.prototype.fromJson = function (json) {
    const instance = Reflect.construct(this.constructor, []);
    const fields   = Object.entries(json);

    for (const [fieldName, fieldValue] of fields) {
        if (!instance.hasOwnProperty(fieldName)) {
            continue;
        }

        instance[fieldName] = fieldValue;
    }

    return instance;
};

export default DeserializableObject;
