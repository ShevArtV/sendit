export default class QuizForm {

    constructor(config) {
        const defaults = {
            rootSelector: '[data-si-form]',
            rootKey: 'siForm',
            itemSelector: '[data-qf-item]',
            itemCompleteSelector: '[data-qf-complete="1"]',
            finishSelector: '[data-qf-finish]',
            itemCurrentSelector: '[data-qf-item="${currentIndex}"]',
            btnSelector: '[data-qf-btn]',
            btnNextSelector: '[data-qf-btn="next"]',
            btnPrevSelector: '[data-qf-btn="prev"]',
            btnSendSelector: '[data-qf-btn="send"]',
            btnResetSelector: '[data-qf-btn="reset"]',
            nextIndexSelector: '[data-qf-next]',
            progressSelector: '[data-qf-progress]',
            currentQuestionSelector: '[data-qf-page]',
            totalQuestionSelector: '[data-qf-total]',
            pagesSelector: '[data-qf-pages]',
            progressValueSelector: '[data-qf-progress-value]',
            activeClass: 'active',
            visabilityClass: 'v_hidden',
            disabledClass: 'disabled',
            sendEvent: 'si:complete'
        }
        this.config = Object.assign(defaults, config);

        this.initialize();
    }

    initialize() {
        document.addEventListener('si:init', (e) => {
            const roots = document.querySelectorAll(this.config.rootSelector);
            if (roots.length) {
                roots.forEach(root => {
                    const currentIndex = this.getCookie(root.dataset[this.config.rootKey] + 'Current');
                    const prev = this.getCookie(root.dataset[this.config.rootKey] + 'Prev')?.split(',');

                    this.reset(root);

                    if (prev && prev.length) {
                        prev.forEach(index => {
                            const item = root.querySelector(this.config.itemCurrentSelector.replace('${currentIndex}', index));
                            item.dataset.qfComplete = '1';
                        });
                    }


                    if (currentIndex) {
                        if(typeof SendIt.SaveFormData !== 'undefined'){
                            SendIt.SaveFormData.setValues(root);
                        }
                        this.change(root, currentIndex, 1);
                    }
                });

                document.addEventListener('click', (e) => {
                    const btn = e.target.closest(this.config.btnSelector);
                    const root = btn?.closest(this.config.rootSelector);
                    const dir = btn?.dataset.qfBtn;
                    if (!root) return
                    const {items, current} = this.getElements(root);

                    switch (dir) {
                        case 'send':
                            const event = new CustomEvent(this.config.sendEvent, {
                                detail: {form: root}
                            });
                            document.dispatchEvent(event);
                            break;
                        case 'reset':
                            this.reset(root);
                            break;
                        case 'next':
                            if (root) {
                                const nextIndex = this.getNextIndex(current, items);
                                root ? this.change(root, nextIndex, 1) : '';
                            }
                            break;
                        case 'prev':
                            const prevRaw = this.getCookie(root.dataset[this.config.rootKey] + 'Prev');
                            if (prevRaw) {
                                const prev = prevRaw.split(',').reverse();
                                const nextIndex = Number(prev[0]);
                                prev.shift();
                                if (prev.length) {
                                    this.setCookie(root.dataset[this.config.rootKey] + 'Prev', prev.reverse().join(','));
                                } else {
                                    this.removeCookie(root.dataset[this.config.rootKey] + 'Prev');
                                }

                                root ? this.change(root, nextIndex, 1) : '';
                            }
                            break;
                    }
                });
                document.addEventListener('change', (e) => {
                    const current = e.target.closest(this.config.itemSelector);
                    const root = current?.closest(this.config.rootSelector);

                    if (current && root) {
                        const {items} = this.getElements(root);
                        const nextIndex = this.getNextIndex(current, items);
                        this.change(root, nextIndex, current.dataset.qfAuto);
                    }
                });
                document.addEventListener(this.config.sendEvent, (e) => {
                    console.log(e.detail);
                    const root = e.detail.form.closest(this.config.rootSelector);
                    root ? this.sendHandler(root) : '';
                    if(typeof SendIt.SaveFormData !== 'undefined'){
                        SendIt.SaveFormData.removeValues(root);
                    }
                });
            }
        })
    }

    reset(root) {
        const {items, btns, progress, currentQuestion, totalQuestions, finishItem, pages} = this.getElements(root);
        this.resetItems(root, items, finishItem);
        this.resetBtns(btns);
        this.resetProgress(progress);
        this.resetPagination(pages, currentQuestion, totalQuestions, items.length);
    }

    resetPagination(pages, currentQuestion, totalQuestions, total) {
        currentQuestion.textContent = 1;
        totalQuestions.textContent = total;
        pages.classList.remove(this.config.visabilityClass);
    }

    resetItems(root, items, finishItem) {
        finishItem.classList.add(this.config.visabilityClass);
        items.map((item, i, items) => {
            item.dataset.qfComplete = '0';
            if (i === 0) {
                this.setCookie(root.dataset[this.config.rootKey] + 'Current', item.dataset.qfItem);
                item.classList.remove(this.config.visabilityClass);
            } else {
                item.classList.add(this.config.visabilityClass);
            }
        });
    }

    resetBtns(btns) {
        btns.map((btn) => {
            switch (btn.dataset.qfBtn) {
                case 'prev':
                    btn.disabled = true;
                    btn.classList.add(this.config.disabledClass);
                    btn.classList.remove(this.config.visabilityClass);
                    break;
                case 'next':
                    btn.classList.remove(this.config.visabilityClass);
                    btn.dataset.qfNext = '2';
                    break;
                case 'send':
                case 'reset':
                    btn.classList.add(this.config.visabilityClass)
                    break;
            }
        });
    }

    resetProgress(progress, hide = false) {
        progress.classList.remove(this.config.activeClass);
        if (hide) {
            progress.classList.add(this.config.visabilityClass);
        } else {
            progress.classList.remove(this.config.visabilityClass);
        }
    }

    change(root, nextIndex, isAuto = false) {
        const {items, current, currentQuestion, totalQuestions} = this.getElements(root);
        const prevIndex = current.dataset.qfItem;
        const nextItem = root.querySelector(`[data-qf-item="${nextIndex}"]`);
        const dir = (nextItem && items.indexOf(nextItem) > items.indexOf(current)) ? 'next' : 'prev';

        if (isAuto) {

            this.changeItem(root, current, nextItem);

            this.changeBtnsState(root, prevIndex, nextIndex, dir);

            this.setPagination(currentQuestion, totalQuestions, items, nextIndex);
        }

        this.prepareProgress(root);
    }

    changeItem(root, current, next) {
        if (next) {
            current.classList.add(this.config.visabilityClass);
            this.setCookie(root.dataset[this.config.rootKey] + 'Current', next.dataset.qfItem);
            next.classList.remove(this.config.visabilityClass);
        }
    }

    changeBtnsState(root, prevIndex, nextIndex, dir) {
        const {items, btnSend, btnPrev, btnNext,} = this.getElements(root);
        const prev = this.getCookie(root.dataset[this.config.rootKey] + 'Prev')?.split(',') || [];
        const lastIndex = items[items.length - 1].dataset.qfItem;

        switch (dir) {
            case 'next':
                btnPrev.disabled = false;
                if (!prev.includes(prevIndex) && Number(prevIndex) !== Number(lastIndex)) {
                    prev.push(prevIndex);
                }
                this.setCookie(root.dataset[this.config.rootKey] + 'Prev', prev.join(','));
                break;
            case 'prev':
                if (!prev.length) {
                    btnPrev.disabled = true;
                }
                break;
        }
        if (Number(nextIndex) === Number(lastIndex)) {
            btnSend.classList.remove(this.config.visabilityClass);
            btnNext.classList.add(this.config.visabilityClass);
        } else {
            btnSend.classList.add(this.config.visabilityClass);
            btnNext.classList.remove(this.config.visabilityClass);
        }
    }

    setPagination(currentQuestion, totalQuestions, items, index) {
        const total = items.length;
        const nextItem = items.filter(el => Number(el.dataset.qfItem) === Number(index));
        if (!nextItem[0]) {
            index = total;
        } else {
            index = items.indexOf(nextItem[0]) + 1;
        }
        totalQuestions.textContent = total;
        currentQuestion.textContent = Number(index) < total ? index : total;
    }

    prepareProgress(root) {
        const {items, current, progress, progressValue, btnPrev} = this.getElements(root);
        const inputs = current.querySelectorAll('input, select, textarea');
        const inputNames = [];
        let countInputs = 0;
        let countValues = 0;

        inputs.forEach(el => {
            if (el.type !== 'hidden') {
                if (!inputNames.includes(el.name)) {
                    countInputs++;
                    inputNames.push(el.name);
                }
                if (['checkbox', 'radio'].includes(el.type) && el.checked) {
                    countValues++;
                }

                if (!(['checkbox', 'radio'].includes(el.type)) && el.value) {
                    countValues++;
                }
            }
        });
       
        if (countValues >= countInputs) {
            current.dataset.qfComplete = '1';
        } else {
            current.dataset.qfComplete = '0';
        }

        this.setProgress(progress, progressValue, root, current, btnPrev, items);
    }

    setProgress(progress, progressValue, root, current, btnPrev, items) {
        const itemsComplete = Array.from(root.querySelectorAll(this.config.itemCompleteSelector));
        const currentIndex = items.indexOf(current);
        const prevRaw = this.getCookie(root.dataset[this.config.rootKey] + 'Prev') || '';
        const prev = prevRaw.split(',').reverse();

        let total = items.length
        let complete = itemsComplete.length;

        if ((total - 1) === Number(currentIndex) && items[currentIndex].dataset.qfComplete === '1') {
            total = prev.length;
        }

        if (complete > total) complete = total;

        let percent = Math.round(Number(complete) * 100 / Number(total));

        if (percent) {
            progressValue.style.width = progressValue.textContent = `${percent}%`;
            progress.classList.add(this.config.activeClass);
        }
    }

    getNextIndex(item, items) {
        const radio = item.querySelector('[type="radio"]:checked');
        const nextItemIndex = items.indexOf(item) + 1;
        const nextItem = items[nextItemIndex];

        if (!nextItem) return items[items.length - 1].dataset.qfItem;

        let nextIndex = nextItem.dataset.qfItem;
        if (radio && radio.dataset.qfNext) {
            nextIndex = radio.dataset.qfNext;
        } else if (item.dataset.qfNext) {
            nextIndex = item.dataset.qfNext;
        }
        return nextIndex;
    }

    sendHandler(root) {
        const {items, btns, progress, pages, finishItem} = this.getElements(root);
        items.map(item => item.classList.add(this.config.visabilityClass));
        btns.map(btn => {
            if (btn.dataset.qfBtn !== 'reset') {
                btn.classList.add(this.config.visabilityClass);
            } else {
                btn.classList.remove(this.config.visabilityClass);
            }
        });
        finishItem.classList.remove(this.config.visabilityClass);
        pages.classList.add(this.config.visabilityClass);
        localStorage.removeItem(root.dataset[this.config.rootKey]);
        this.removeCookie(root.dataset[this.config.rootKey] + 'Current');
        this.removeCookie(root.dataset[this.config.rootKey] + 'Prev');
        this.resetProgress(progress, 1);
    }

    setCookie(name, value, options = {}) {
        options = {
            path: '/',
            ...options
        };

        if (options.expires instanceof Date) {
            options.expires = options.expires.toUTCString();
        }

        let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

        for (let optionKey in options) {
            updatedCookie += "; " + optionKey;
            let optionValue = options[optionKey];
            if (optionValue !== true) {
                updatedCookie += "=" + optionValue;
            }
        }

        document.cookie = updatedCookie;
    }

    getCookie(name) {
        let matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    removeCookie(name) {
        const date = new Date();
        date.setDate(date.getDate() - 1);
        this.setCookie(name, '', {expires: date});
    }

    getElements(root) {
        const currentIndex = this.getCookie(root.dataset[this.config.rootKey] + 'Current');

        return {
            items: Array.from(root.querySelectorAll(this.config.itemSelector)),
            btns: Array.from(root.querySelectorAll(this.config.btnSelector)),
            itemsComplete: Array.from(root.querySelectorAll(this.config.itemCompleteSelector)),
            current: root.querySelector(this.config.itemCurrentSelector.replace('${currentIndex}', currentIndex)),
            btnSend: root.querySelector(this.config.btnSendSelector),
            btnPrev: root.querySelector(this.config.btnPrevSelector),
            btnNext: root.querySelector(this.config.btnNextSelector),
            btnReset: root.querySelector(this.config.btnResetSelector),
            progress: root.querySelector(this.config.progressSelector),
            progressValue: root.querySelector(this.config.progressValueSelector),
            currentQuestion: root.querySelector(this.config.currentQuestionSelector),
            totalQuestions: root.querySelector(this.config.totalQuestionSelector),
            finishItem: root.querySelector(this.config.finishSelector),
            pages: root.querySelector(this.config.pagesSelector),
        }
    }
}