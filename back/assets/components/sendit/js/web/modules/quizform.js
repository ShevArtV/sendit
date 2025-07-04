import {Base} from './base.js';

export class QuizForm extends Base {
  initialize() {
    this.events = {
      change: 'si:quiz:change',
      reset: 'si:quiz:reset',
      progress: 'si:quiz:progress',
      init: 'si:quiz:init'
    };

    const roots = Array.from(document.querySelectorAll(this.config.formSelector));
    if (roots.length) {
      for (let i in roots) {
        if (typeof roots[i] !== 'object') continue;
        this.initQuiz(roots[i]);
      }

      document.addEventListener('click', (e) => {
        const btn = e.target.closest(this.config.btnSelector);
        const root = btn?.closest(this.config.formSelector);
        const dir = btn?.dataset[this.config.btnKey];
        let nextIndex = 0;
        let prevRaw = '';
        if (!root) return;
        const {items, current} = this.getElements(root);
        if (items.length < 2) return;
        switch (dir) {
        case 'reset':
          this.reset(root);
          break;
        case 'next':
          if (root) {
            nextIndex = this.getNextIndex(current, items);
            root ? this.change(root, nextIndex, true) : '';
          }
          break;
        case 'prev':
          prevRaw = this.hub.getComponentCookie(root.dataset[this.config.rootKey] + 'Prev');
          if (prevRaw) {
            const prev = prevRaw.split(',').reverse();
            const nextIndex = Number(prev[0]);
            prev.shift();
            if (prev.length) {
              this.hub.setComponentCookie(root.dataset[this.config.rootKey] + 'Prev', prev.reverse().join(','));
            } else {
              this.hub.removeComponentCookie(root.dataset[this.config.rootKey] + 'Prev');
            }
            root ? this.change(root, nextIndex, true) : '';
          }

          break;
        }
      });

      document.addEventListener('change', (e) => {
        if (!e.isTrusted) return;
        const current = e.target.closest(this.config.rootSelector);
        const root = current?.closest(this.config.formSelector);

        if (current && root) {
          const {items} = this.getElements(root);
          if (items.length < 2) return;
          const nextIndex = this.getNextIndex(current, items);
          this.prepareProgress(root, current);
          if (current.dataset[this.config.autoKey]) {
            this.change(root, nextIndex, true);
          }
        }
      });

      document.addEventListener(this.config.sendEvent, (e) => {
        if (e.detail.target === document) return true;
        const root = e.detail.target.closest(this.config.formSelector);
        if (!root) return true;
        const {items} = this.getElements(root);
        if (items.length < 2) return;
        if (e.detail.result.success) {
          root ? this.sendHandler(root) : '';
        } else {
          root ? this.errorHandler(root) : '';
        }

      });
    }
  }

  initQuiz(root) {
    const items = root.querySelectorAll(this.config.rootSelector);
    const currentIndex = root.hasAttribute(this.config.nosaveAttr) ? 1 : this.hub.getComponentCookie(root.dataset[this.config.rootKey] + 'Current');

    if (items.length < 2) return;

    this.hub.dispatchEvent(this.events.init, {
      bubbles: true,
      cancelable: false,
      detail: {
        items: items,
        root: root,
        currentIndex: currentIndex,
        Quiz: this
      }
    });   

    this.reset(root);

    items.forEach(item => this.prepareProgress(root, item));

    if (currentIndex) {
      this.change(root, currentIndex);
    }
  }

  errorHandler(root) {
    const errorField = root?.querySelector('.' + this.hub.Sending?.config?.errorClass);
    const item = errorField?.closest(this.config.rootSelector);
    const prevRaw = this.hub.getComponentCookie(root.dataset[this.config.rootKey] + 'Prev');

    if (item) {
      if (prevRaw) {
        const prev = prevRaw.split(',');
        const newPrev = [];
        for (let i = 0; i < prev.length; i++) {
          if (prev[i] !== item.dataset[this.config.itemKey]) {
            newPrev.push(prev[i]);
          } else {
            break;
          }
        }

        if (newPrev.length) {
          this.hub.setComponentCookie(root.dataset[this.config.rootKey] + 'Prev', newPrev.join(','));
        }
      }

      this.change(root, item.dataset[this.config.itemKey]);
    }
  }

  reset(root) {
    const {items, btns, progress, currentQuestion, totalQuestions, finishItem, pages} = this.getElements(root);
    this.resetItems(root, items, finishItem);
    this.resetBtns(btns);
    this.resetProgress(progress);
    this.resetPagination(pages, currentQuestion, totalQuestions, items.length);

    this.hub.dispatchEvent(this.events.reset, {
      bubbles: true,
      cancelable: false,
      detail: {
        items: items,
        btns: btns,
        progress: progress,
        currentQuestion: currentQuestion,
        totalQuestions: totalQuestions,
        root: root,
        finishItem: finishItem,
        pages: pages,
        Quiz: this
      }
    });   
  }

  resetPagination(pages, currentQuestion, totalQuestions, total) {
    if (!currentQuestion && !totalQuestions && !pages) return;
    currentQuestion.textContent = 1;
    totalQuestions.textContent = total;
    pages.classList.remove(this.config.visabilityClass);
  }

  resetItems(root, items, finishItem) {
    finishItem?.classList.add(this.config.visabilityClass);
    items.map((item, i, items) => {
      item.dataset[this.config.itemCompleteKey] = '0';
      if (i === 0) {
        this.hub.setComponentCookie(root.dataset[this.config.rootKey] + 'Current', item.dataset[this.config.itemKey]);
        item.classList.remove(this.config.visabilityClass);
      } else {
        item.classList.add(this.config.visabilityClass);
      }
    });
  }

  resetBtns(btns) {
    btns.map((btn) => {
      switch (btn.dataset[this.config.btnKey]) {
      case 'prev':
        btn.disabled = true;
        btn.classList.add(this.config.disabledClass);
        btn.classList.remove(this.config.visabilityClass);
        break;
      case 'next':
        btn.classList.remove(this.config.visabilityClass);
        btn.dataset[this.config.nextIndexKey] = '2';
        break;
      case 'send':
      case 'reset':
        btn.classList.add(this.config.visabilityClass);
        break;
      }
    });
  }

  resetProgress(progress, hide = false) {
    progress?.classList.remove(this.config.activeClass);
    if (hide) {
      progress?.classList.add(this.config.visabilityClass);
    } else {
      progress?.classList.remove(this.config.visabilityClass);
    }
  }

  change(root, nextIndex, isTrusted = false) {
    const {items, current, currentQuestion, totalQuestions, btnReset, btnSend, btnNext, btnPrev} = this.getElements(root);
    const prevIndex = current.dataset[this.config.itemKey];
    const nextItem = root.querySelector(`[data-qf-item="${nextIndex}"]`);
    const dir = (nextItem && items.indexOf(nextItem) > items.indexOf(current)) ? 'next' : 'prev';
    if (nextItem === current) return;

    if(!this.hub.dispatchEvent(this.events.change, {
      bubbles: true,
      cancelable: true,
      detail: {
        isTrusted: isTrusted,
        btnReset: btnReset,
        btnSend: btnSend,
        btnNext: btnNext,
        btnPrev: btnPrev,
        root: root,
        nextIndex: nextIndex,
        items: items,
        current: current,
        currentQuestion: currentQuestion,
        totalQuestions: totalQuestions,
        prevIndex: prevIndex,
        nextItem: nextItem,
        dir: dir,
        Quiz: this
      }
    })){
      return;
    }   

    this.changeItem(root, current, nextItem, items);

    this.changeBtnsState(root, prevIndex, nextIndex, dir);

    this.setPagination(currentQuestion, totalQuestions, items, nextIndex);

    if (nextItem === items[items.length - 1]) {
      this.prepareProgress(root, nextItem);
    }
  }

  changeItem(root, current, next) {
    if (next) {
      current.classList.add(this.config.visabilityClass);
      this.hub.setComponentCookie(root.dataset[this.config.rootKey] + 'Current', next.dataset[this.config.itemKey]);
      next.classList.remove(this.config.visabilityClass);
    }
  }

  changeBtnsState(root, prevIndex, nextIndex, dir) {
    const {items, btnSend, btnPrev, btnNext} = this.getElements(root);
    const prev = this.hub.getComponentCookie(root.dataset[this.config.rootKey] + 'Prev')?.split(',') || [];
    const lastIndex = items[items.length - 1].dataset[this.config.itemKey];

    switch (dir) {
    case 'next':
      btnPrev && (btnPrev.disabled = false);
      btnPrev && btnPrev.classList.remove(this.config.disabledClass);
      if (!prev.includes(prevIndex) && Number(prevIndex) !== Number(lastIndex)) {
        prev.push(prevIndex);
      }
      this.hub.setComponentCookie(root.dataset[this.config.rootKey] + 'Prev', prev.join(','));

      break;
    case 'prev':
      if (!prev.length || nextIndex === '1') {
        btnPrev && (btnPrev.disabled = true);
        btnPrev && btnPrev.classList.add(this.config.disabledClass);
      }
      break;
    }
    if (Number(nextIndex) === Number(lastIndex)) {
      btnSend && btnSend.classList.remove(this.config.visabilityClass);
      btnNext && btnNext.classList.add(this.config.visabilityClass);
    } else {
      btnSend && btnSend.classList.add(this.config.visabilityClass);
      btnNext && btnNext.classList.remove(this.config.visabilityClass);
    }
  }

  setPagination(currentQuestion, totalQuestions, items, index) {
    if (!currentQuestion) return;
    const total = items.length;
    const nextItem = items.filter(el => Number(el.dataset[this.config.itemKey]) === Number(index));
    if (!nextItem[0]) {
      index = total;
    } else {
      index = items.indexOf(nextItem[0]) + 1;
    }
    totalQuestions && (totalQuestions.textContent = total);
    currentQuestion && (currentQuestion.textContent = Number(index) < total ? index : total);
  }

  prepareProgress(root, item) {
    const inputs = item.querySelectorAll('input, select, textarea');
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

    if (countValues >= countInputs && countValues > 0) {
      item.dataset[this.config.itemCompleteKey] = '1';
    } else {
      item.dataset[this.config.itemCompleteKey] = '0';
    }

    this.setProgress(root);
  }

  setProgress(root) {
    const {items, progress, progressValue} = this.getElements(root);
    if (!progress) return;
    const itemsComplete = Array.from(root.querySelectorAll(this.config.itemCompleteSelector));
    let total = items.length;
    let complete = itemsComplete.length;

    if (!items[items.length - 1].classList.contains(this.config.visabilityClass)) {
      const prev = this.hub.getComponentCookie(root.dataset[this.config.rootKey] + 'Prev')?.split(',');
      if (prev && prev.length) {
        if (prev.length + 1 === itemsComplete.length) {
          total = complete;
        }
      }
    }

    if (complete > total) complete = total;

    let percent = Math.round(Number(complete) * 100 / Number(total));

    if (percent) {
      progressValue.style.width = progressValue.textContent = `${percent}%`;
      progress.classList.add(this.config.activeClass);
    }

    this.hub.dispatchEvent(this.events.progress, {
      bubbles: true,
      cancelable: false,
      detail: {
        items: items,
        progressValue: progressValue,
        progress: progress,
        itemsComplete: itemsComplete,
        total: total,
        root: root,
        complete: complete,
        percent: percent,
        Quiz: this
      }
    });
  }

  getNextIndex(item, items) {
    const radio = item.querySelector('[type="radio"]:checked');
    const nextItemIndex = items.indexOf(item) + 1;
    const nextItem = items[nextItemIndex];

    if (!nextItem) return items[items.length - 1].dataset[this.config.itemKey];

    let nextIndex = nextItem.dataset[this.config.itemKey];
    if (radio && radio.dataset[this.config.nextIndexKey]) {
      nextIndex = radio.dataset[this.config.nextIndexKey];
    } else if (item.dataset[this.config.nextIndexKey]) {
      nextIndex = item.dataset[this.config.nextIndexKey];
    }
    return nextIndex;
  }

  sendHandler(root) {
    const {items, btns, progress, pages, finishItem} = this.getElements(root);
    if (items.length < 2) return;
    items.map(item => item.classList.add(this.config.visabilityClass));
    btns.map(btn => {
      if (btn.dataset[this.config.btnKey] !== 'reset') {
        btn.classList.add(this.config.visabilityClass);
      } else {
        btn.classList.remove(this.config.visabilityClass);
      }
    });

    pages?.classList.add(this.config.visabilityClass);
    this.hub.removeComponentCookie(root.dataset[this.config.rootKey] + 'Current');
    this.hub.removeComponentCookie(root.dataset[this.config.rootKey] + 'Prev');
    if (finishItem) {
      this.resetProgress(progress, 1);
      finishItem.classList.remove(this.config.visabilityClass);
    } else {
      this.reset(root);
    }
  }

  getElements(root) {
    const currentIndex = this.hub.getComponentCookie(root.dataset[this.config.rootKey] + 'Current');

    return {
      items: Array.from(root.querySelectorAll(this.config.rootSelector)),
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
    };
  }
}
