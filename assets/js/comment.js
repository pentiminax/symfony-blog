import {$, $$} from "./functions/dom";
import {Article} from "./article";
import {getCommentElement, getReplyDialogElement} from "./comment_html_helper";
import {jsonFetch} from "./functions/api";

document.addEventListener('DOMContentLoaded', async () => {
    const article = new Article();
    const comments = await article.fetchComments();
    const comment = new Comment();
    comment.comments = comments;
    comment.handleCommentsList(comments);
    comment.listenCommentAnswerButton();
});

class Comment {
    /**
     * @type {Array}
     */
    comments;

    /**
     * @type {HTMLElement}
     */
    commentList;

    /**
     * @type {HTMLElement}
     */
    commentCount;

    /**
     * @type {HTMLFormElement}
     */
    commentForm;

    /**
     * @type {HTMLElement}
     */
    currentReplyDialog;

    /**
     * @type {Number}
     */
    userId;

    selectedCommentText;

    constructor() {
        this.commentList = $('.comment-list');
        this.commentCount = $('#comment-count');
        this.commentForm = $('form.comment-form');
        this.userId = Number($('.article-data').dataset.userId);

        this.commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.addComment(e.target);
        });
    }

    /**
     *
     * @param {HTMLFormElement} target
     * @returns {Promise<void>}
     */
    async addAnswer(target) {
        const content = this.getAnswerContentFromActionArea(target);

        if (!content.length) {
            return;
        }

        const data = await jsonFetch('/ajax/comments/answer', {
            body: new FormData(target),
            method: 'POST'
        })

        if (data.code !== 'ANSWER_ADDED_SUCCESSFULLY') {
            return;
        }

        const answer = data.detail.answer;
        const commentAnswers = target.nextElementSibling;

        commentAnswers.insertAdjacentHTML('beforeend', data.message);

        this.addCardFooterEventListeners(answer.id);
        this.comments.push(answer);

        this.currentReplyDialog.remove();
    }

    /**
     *
     * @param {HTMLFormElement} target
     * @returns {Promise<void>}
     */
    async addComment(target) {
        const data = await jsonFetch('/ajax/comments', {
            body: new FormData(target),
            method: 'POST'
        });

        if (data.code !== 'COMMENT_ADDED_SUCCESSFULLY') {
            return;
        }

        this.commentList.insertAdjacentHTML('beforeend', data.message);

        const comment = data.detail.comment;
        const commentElt = this.commentList.lastElementChild;
        commentElt.scrollIntoView();

        this.addCardFooterEventListeners(comment.id);
        this.comments.push(comment);
        this.commentCount.innerText = data.detail.numberOfComments;

        $('#comment_content').value = '';
    }

    /**
     *
     * @param {HTMLButtonElement} target
     * @returns {Promise<void>}
     */
    async editComment(target) {
        const commentId = target.dataset.id;
        const content = this.getCardTextElement(commentId).querySelector('#comment_content').value;

        const data = await jsonFetch(`/ajax/comments/${commentId}`, {
            body: JSON.stringify({content: content}),
            method: 'PATCH'
        });

        if (data.code !== 'COMMENT_SUCCESSFULLY_EDITED') {
            return;
        }

        const modifiedComment = data.detail.comment;

        const commentIndex = this.comments.findIndex(comment => comment.id === modifiedComment.id);

        if (commentIndex !== -1) {
            this.comments[commentIndex] = modifiedComment;
        }
    }

    /**
     *
     * @param {Array} comments
     */
    handleCommentsList(comments) {
        const commentList = document.createElement('div');
        commentList.classList.add('comment-list');

        comments.forEach(comment => {
            if (null === comment.parentId) {
                const item = document.createElement('div');
                item.classList.add('comment');
                item.id = `c${comment.id}`;
                item.innerHTML = getCommentElement(comment, this.userId);
                commentList.append(item);

                const replyList = document.createElement('div');
                replyList.classList.add('comment-answers');
                item.append(replyList);

                this.handleCommentReplies(comment.id, comments, replyList);
            }
        });

        this.commentList.replaceWith(commentList);
        this.commentList = $('.comment-list');
    }

    handleCommentReplies(commentId, comments, list) {
        for (let i = 0; (i < comments.length); i++) {
            if (commentId === comments[i].parentId) {
                const item = document.createElement('div');
                item.classList.add('comment');
                item.id = `c${comments[i].id}`;
                item.innerHTML = getCommentElement(comments[i], this.userId);

                list.append(item);

                const replyList = document.createElement('div');
                replyList.classList.add('comment-answers');
                item.append(replyList);

                this.handleCommentReplies(comments[i].id, comments, replyList);
            }
        }
    }

    /**
     *
     * @param {HTMLButtonElement} target
     * @returns {Promise<void>}
     */
    async deleteComment(target) {
        const data = await jsonFetch(`/ajax/comments/${target.dataset.id}`, {
            method: 'DELETE'
        });

        if (data.code !== 'COMMENT_SUCCESSFULLY_DELETED') {
            return;
        }

        const commentElement = target.closest('.comment');
        commentElement.remove();

        this.commentCount.innerText = data.detail.numberOfComments;
    }

    async toggleEditArea(target) {
        const commentId = Number(target.dataset.id);
        const cardText = this.getCardTextElement(commentId);

        switch (target.dataset.action) {
            case 'showEditArea':
                const data = await jsonFetch(`/ajax/comments/${commentId}`, {
                    method: 'GET'
                });

                const cardFooter = this.getCardFooterElement(commentId);

                cardText.innerHTML = data.cardText
                cardFooter.innerHTML = data.cardFooter;

                cardFooter.querySelector('#save-edit-comment-button').addEventListener('click', async (e) => {
                    await this.editComment(e.target);
                    await this.toggleEditArea(e.target);
                });

                cardFooter.querySelector('#cancel-edit-comment-button').addEventListener('click', async (e) => {
                    await this.toggleEditArea(e.target);
                });

                this.selectedCommentText = cardText.querySelector('#comment_content').value;
                break;
            case 'hideEditArea':
                $(`#c${commentId} > .card`).innerHTML = getCommentElement(this.comments.find(c => c.id === commentId), this.userId, true);
                this.addCardFooterEventListeners(commentId);
                break;
            default:
                break;
        }
    }

    listenCommentAnswerButton() {
        const answerCommentButtons = $$('#show-reply-dialog-button');
        const deleteButtons = $$('#delete-comment-button');
        const editButtons = $$('#edit-comment-button');

        answerCommentButtons.forEach(btn => {
            btn.addEventListener('click', e => {
                this.showReplyDialog(e.target);
            })
        });

        deleteButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                await this.deleteComment(e.target);
            })
        });

        editButtons.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                await this.toggleEditArea(e.target);
            })
        });
    }

    /**
     *
     * @param {HTMLButtonElement} target
     */
    showReplyDialog(target) {
        const commentId = target.dataset.id;
        const replyDialogSelector = `#reply-dialog-${commentId}`

        if ($(replyDialogSelector)) {
            return;
        }

        if (undefined !== this.currentReplyDialog) {
            this.currentReplyDialog.remove();
        }

        const replyDialogElement = getReplyDialogElement(commentId);
        target.parentElement.parentElement.insertAdjacentHTML('afterend', replyDialogElement);

        const answerButton = $('#answer-button');
        const cancelAnswerCommentButton = $('#hide-reply-dialog-button');

        $('.reply-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.addAnswer(e.target);
        });

        cancelAnswerCommentButton.addEventListener('click', e => {
            this.currentReplyDialog.remove();
        })

        this.currentReplyDialog = $(replyDialogSelector);
    }

    /**
     * @param  {HTMLElement} element
     * @returns {*}
     */
    getReplyDialogFromActionArea(element) {
        return element.parentElement.parentElement.parentElement;
    }

    /**
     * @param {HTMLElement} element
     * @returns {string}
     */
    getAnswerContentFromActionArea(element) {
        return this.getReplyDialogFromActionArea(element).querySelector('#answer-content').value
    }

    getCardTextElement(commentId) {
        return $(`#c${commentId} > .card > .comment-content > .card-text`);
    }

    getCardFooterElement(commentId) {
        return $(`#c${commentId} > .card > .card-footer`);
    }

    addCardFooterEventListeners(commentId) {
        const cardFooter = this.getCardFooterElement(commentId);

        cardFooter.querySelector('#show-reply-dialog-button').addEventListener('click', (e) => {
            this.showReplyDialog(e.target);
        });

        cardFooter.querySelector('#edit-comment-button').addEventListener('click', async (e) => {
            await this.toggleEditArea(e.target);
        });

        cardFooter.querySelector('#delete-comment-button').addEventListener('click', async (e) => {
            await this.deleteComment(e.target);
        });
    }
}