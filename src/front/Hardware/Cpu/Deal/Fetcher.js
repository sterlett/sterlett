
/**
 * URI for the CPU deals action
 *
 * @const {String}
 */
const fetchUri = '/api/cpu/deals.json';

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

        throw new Error('Unable to fetch CPU deals. ' + errorMessage);
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

        throw new Error('Unable to deserialize CPU deals. ' + errorMessage);
    }
}

/**
 * Resolves into the list of CPU entities
 *
 * @return {Promise<Object>}
 */
async function fetchCpuDealList() {
    const response = await fetchByUri(fetchUri);
    const responseJson = await responseDeserialize(response);

    if (responseJson.length < 1) {
        throw new Error('Unable to validate CPU deals. Invalid data.');
    }

    return responseJson;
}

export { fetchCpuDealList };
