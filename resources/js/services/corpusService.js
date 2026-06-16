import axios from '../plugins/axios';

export const corpusService = {
    /**
     * Recupera la lista di tutti i corpora.
     * @returns {Promise}
     */
    async getAll() {
        return await axios.get('/corpora');
    },

    /**
     * Recupera un singolo corpus per ID.
     * @param {number} id
     * @returns {Promise}
     */
    async get(id) {
        return await axios.get(`/corpora/${id}`);
    },

    /**
     * Crea un nuovo corpus.
     * @param {Object} corpusData
     * @returns {Promise}
     */
    async create(corpusData) {
        return await axios.post('/corpora', corpusData);
    },

    /**
     * Aggiorna un corpus esistente.
     * @param {number} id
     * @param {Object} corpusData
     * @returns {Promise}
     */
    async update(id, corpusData) {
        return await axios.put(`/corpora/${id}`, corpusData);
    },

    /**
     * Elimina un corpus.
     * @param {number} id
     * @returns {Promise}
     */
    async delete(id) {
        return await axios.delete(`/corpora/${id}`);
    }
};
