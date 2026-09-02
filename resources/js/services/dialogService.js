import axios from '../plugins/axios';

export const dialogService = {
    /**
     * Crea un nuovo dialog e importa i dati dall'EAF.
     * @param {FormData} formData
     * @returns {Promise}
     */
    async create(formData) {
        return await axios.post('/dialogs', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    },

    /**
     * Recupera la lista di tutti i dialoghi.
     * @param {Object} params - Parametri di filtro (es. { corpus_id: 1 })
     * @returns {Promise}
     */
    async getAll(params = {}) {
        return await axios.get('/dialogs', { params });
    },

    /**
     * Recupera un singolo dialogo per ID.
     * @param {number} id
     * @returns {Promise}
     */
    async get(id) {
        return await axios.get(`/dialogs/${id}`);
    },

    /**
     * Elimina un dialogo.
     * @param {number} id
     * @returns {Promise}
     */
    async delete(id) {
        return await axios.delete(`/dialogs/${id}`);
    },

    /**
     * Aggiorna un dialogo esistente.
     * @param {number} id
     * @param {Object} dialogData
     * @returns {Promise}
     */
    async update(id, dialogData) {
        return await axios.put(`/dialogs/${id}`, dialogData);
    },

    /**
     * Recupera la lista dei corpora (necessario per la select nella form).
     * @returns {Promise}
     */
    async getCorpora() {
        return await axios.get('/corpora');
    }
};
