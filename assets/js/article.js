import {$} from "./functions/dom";
import Checklist from '@editorjs/checklist';
import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';

document.addEventListener('DOMContentLoaded', async () => {
    const article = new Article();

    if ($('.article-data').dataset['isAuthor']) {
        await article.initializeEditor();
    } else {
        article.initializeContent();
    }
})

export class Article {
    id;

    /** @var {EditorJS} */
    editor;

    /** @var {boolean} */
    isPatching = false;

    constructor() {
        this.id = $('.article-data').dataset.id;
    }

    initializeContent() {
        const data = this.getData();
        const articleContent = $('#article-content');

        console.log(data.blocks);

        data.blocks.forEach(block => {
            switch (block.type) {
                case 'checklist':
                    articleContent.append(this.handleChecklistBlock(block.data));
                    break;
                case 'header':
                    articleContent.append(this.handleHeaderBlock(block.data))
                    break;
                case 'list':
                    articleContent.append(this.handleListBlock(block.data));
                case 'paragraph':
                    articleContent.append(this.handleParagraphBlock(block.data));
                    break;
            }
        });
    }

    async initializeEditor() {
        this.editor = await new EditorJS({
            data: this.getData(),
            holder: 'article-content',
            onChange: async () => {
                if (!this.isPatching) {
                    await this.patchArticle();
                }
            },
            tools: {
                checklist: Checklist,
                header: Header,
                list: List
            }
        });

        await this.editor.isReady;

        $('.codex-editor__redactor').style.removeProperty('padding-bottom');
    }

    /**
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

    getData() {
        return JSON.parse($('.article-data').dataset['content']);
    }

    handleChecklistBlock(data) {
        const ul = document.createElement('ul');
        ul.classList.add('list-group', 'list-group-flush')

        data.items.forEach(item => {
            const li = document.createElement('li');
            li.classList.add('list-group-item');

            const div = document.createElement('div');
            div.classList.add('form-check');

            const input = document.createElement('input');
            input.classList.add('form-check-input');
            input.checked = item.checked;
            input.type = 'checkbox';

            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.innerText = item.text;

            div.append(input);
            div.append(label);
            li.appendChild(div);

            ul.append(li);
        })

        return ul;
    }

    handleHeaderBlock(data) {
        const header = document.createElement(`h${data.level}`);
        header.innerText = data.text;

        return header;
    }

    handleListBlock(data) {
        const tagName = 'ordered' === data.type ? 'ol' : 'ul';
        const list = document.createElement(tagName);

        data.items.forEach(item => {
            const li = document.createElement('li');
            li.innerText = item;

            list.append(li);
        });

        return list;
    }

    handleParagraphBlock(data) {
        const paragraph = document.createElement('p');
        paragraph.innerHTML = data.text;

        return paragraph;
    }

    async patchArticle() {
        this.isPatching = true;

        await fetch(`/api/articles/${this.id}`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/merge-patch+json'
            },
            body: JSON.stringify({ content: await this.editor.save() })
        });

        this.isPatching = false;
    }
}