export const ModulesConfig = {
  Helpers: {
    className: 'Helpers',
    pathToScripts: '../modules/helpers.js',
    forceLoad: true
  },
  UserBehaviorTracker: {
    className: 'UserBehaviorTracker',
    pathToScripts: '../modules/userbehaviortracker.js',
    debug: false,
    forceLoad: true,
    autoUpdateTime: 0,          // Период автообновления информации о поведении пользователя, 0 - автообновление не работает.
    maxEvents: 100,             // Максимальное количество анализируемых событий
    minBotScoreAmount: 50,      // Минимальное количество баллов для признания пользователя ботом
    cooldownMultiplier: 0.02,   // Множитель для охлаждения счёта между обновлениями
    cooldownTime: 5,            // Время через которое счётчик подозрительности обнуляется в минутах
    maxSum: 150,                // Если сумма процентов по всем показателям будет больше или равна этому параметру пользователь станет считаться ботом
    weights: {
      mouseMovements: 1,        // Вес анализа движений мыши в общем счёте
      clicks: 1,                // Вес анализа кликов в общем счёте
      keystrokes: 1,            // Вес анализа нажатий клавиш в общем счёте
      scrolls: 1,               // Вес анализа скроллинга в общем счёте
      timing: 1,                // Вес анализа временных характеристик в общем счёте
      behaviorPatterns: 1,        // Вес анализа временных характеристик в общем счёте
      recentBehavior: 1         // Вес последних действий на общий счёт (0-1)
    },
    mouse: {
      minMovements: 50,            // Минимальное количество движений для анализа
      straightLineThreshold: 0.7, // Порог определения прямолинейного движения (0-1)
      rightAngleThreshold: 0.3,   // Порог определения прямого угла (0-1)
    },
    clicks: {
      minClicks: 3,                    // Минимальное количество кликов для анализа
      positionTolerance: 5,            // Допустимое отклонение позиции (в пикселях)
      samePositionThreshold: 0.6,      // Порог определения кликов в одной позиции (0-1)
      sameLocationThreshold: 0.5,      // Порог определения кликов в одной области (0-1)
      naturalVariationThreshold: 0.4,  // Порог естественного разброса кликов (0-1)
      naturalVariationInterval: 150,   // Естественный интервал между нажатиями мс
      stdDevThreshold: 30,             // Максимальное стандартное отклонение позиций
      avgIntervalThreshold: 400,       // Средний интервал между кликами (мс)
      highVarianceThreshold: 250,      // Порог высокой вариативности интервалов
      minVarianceThreshold: 100,       // Порог минимальной вариативности интервалов
    },
    keystrokes: {
      minKeystrokes: 5,                // Минимальное количество нажатий для анализа
      naturalVariationThreshold: 0.5,  // Порог естественного разброса нажатий (0-1)
      naturalVariationInterval: 75,    // Естественный интервал между нажатиями мс
      stdDevThreshold: 30,             // Максимальное стандартное отклонение интервалов
      fastTypingThreshold: 100,        // Порог быстрого набора (символов в минуту)
      veryFastTypingThreshold: 50,     // Порог очень быстрого набора (символов в минуту)
      highVarianceThreshold: 50,       // Порог высокой вариативности интервалов
      minVarianceThreshold: 40,        // Порог минимальной вариативности интервалов
      repeatedPatternMultiplier: 5     // Множитель для повторяющихся паттернов набора
    },
    scrolls: {
      minScrolls: 5,              // Минимальное количество скроллов для анализа
      stdDevThreshold: 5,         // Максимальное стандартное отклонение дистанции скролла
      avgDistanceThreshold: 10,   // Порог средней дистанции скролла
    },
    behavior: {
      historySize: 30,                        // Максимальное количество записей в истории
      recentWindow: 10,                       // Размер окна для анализа последних действий
      minTimeThreshold: 5000,                 // Минимальное время наблюдения (мс)
      suspiciousPatternThreshold: 0.8,        // Порог подозрительных паттернов (0-1)
      humanLikePatternThreshold: 0.3,         // Порог человеко-подобного поведения (0-1)
      maxInteractionsPerSecondThreshold: 100, // Макс. кол-во действий в секунду
      minInteractionsPerSecondThreshold: 10,  // Мин. кол-во действий в секунду
    }
  },
  Sending: {
    forceLoad: true,
    className: 'Sending',
    pathToScripts: '../modules/sending.js',
    rootSelector: '[data-si-form]',
    presetSelector: '[data-si-preset]',
    rootKey: 'siForm',
    presetKey: 'siPreset',
    eventKey: 'siEvent',
    goalKey: 'siGoal',
    actionUrl: '/assets/components/sendit/action.php',
    errorBlockSelector: '[data-si-error="${fieldName}"]',
    eventSelector: '[data-si-event="${eventName}"]',
    errorClass: 'si-error'
  },
  Notify: {
    forceLoad: true,
    className: 'Notify',
    pathToScripts: '../modules/notify.js',
    jsPath: '/assets/components/sendit/js/web/lib/izitoast/iziToast.js',
    cssPath: '/assets/components/sendit/css/web/lib/izitoast/iziToast.min.css',
    handlerClassName: 'iziToast',
    toastSelector: '.iziToast',
    typeSelectors: {
      success: '.iziToast-color-green',
      info: '.iziToast-color-blue',
      error: '.iziToast-color-red',
      warning: '.iziToast-color-yellow',
    },
    titleSelector: '.iziToast-title',
    handlerOptions: {
      timeout: 2500,
      position: 'topCenter'
    }
  },
  FileUploaderFactory: {
    className: 'FileUploaderFactory',
    pathToScripts: '../modules/fileuploader.js',
    formSelector: '[data-si-form]',
    progressSelector: '[data-fu-progress]',
    rootSelector: '[data-fu-wrap]',
    tplSelector: '[data-fu-tpl]',
    dropzoneSelector: '[data-fu-dropzone]',
    fileListSelector: '[data-fu-list]',
    progressIdAttr: 'data-fu-id',
    progressTextAttr: 'data-fu-text',
    hideBlockSelector: '[data-fu-hide]',
    presetSelector: '[data-si-preset]',
    presetKey: 'siPreset',
    sendEvent: 'si:send:after',
    pathKey: 'fuPath',
    pathAttr: 'data-fu-path',
    actionUrl: '/assets/components/sendit/action.php',
    hiddenClass: 'v_hidden',
    progressClass: 'progress__line',
    showTime: true
  },
  PaginationFactory: {
    className: 'PaginationFactory',
    pathToScripts: '../modules/paginationhandler.js',
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
    limitSelector: '[data-pn-limit]',
    typeKey: 'pnType',
    hideClass: 'v_hidden',
    presetKey: 'siPreset',
    rootKey: 'pnPagination'
  },
  SaveFormData: {
    className: 'SaveFormData',
    pathToScripts: '../modules/saveformdata.js',
    rootSelector: '[data-si-form]',
    noSaveSelector: '[data-si-nosave]',
    rootKey: 'siForm',
    resetEvent: 'si:send:reset'
  },
  QuizForm: {
    className: 'QuizForm',
    pathToScripts: '../modules/quizform.js',
    formSelector: '[data-si-form]',
    rootKey: 'siForm',
    autoKey: 'qfAuto',
    rootSelector: '[data-qf-item]',
    itemKey: 'qfItem',
    itemCompleteSelector: '[data-qf-complete="1"]',
    itemCompleteKey: 'qfComplete',
    finishSelector: '[data-qf-finish]',
    itemCurrentSelector: '[data-qf-item="${currentIndex}"]',
    btnSelector: '[data-qf-btn]',
    btnKey: 'qfBtn',
    btnNextSelector: '[data-qf-btn="next"]',
    btnPrevSelector: '[data-qf-btn="prev"]',
    btnSendSelector: '[data-qf-btn="send"]',
    btnResetSelector: '[data-qf-btn="reset"]',
    nextIndexSelector: '[data-qf-next]',
    nextIndexKey: 'qfNext',
    progressSelector: '[data-qf-progress]',
    currentQuestionSelector: '[data-qf-page]',
    totalQuestionSelector: '[data-qf-total]',
    pagesSelector: '[data-qf-pages]',
    progressValueSelector: '[data-qf-progress-value]',
    activeClass: 'active',
    visabilityClass: 'v_hidden',
    disabledClass: 'disabled',
    sendEvent: 'si:send:finish',
  },
};
