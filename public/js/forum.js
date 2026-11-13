/**
 * Nested Forum - Interactive Behaviors
 * Implements accessible collapse/expand replies (Lab Challenge 9) and inline reply toggling.
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Collapse & Expand Replies
    const collapseButtons = document.querySelectorAll('.collapse-btn');
    
    collapseButtons.forEach(btn => {
        // Ensure buttons are visible now that JavaScript has initialized
        btn.classList.add('js-ready');

        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('aria-controls');
            if (!targetId) return;

            const targetContainer = document.getElementById(targetId);
            if (!targetContainer) return;

            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            const count = btn.getAttribute('data-count') || '';
            const suffix = count ? ` (${count})` : '';

            if (isExpanded) {
                // Collapse the branch
                targetContainer.hidden = true;
                btn.setAttribute('aria-expanded', 'false');
                btn.textContent = `Show replies${suffix}`;
            } else {
                // Expand the branch
                targetContainer.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
                btn.textContent = `Hide replies${suffix}`;
            }
        });
    });

    // 2. Inline Reply Form Toggle
    const replyToggles = document.querySelectorAll('.btn-reply-toggle');
    replyToggles.forEach(toggleBtn => {
        toggleBtn.addEventListener('click', () => {
            const commentId = toggleBtn.getAttribute('data-comment-id');
            const form = document.getElementById(`reply-form-${commentId}`);
            if (!form) return;

            const isClosed = form.style.display === 'none' || form.style.display === '';
            form.style.display = isClosed ? 'block' : 'none';

            if (isClosed) {
                const textarea = form.querySelector('textarea');
                if (textarea) textarea.focus();
            }
        });
    });

    const replyCancels = document.querySelectorAll('.btn-cancel-reply');
    replyCancels.forEach(cancelBtn => {
        cancelBtn.addEventListener('click', () => {
            const commentId = cancelBtn.getAttribute('data-comment-id');
            const form = document.getElementById(`reply-form-${commentId}`);
            if (form) {
                form.style.display = 'none';
            }
        });
    });
});
