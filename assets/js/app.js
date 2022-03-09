import '../css/app.scss';
import {Dropdown} from 'bootstrap';

document.addEventListener('DOMContentLoaded', async () => {
    createColorSchemeSelector();
    enableDropdowns();
    handleCommentForm();
});

const createColorSchemeSelector = () => {
    if (null === document.querySelector('.dropdown-appearance')) {
        return;
    }

    const currentScheme = localStorage.getItem('blog/colorScheme') || 'auto';
    const colorSchemeSelectors = document.querySelectorAll('.dropdown-appearance a[data-color-scheme]');
    const activeColorSchemeSelector = document.querySelector(`.dropdown-appearance a[data-color-scheme="${currentScheme}"]`);

    colorSchemeSelectors.forEach((selector) => { selector.classList.remove('active') });
    activeColorSchemeSelector.classList.add('active');

    colorSchemeSelectors.forEach((selector) => {
        selector.addEventListener('click', () => {
            const selectedColorScheme = selector.getAttribute('data-color-scheme');
            const resolvedColorScheme = 'auto' === selectedColorScheme
                ? matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
                : selectedColorScheme;

            document.body.classList.remove('light-scheme', 'dark-scheme');
            document.body.classList.add('light' === resolvedColorScheme ? 'light-scheme' : 'dark-scheme');
            document.body.style.colorScheme = resolvedColorScheme;
            localStorage.setItem('blog/colorScheme', selectedColorScheme);

            colorSchemeSelectors.forEach((otherSelector) => { otherSelector.classList.remove('active') });
            selector.classList.add('active');
        });
    });
}

const enableDropdowns = () => {
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.map(function (dropdownToggleEl) {
        return new Dropdown(dropdownToggleEl);
    });
}

const handleCommentForm = () => {
    if (null === document.querySelector('.comment-area')) {
        return;
    }

    const commentForm = document.querySelector('form.comment-form');

    commentForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const response = await fetch('/ajax/comments', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            method: 'POST',
            body: new FormData(e.target)
        });

        if (!response.ok) {
            return;
        }

        const json = await response.json();

        if (json.code === 'COMMENT_ADDED_SUCCESSFULLY') {
            const commentsList = document.querySelector('.comment-list');
            const commentCount = document.querySelector('#comment-count');
            const commentFormContent = document.querySelector('#comment_form_content');
            commentsList.insertAdjacentHTML('beforeend', json.message);
            commentsList.lastElementChild.scrollIntoView();
            commentCount.innerText = json.numberOfComments;
            commentFormContent.value = '';
        }
    })
}