export default class PaginationFactory {
  constructor(config) {
    if (window.SendIt && window.SendIt.PaginationFactory) return window.SendIt.PaginationFactory;
    this.rootSelector = config['rootSelector'] || '[data-pn-pagination]';
    this.config = config;
    this.instances = new Map();

    document.addEventListener('si:init', (e) => {
      this.initialize()
    });
  }

  initialize() {
    this.addInstances(document)

    document.addEventListener('change', (e) => {
      if (e.target.closest(this.config.rootSelector)) {
        const root = e.target.closest(this.rootSelector);
        if (this.instances.has(root)) {
          e.preventDefault();
          this.instances.get(root).changeEventHandler(e.target);
        }
      }
    })

    document.addEventListener('click', (e) => {
      if (e.target.closest(this.config.rootSelector)) {
        const root = e.target.closest(this.rootSelector);
        if (this.instances.has(root)) {
          e.preventDefault();
          this.instances.get(root).clickEventHandler(e.target);
        }
      }
    })

    document.addEventListener(this.config.sendEvent, (e) => {
      if (e.detail.result.data && e.detail.result.data.pagination) {
        const rootSelector = this.rootSelector.replace(']', `="${e.detail.result.data.pagination}"]`);
        const root = document.querySelector(rootSelector);
        if (this.instances.has(root)) {
          this.instances.get(root).responseHandler(e.detail.result);
        }
      }
    })
  }

  addInstances(block) {
    const roots = block.querySelectorAll(this.rootSelector);
    if (roots.length) {
      roots.forEach(root => {
        if (!this.instances.has(root)) {
          this.instances.set(root, new PaginationHandler(root, this.config));
        }
      })
    }
  }
}


class PaginationHandler {
  constructor(root, config) {
    if (window.SendIt && window.SendIt.PaginationHandler) return window.SendIt.PaginationHandler;
    const defaults = {
      sendEvent: 'si:send:finish',
      rootSelector: '[data-pn-pagination]',
      firstPageBtnSelector: '[data-pn-first]',
      lastPageBtnSelector: '[data-pn-last]',
      lastPageKey: 'pnLast',
      prevPageBtnSelector: '[data-pn-prev]',
      nextPageBtnSelector: '[data-pn-next]',
      morePageBtnSelector: '[data-pn-more]',
      resultBlockSelector: '[data-pn-result="${key}"]',
      currentPageInputSelector: '[data-pn-current]',
      totalPagesSelector: '[data-pn-total]',
      totalResultsSelector: '[data-pn-total-results="${key}"]',
      limitSelector: '[data-pn-limit]',
      pageListSelector: '[data-pn-list]',
      pageLinkSelector: '[data-pn-page]',
      pageLinkKey: 'pnPage',
      typeKey: 'pnType',
      hideClass: 'v_hidden',
      activeClass: 'active',
      presetKey: 'siPreset',
      rootKey: 'pnPagination'
    }
    this.wrapper = root;
    this.events = {
      before: 'pn:handle:before',
      after: 'pn:handle:after',
    };
    this.config = Object.assign(defaults, config);
    this.initialize();
  }

  initialize() {
    if (!this.wrapper) return;
    this.key = this.wrapper.dataset[this.config.rootKey];
    const resultBlockSelector = this.config.resultBlockSelector.replace('${key}', this.key);
    const totalResultsSelector = this.config.totalResultsSelector.replace('${key}', this.key);
    this.resultBlock = document.querySelector(resultBlockSelector);
    this.totalResultsBlock = document.querySelector(totalResultsSelector);
    this.pageInput = this.wrapper.querySelector(this.config.currentPageInputSelector);
    this.limitInput = this.wrapper.querySelector(this.config.limitSelector);
    this.gotoFirstBtn = this.wrapper.querySelector(this.config.firstPageBtnSelector);
    this.gotoLastBtn = this.wrapper.querySelector(this.config.lastPageBtnSelector);
    this.gotoNextBtn = this.wrapper.querySelector(this.config.nextPageBtnSelector);
    this.gotoMoreBtn = this.wrapper.querySelector(this.config.morePageBtnSelector);
    this.gotoPrevBtn = this.wrapper.querySelector(this.config.prevPageBtnSelector);
    this.pageList = this.wrapper.querySelector(this.config.pageListSelector);
    this.links = Array.from(this.wrapper.querySelectorAll(this.config.pageLinkSelector));
    this.type = this.wrapper.dataset[this.config.typeKey] || 'classic';
    this.form = this.pageInput.form || this.wrapper.closest('form');
    this.preset = this.pageInput.dataset[this.config.presetKey] || this.form.dataset[this.config.presetKey];

    this.buttonsHandler(this.pageInput.value);

    this.initObserve();
  }

  changeEventHandler(target) {
    if (target.closest(this.config.currentPageInputSelector)) {
      this.resultShowMethod = 'insert';
      this.goto(Number(this.pageInput.value));
    }
    if (target.closest(this.config.limitSelector)) {
      this.resultShowMethod = 'insert';
      this.goto(1);
    }
  }

  clickEventHandler(target) {
    if (target.closest(this.config.pageLinkSelector)) {
      this.resultShowMethod = 'insert';
      this.pageInput.value = target.dataset[this.config.pageLinkKey];
      this.goto(Number(target.dataset[this.config.pageLinkKey]));
    }
    if (target.closest(this.config.lastPageBtnSelector)) {
      this.resultShowMethod = 'insert';
      this.goto(target.closest(this.config.lastPageBtnSelector).dataset[this.config.lastPageKey]);
    }
    if (target.closest(this.config.firstPageBtnSelector)) {
      this.resultShowMethod = 'insert';
      this.goto(1);
    }
    if (target.closest(this.config.nextPageBtnSelector)) {
      this.resultShowMethod = 'insert';
      this.goto(Number(this.pageInput.value) + 1);
    }
    if (target.closest(this.config.morePageBtnSelector)) {
      this.resultShowMethod = 'append';
      this.goto(Number(this.pageInput.value) + 1);
    }
    if (target.closest(this.config.prevPageBtnSelector)) {
      this.resultShowMethod = 'insert';
      this.goto(Number(this.pageInput.value) - 1);
    }
  }

  initObserve() {
    if (this.type === 'scroll' && this.resultBlock) {
      this.wrapper.classList.add(this.config.hideClass);
      this.setSearchParams(1);
      this.observer = new IntersectionObserver(this.scrollHandler.bind(this), {
        threshold: 0.5
      });
      this.resultBlock.lastElementChild && this.observer.observe(this.resultBlock.lastElementChild);
    }
  }

  scrollHandler(entries, observer) {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        observer.unobserve(entry.target);
        const nextPage = Number(this.pageInput.value) + 1;
        if (this.pageInput.max >= nextPage) {
          this.resultShowMethod = 'append';
          this.goto(nextPage);
        }
        this.observer = observer;
      }
    })
  }

  responseHandler(result) {
    if (!result.data) return;
    const totalBlock = this.wrapper.querySelector(this.config.totalPagesSelector);
    const currentPageInput = this.pageInput;
    const lastPage = this.gotoLastBtn;

    if (!document.dispatchEvent(new CustomEvent(this.events.before, {
      bubbles: true,
      cancelable: true,
      detail: {
        result: result,
        PaginationHandler: this
      }
    }))) {
      return;
    }

    this.enabled([this.gotoLastBtn, this.gotoNextBtn, this.gotoMoreBtn, this.gotoFirstBtn, this.gotoPrevBtn, this.pageInput, this.limitInput]);
    if (result.data.totalPages) {
      totalBlock && (totalBlock.textContent = result.data.totalPages);
      lastPage && (lastPage.dataset[this.config.lastPageKey] = result.data.totalPages);
      currentPageInput && (currentPageInput.max = result.data.totalPages);
      this.type !== 'scroll' && this.wrapper.classList[result.data.totalPages > 1 ? 'remove' : 'add'](this.config.hideClass);
    } else {
      result.data.getDisabled && this.wrapper.classList.add(this.config.hideClass);
    }
    if (result.data.currentPage) {
      this.buttonsHandler(result.data.currentPage);
      Number(result.data.currentPage) !== Number(this.pageInput.value) && this.goto(result.data.currentPage, true);
    }
    if (this.observer && this.resultBlock && this.type === 'scroll') {
      this.observer.observe(this.resultBlock.lastElementChild);
    }
    if (this.resultShowMethod === 'insert' && this.resultBlock) {
      this.resultBlock.scrollIntoView({
        behavior: "smooth",
        block: "start",
        inline: "start"
      });
    }

    if (this.pageList && result.data.pageList) {
      this.pageList.innerHTML = result.data.pageList;
    }
    this.resultShowMethod = '';
    this.totalResultsBlock && (this.totalResultsBlock.textContent = result.data.total);
    document.dispatchEvent(new CustomEvent(this.events.after, {
      bubbles: true,
      cancelable: false,
      detail: {
        result: result,
        PaginationHandler: this
      }
    }))
  }

  buttonsHandler(pageNum) {
    if (this.pageInput.max <= pageNum) {
      this.disabled([this.gotoLastBtn, this.gotoNextBtn, this.gotoMoreBtn, this.gotoFirstBtn, this.gotoPrevBtn]);
    } else {
      this.enabled([this.gotoLastBtn, this.gotoNextBtn, this.gotoMoreBtn, this.gotoFirstBtn, this.gotoPrevBtn]);
    }
    if (pageNum >= Number(this.pageInput.max)) {
      this.disabled([this.gotoLastBtn, this.gotoNextBtn, this.gotoMoreBtn,]);
    } else {
      this.enabled([this.gotoLastBtn, this.gotoNextBtn, this.gotoMoreBtn,]);
    }
    if (pageNum <= 1) {
      this.disabled([this.gotoFirstBtn, this.gotoPrevBtn]);
    } else {
      this.enabled([this.gotoFirstBtn, this.gotoPrevBtn]);
    }
  }

  disabled(elements) {
    elements.length && elements.forEach(el => {
      el && (el.disabled = true)
    })
  }

  enabled(elements) {
    elements.length && elements.forEach(el => {
      el && (el.disabled = false)
    })
  }

  goto(pageNum, nosend = false) {
    pageNum = pageNum || 1;
    if (pageNum >= Number(this.pageInput.max)) {
      pageNum = Number(this.pageInput.max);
    }
    if (pageNum <= 1) {
      pageNum = 1;
    }
    this.pageInput.value = pageNum;
    if (this.type === 'classic') {
      this.setSearchParams(pageNum);
    }

    this.form && !nosend && this.sendResponse();
  }

  setSearchParams(pageNum) {
    const url = window.location.href;
    const params = new URLSearchParams(window.location.search);
    const key = this.key + 'page';
    if (pageNum > 1) {
      params.set(key, pageNum);
    } else {
      params.delete(key);
    }

    if (params && params.toString()) {
      window.history.replaceState({}, '', url.split('?')[0] + '?' + params.toString());
    } else {
      window.history.replaceState({}, '', url.split('?')[0]);
    }
  }

  async sendResponse() {
    this.resultShowMethod && this.setResultShowMethod();
    const params = new FormData(this.form);
    this.disabled([this.gotoLastBtn, this.gotoNextBtn, this.gotoMoreBtn, this.gotoFirstBtn, this.gotoPrevBtn, this.pageInput, this.limitInput]);
    await SendIt.Sending.prepareSendParams(this.form, this.preset, params);
  }

  setResultShowMethod() {
    const resultShowMethodInput = this.form.querySelector('input[name="resultShowMethod"]');
    if (resultShowMethodInput) {
      resultShowMethodInput.value = this.resultShowMethod;
    } else {
      const hiddenField = document.createElement('input');
      hiddenField.type = 'hidden';
      hiddenField.name = 'resultShowMethod';
      hiddenField.value = this.resultShowMethod;
      this.form.appendChild(hiddenField);
    }
  }
}
