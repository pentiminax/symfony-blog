import TimeAgo from 'javascript-time-ago'
import fr from 'javascript-time-ago/locale/fr'

TimeAgo.addDefaultLocale(fr);

export function getCommentElement(comment, userId, fromEdit = false) {
    let html = fromEdit ? '' : '<div class="card">';

    const timeAgo = new TimeAgo('fr-FR');
    const createdAt = timeAgo.format(new Date(comment.createdAt));

    html += `<div class="comment-content">
                <h5 class="card-title" style="margin-bottom: 0 !important;">
                    <a class="text-decoration-none" href="/user/${comment.username}">
                        <span class="comment-author">${comment.username}</span>
                    </a>
                </h5>
                 <small>${createdAt}</small>
                <p class="card-text">${comment.content}</p>
            </div>`

    html += `<div class="card-footer">
                <button type="button" class="btn btn-sm btn-outline-primary" id="show-reply-dialog-button" data-id="${comment.id}">Répondre</button>`;

    if (0 !== userId && comment.userId === userId) {
        html += `<button type="button" class="btn btn-sm btn-outline-danger ms-2 float-end" id="delete-comment-button" data-id="${comment.id}">Supprimer</button>
                <button type="button" class="btn btn-sm btn-outline-info float-end" id="edit-comment-button" data-action="showEditArea" data-id="${comment.id}">Modifier</button>`;
    }

    html += '</div>';

    if (!fromEdit) {
        html += '</div>';
    }

    return html;
}

/**
 *
 * @param {string} commentId
 * @returns {string}
 */
export function getReplyDialogElement(commentId) {
    return ` <form class="reply-form my-2" id="reply-dialog-${commentId}">
                <textarea class="form-control" placeholder="Ajouter une réponse..." id="answer-content" name="comment[content]" required></textarea>
                <input type="hidden" name="comment[id]" value="${commentId}">
                <div class="text-end" id="reply-action">
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" id="hide-reply-dialog-button">Annuler</button>
                    <button type="submit" class="btn btn-sm btn-outline-primary mt-2" id="answer-button">Répondre</button>
                </div>
             </form>`;
}