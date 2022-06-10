import {$} from "./functions/dom";

export class Article {
    id;

    constructor() {
        this.id = $('.article-data').dataset.id;
    }

    /**
     *
     * @returns {Promise<Array>}
     */
    async fetchComments() {
        const response = await fetch(`/ajax/articles/${this.id}/comments`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            method: 'GET'
        });

        return await response.json();
    }
}