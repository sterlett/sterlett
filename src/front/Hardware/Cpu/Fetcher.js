
import Cpu from '@Hardware/Cpu';

/**
 * URI of the list with CPU entities
 *
 * @const {String}
 */
const fetchUri = '/api/cpu/list.json';

/**
 * Resolves into a response object
 *
 * @param {String} uri Data URI
 *
 * @return {Promise<Response>}
 */
async function fetchByUri(uri) {
    try {
        return await fetch(uri);
    } catch (error) {
        const errorMessage = error?.message ?? error?.toString() ?? 'Unknown error.';

        // expands the context with additional information.
        // will be passed as a reason to the rejection callback.
        throw new Error('Unable to fetch CPU list. ' + errorMessage);
    }
}

/**
 * Resolves into the data transfer object
 *
 * @param {Response} response The response to a request
 *
 * @return {Promise<any>}
 */
async function responseDeserialize(response)
{
    try {
        return await response.json();
    } catch (error) {
        const errorMessage = error?.message ?? error?.toString() ?? 'Unknown error.';

        throw new Error('Unable to deserialize CPU list. ' + errorMessage);
    }
}

/**
 * Resolves into the list of CPU entities
 *
 * @return {Promise<Cpu[]>}
 */
async function fetchCpuList() {
    const response = await fetchByUri(fetchUri);
    const responseJson = await responseDeserialize(response);

    if ('undefined' === typeof responseJson.items) {
        throw new Error('Unable to validate CPU list. Invalid data path.');
    }

    let cpuList = [];

    for (const item of responseJson.items) {
        const cpuNormalized = Cpu.prototype.fromJson(item);

        cpuList = [...cpuList, cpuNormalized];
    }

    return cpuList;
}

export { fetchCpuList };
