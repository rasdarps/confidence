(() => {
    'use strict';
    const root = document.getElementById('business-chat');
    if (!root) return;
    const form = document.getElementById('chat-form');
    const input = document.getElementById('chat-question');
    const messages = document.getElementById('chat-messages');
    const status = document.getElementById('chat-status');
    const newButton = document.getElementById('chat-new');
    const controls = [...root.querySelectorAll('button'), input];
    let busy = false;
    let reset = false;

    function element(tag, text, className) {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    }

    function metricList(metrics) {
        const list = element('dl', undefined, 'chat-metrics');
        Object.entries(metrics).forEach(([label, value]) => {
            const group = element('div');
            const quantity = /^Total quantity \(/.test(label);
            group.append(element('dt', label), element('dd', Number(value).toLocaleString(undefined, {
                minimumFractionDigits: quantity || /count|products/i.test(label) ? 0 : 2,
                maximumFractionDigits: quantity ? 4 : (/count|products/i.test(label) ? 0 : 2)
            })));
            list.append(group);
        });
        return list;
    }

    function reportTable(columns, rows, title) {
        const wrapper = element('div', undefined, 'table-responsive');
        const table = element('table', undefined, 'table table-sm');
        table.setAttribute('aria-label', title);
        const heading = element('tr');
        columns.forEach(column => {
            const cell = element('th', column);
            cell.scope = 'col';
            heading.append(cell);
        });
        const head = element('thead');
        head.append(heading);
        const body = element('tbody');
        rows.forEach(row => {
            const line = element('tr');
            row.forEach(value => line.append(element('td', String(value ?? ''))));
            body.append(line);
        });
        table.append(head, body);
        wrapper.append(table);
        return wrapper;
    }

    function renderAnswer(answer) {
        const article = element('article', undefined, 'chat-message chat-message-answer');
        article.append(element('h5', answer.title), element('div', answer.period, 'chat-period'));
        const filters = Object.entries(answer.filters || {}).filter(([, value]) => value).map(([key, value]) => `${key.replace(/_/g, ' ')}: ${value}`);
        if (filters.length) article.append(element('div', filters.join(' | '), 'chat-filters'));
        article.append(metricList(answer.metrics));
        if (answer.rows.length) {
            article.append(reportTable(answer.columns, answer.rows, answer.title));
        } else if (!Object.keys(answer.metrics).length) {
            article.append(element('p', 'No matching records.'));
        }
        if (answer.comparison) {
            const comparison = element('section', undefined, 'chat-comparison');
            comparison.append(element('h6', `Comparison: ${answer.comparison.period}`), metricList(answer.comparison.metrics));
            if (answer.comparison.rows.length) comparison.append(reportTable(answer.comparison.columns, answer.comparison.rows, 'Comparison: ' + answer.title));
            article.append(comparison);
        }
        const notes = element('ul', undefined, 'chat-notes');
        answer.notes.forEach(note => notes.append(element('li', note)));
        article.append(notes);
        if (answer.source) {
            const link = element('a', answer.source.label, 'chat-source');
            const url = new URL(answer.source.url, window.location.href);
            if (url.origin === window.location.origin) {
                link.href = url.href;
                const icon = element('i', undefined, 'ri-arrow-right-line');
                icon.setAttribute('aria-hidden', 'true');
                link.append(icon);
                article.append(link);
            }
        }
        messages.append(article);
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const question = input.value.trim();
        if (!question || busy) return;
        busy = true;
        document.getElementById('chat-empty')?.remove();
        messages.append(element('div', question, 'chat-message chat-message-user'));
        input.value = '';
        controls.forEach(control => { control.disabled = true; });
        status.textContent = 'Checking records...';
        messages.setAttribute('aria-busy', 'true');
        const abort = new AbortController();
        const timer = setTimeout(() => abort.abort(), 30000);
        try {
            const response = await fetch(root.dataset.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                signal: abort.signal,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ message: question, conversation_id: root.dataset.conversation, reset })
            });
            if (!response.ok) {
                const errors = {
                    401: 'Your session has expired. Reload the page and sign in.',
                    403: 'You do not have permission for this report.',
                    419: 'Your session has expired. Reload the page before continuing.',
                    422: 'Enter a question of up to 500 characters.',
                    429: 'Too many questions. Try again in a minute.'
                };
                throw new Error(errors[response.status] || 'The report could not be loaded. Try again.');
            }
            const payload = await response.json();
            if (payload.status === 'success') {
                renderAnswer(payload.answer);
                reset = false;
            } else {
                messages.append(element('div', payload.message, 'chat-message'));
            }
            status.textContent = '';
        } catch (error) {
            const text = error.name === 'AbortError' ? 'The request timed out. Try a smaller date range.' : error.message;
            messages.append(element('div', text, 'chat-message chat-message-error'));
            status.textContent = 'Question not completed.';
            input.value = question;
        } finally {
            clearTimeout(timer);
            busy = false;
            controls.forEach(control => { control.disabled = false; });
            messages.setAttribute('aria-busy', 'false');
            messages.scrollTop = messages.scrollHeight;
            input.focus();
        }
    });

    root.querySelectorAll('.chat-example').forEach(button => button.addEventListener('click', () => {
        if (busy) return;
        input.value = button.dataset.question;
        form.requestSubmit();
    }));
    newButton.addEventListener('click', () => {
        if (busy) return;
        reset = true;
        messages.replaceChildren();
        status.textContent = '';
        input.value = '';
        input.focus();
    });
})();