
import Cpu from '@Hardware/Cpu.js';

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
        throw new Error('Unable to fetch CPU list. ' + error);
    }
}

/**
 * Resolves into the data transfer object
 *
 * @param {Response} response The response to a request
 *
 * @returns {Promise<any>}
 */
async function responseDeserialize(response)
{
    try {
        return await response.json();
    } catch (error) {
        throw new Error('Unable to deserialize CPU list. ' + error);
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
        const cpu = Cpu.prototype.fromJson(item);

        cpuList = [...cpuList, cpu];
    }

    return cpuList;
}

export { fetchCpuList };
